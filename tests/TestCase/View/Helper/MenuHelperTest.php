<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\View\Helper;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\Routing\Route\Route;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use CakeMenu\Item\Item;
use CakeMenu\Item\ItemInterface;
use CakeMenu\Item\SelfRendererInterface;
use CakeMenu\Menu;
use CakeMenu\MenuInterface;
use CakeMenu\Renderer\BreadcrumbRenderer;
use CakeMenu\Renderer\StringTemplateRenderer;
use CakeMenu\Resolver\AuthorizationResolver;
use CakeMenu\Resolver\LoggedInResolver;
use CakeMenu\Resolver\ResolverCollection;
use CakeMenu\Resolver\ResolverContext;
use CakeMenu\Resolver\SectionResolver;
use CakeMenu\View\Helper\MenuHelper;
use InvalidArgumentException;
use RuntimeException;

class MenuHelperTest extends TestCase
{
    public function testBreadcrumbTitlesEscapeTranslateAndRespectOptOut(): void
    {
        $request = (new ServerRequest(['url' => '/target']))
            ->withUri((new ServerRequest())->getUri()->withPath('/target'));
        $helper = $this->createHelper($request);
        $menu = $helper->create('crumbs');
        $item = $menu->addItem('<script>x</script>', '/target');

        $this->assertSame('&lt;script&gt;x&lt;/script&gt;', $helper->getBreadcrumbs('crumbs')[0]['title']);
        $helper->populateBreadcrumbs('crumbs');
        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $helper->Breadcrumbs->render());

        $item->setLabel('<script>x</script>', false);
        $this->assertSame('<script>x</script>', $helper->getBreadcrumbs('crumbs')[0]['title']);

        $oldLocale = I18n::getLocale();
        try {
            I18n::setLocale('en_US');
            I18n::setTranslator('default', static function (): Package {
                $package = new Package('default');
                $package->setMessages(['<script>x</script>' => '<b>Translated</b>']);

                return $package;
            }, 'en_US');
            $item->setLabel('<script>x</script>');
            $this->assertSame('&lt;b&gt;Translated&lt;/b&gt;', $helper->getBreadcrumbs('crumbs', ['translate' => true])[0]['title']);
        } finally {
            I18n::clear();
            I18n::setLocale($oldLocale);
        }
    }

    public function testCurrentItemKeepsAncestorsAndResolvedState(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view/42']))
            ->withAttribute('params', ['controller' => 'Articles', 'action' => 'view', 'pass' => [42]])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view/42'));
        $helper = $this->createHelper($request);
        $menu = $helper->create('trail');
        $parent = $menu->addItem('Articles', ['controller' => 'Articles']);
        $child = $parent->getSubMenu()->addItem('View', ['controller' => 'Articles', 'action' => 'view']);

        $current = $helper->getCurrentItem('trail');

        $this->assertNotNull($current);
        $this->assertTrue($current->isActive());
        $this->assertSame(['Articles', 'View'], array_map(
            static fn ($item) => $item->getLabel(),
            $helper->extractPath($current),
        ));
        $this->assertNotSame($child, $current);
        $this->assertFalse($child->isActive());
    }

    public function testCurrentItemFromSubmenuKeepsFullPathAndFrozenState(): void
    {
        $helper = $this->createHelper(new ServerRequest(['url' => '/articles']));
        $menu = $helper->create('nested');
        $sub = $menu->addItem('Admin', '#')->getSubMenu();
        $sub->addItem('Articles', '/articles');
        $menu->freeze();

        $current = $helper->getCurrentItem($sub);

        $this->assertNotNull($current);
        $this->assertSame(['Admin', 'Articles'], array_map(static fn ($item) => $item->getLabel(), $current->getPath()));
        $this->assertSame(1, $current->getLevel());
        $this->assertTrue($current->isFrozen());
        $this->assertSame(['Admin', 'Articles'], array_column($helper->getBreadcrumbs($sub), 'title'));
    }

    public function testCurrentItemStaysIndependentForShallowCloningMenu(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view/42']))
            ->withAttribute('params', ['controller' => 'Articles', 'action' => 'view', 'pass' => [42]])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view/42'));
        $helper = $this->createHelper($request);
        $menu = new class extends Menu {
            public function __clone(): void
            {
            }
        };
        $live = $menu->addItem('View', ['controller' => 'Articles', 'action' => 'view']);

        $current = $helper->getCurrentItem($menu);

        $this->assertNotNull($current);
        $this->assertNotSame($live, $current);
        $this->assertTrue($current->isActive());
        $this->assertFalse($live->isActive());
    }

    public function testNestedFuzzyMatchesUseDeepestItemWithoutSingleActive(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view/42']))
            ->withAttribute('params', ['controller' => 'Articles', 'action' => 'view', 'pass' => [42]])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view/42'));
        $helper = $this->createHelper($request);
        $menu = $helper->create('nested');
        $parent = $menu->addItem('Articles', ['controller' => 'Articles']);
        $child = $parent->getSubMenu()->addItem('View', ['controller' => 'Articles', 'action' => 'view']);

        $this->assertSame('View', $helper->getCurrentItem('nested', ['singleActive' => false])?->getLabel());
        $this->assertSame(['Articles', 'View'], array_column($helper->getBreadcrumbs('nested', ['singleActive' => false]), 'title'));
    }

    public function testVisibilityDoesNotLeakBetweenRenders(): void
    {
        $helper = $this->createHelper(new ServerRequest());
        $menu = $helper->create('private');
        $item = $menu->addItem('Private', '/private')->setData('auth', 'loggedIn');

        $this->assertSame('<ul></ul>', $helper->render('private', ['resolver' => new LoggedInResolver(false)]));
        $this->assertNull($item->getRuntimeState()['visible']);
        $this->assertStringContainsString('Private', $helper->render('private', ['resolver' => new LoggedInResolver(true)]));
    }

    public function testRenderPreservesVisibilityResolvedBeforeHelperCall(): void
    {
        $helper = $this->createHelper(new ServerRequest());
        $menu = $helper->create('private');
        $item = $menu->addItem('Profile', '/profile')->setData('auth', 'loggedIn');
        $menu->resolve(new LoggedInResolver(false));

        $this->assertSame('<ul></ul>', $helper->render($menu));
        $this->assertFalse($item->isVisible());
        $this->assertFalse($item->getRuntimeState()['visible']);
    }

    public function testRenderClearsStaleRuntimeActiveAndExpandedButKeepsVisibility(): void
    {
        $helper = $this->createHelper(new ServerRequest(['url' => '/users']));
        $menu = $helper->create('state');
        $stale = $menu->addItem('Articles', '/articles');
        $stale->getSubMenu()->addItem('Hidden', '/hidden');
        $stale->setRuntimeActive(true)->setRuntimeExpanded(true);
        $hidden = $menu->addItem('Private', '/private')->setRuntimeVisible(false);
        $menu->addItem('Users', '/users');

        $html = $helper->render($menu);

        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertSame(1, substr_count($html, 'class="active"'));
        $this->assertStringNotContainsString('Private', $html);
        $this->assertSame('Users', $helper->getCurrentItem($menu)?->getLabel());
        $this->assertSame(['Users'], array_column($helper->getBreadcrumbs($menu), 'title'));
        $this->assertTrue($stale->getRuntimeState()['active']);
        $this->assertTrue($stale->getRuntimeState()['expanded']);
        $this->assertFalse($hidden->getRuntimeState()['visible']);
    }

    public function testBreadcrumbsOmitHiddenActiveItemAndAncestors(): void
    {
        $request = new ServerRequest(['url' => '/secret']);
        $helper = $this->createHelper($request);
        $menu = $helper->create('private');
        $parent = $menu->addItem('Private', '/private');
        $child = $parent->getSubMenu()->addItem('Secret', '/secret');

        foreach (['authoring', 'runtime'] as $mode) {
            if ($mode === 'authoring') {
                $parent->setVisible(false);
            } else {
                $parent->setVisible(true)->setRuntimeVisible(false);
            }
            $this->assertSame([], $helper->getBreadcrumbs($menu));
            $helper->populateBreadcrumbs($menu);
            $this->assertSame('', $helper->Breadcrumbs->render());
            $this->assertSame('', $helper->renderBreadcrumbs($menu));
            $this->assertNull($helper->getCurrentItem($menu));
        }

        $parent->setRuntimeVisible(true);
        $child->setVisible(false);
        $this->assertSame([], $helper->getBreadcrumbs($menu));
        $this->assertSame('', $helper->renderBreadcrumbs($menu));
    }

    public function testBreadcrumbsSelectVisibleActiveSibling(): void
    {
        $helper = $this->createHelper(new ServerRequest(['url' => '/target']));
        $menu = $helper->create('siblings');
        $menu->addItem('Hidden', '/target')->setVisible(false);
        $menu->addItem('Visible', '/target');

        $this->assertSame('Visible', $helper->getCurrentItem($menu)?->getLabel());
        $this->assertSame(['Visible'], array_column($helper->getBreadcrumbs($menu), 'title'));
        $this->assertStringNotContainsString('Hidden', $helper->renderBreadcrumbs($menu));
    }

    protected function createSelfRenderingItem(string $label, string $url, string $id): ItemInterface
    {
        return new class ($label, $url, $id) extends Item implements SelfRendererInterface {
            public function __construct(string $label, string $url, string $id)
            {
                parent::__construct($label, $url);
                $this->setId($id);
            }

            public function render(): string
            {
                return '<li class="custom-item">custom</li>';
            }
        };
    }

    protected function createHelper(ServerRequest $request): MenuHelper
    {
        return new MenuHelper(new View(
            request: $request,
            response: new Response(),
        ));
    }

    public function testRender(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());
        $menu = Menu::create();
        $menu->addItem('First', '/x');
        $menu->addItem('Second', '/y');

        $html = $menuHelper->render($menu);

        $this->assertSame(
            '<ul><li><a href="/x">First</a></li><li><a href="/y">Second</a></li></ul>',
            $html,
        );
    }

    public function testRenderDoesNotPinRuntimeStateAfterRestore(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());
        $menu = Menu::create();
        $item = $menu->addItem('First', '/x');

        $menuHelper->render($menu);
        $item->setVisible(false);

        $this->assertFalse($item->isVisible());
        $this->assertSame(['visible' => null, 'active' => null, 'expanded' => null], $item->getRuntimeState());
    }

    public function testCreateRenderAndGetByName(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view']))
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => [42],
            ])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view'));

        $menuHelper = $this->createHelper($request);
        $menu = $menuHelper->create('main', [
            'attributes' => ['class' => 'nav'],
        ]);
        $menu->addItem('Home', '/');
        $menu->addItem('Articles', '/articles/view', [
            'fuzzy' => true,
        ]);

        $html = $menuHelper->render('main');

        $this->assertSame($menu, $menuHelper->get('main'));
        $this->assertStringContainsString('<ul class="nav">', $html);
        $this->assertStringContainsString('class="active"', $html);
    }

    public function testGetCurrentItemAndExtractPath(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view/42']))
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => [42],
            ])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view/42'));
        $menuHelper = $this->createHelper($request);

        $menu = $menuHelper->create('main');
        $parent = $menu->addItem('Articles', ['controller' => 'Articles', 'action' => 'index']);
        $child = $parent->getSubMenu()->addItem('View', ['controller' => 'Articles', 'action' => 'view'], [
            'fuzzy' => true,
        ]);

        $currentItem = $menuHelper->getCurrentItem('main');
        $path = $menuHelper->extractPath($child);

        $this->assertInstanceOf(ItemInterface::class, $currentItem);
        $this->assertNotSame($child, $currentItem);
        $this->assertSame($child->getId(), $currentItem->getId());
        $this->assertTrue($currentItem->isActive());
        $this->assertNull($child->getRuntimeState()['active']);
        $this->assertSame($parent->getId(), $currentItem->getParent()?->getId());
        $this->assertNotSame($parent, $currentItem->getParent());
        $this->assertSame([$parent, $child], $path);
    }

    public function testHelperLifecycleMethods(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());
        $menuHelper->create('main');

        $this->assertTrue($menuHelper->has('main'));

        $menuHelper->remove('main');
        $this->assertFalse($menuHelper->has('main'));

        $menuHelper->create('secondary');
        $menuHelper->reset();

        $this->assertFalse($menuHelper->has('secondary'));
    }

    public function testGetBreadcrumbsAndRenderBreadcrumbs(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view/42']))
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => [42],
            ])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view/42'));
        $menuHelper = $this->createHelper($request);

        $menu = $menuHelper->create('main');
        $parent = $menu->addItem('Articles', '/articles');
        $parent->getSubMenu()->addItem('View', '/articles/view/42');

        $crumbs = $menuHelper->getBreadcrumbs('main');
        $html = $menuHelper->renderBreadcrumbs('main');

        $this->assertCount(2, $crumbs);
        $this->assertSame('Articles', $crumbs[0]['title']);
        $this->assertSame('/articles', $crumbs[0]['url']);
        $this->assertNull($crumbs[1]['url']);
        $this->assertStringContainsString('<ol class="breadcrumb">', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function testRenderBreadcrumbsWithBreadcrumbRenderer(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view']))
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'view',
                '_route' => new Route('/articles/view', ['controller' => 'Articles', 'action' => 'view'], ['_name' => 'articles:view']),
            ])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view'));
        $menuHelper = $this->createHelper($request);

        $menu = $menuHelper->create('main');
        $menu->addItem('Articles', ['_name' => 'articles:view']);

        $html = $menuHelper->renderBreadcrumbs('main', ['renderer' => BreadcrumbRenderer::class]);

        $this->assertStringContainsString('<nav aria-label="breadcrumb">', $html);
    }

    public function testBreadcrumbsIgnoreActiveOnLink(): void
    {
        $helper = $this->createHelper(new ServerRequest(['url' => '/articles']));
        $menu = $helper->create('crumbs');
        $menu->addItem('Articles', '/articles');

        $html = $helper->renderBreadcrumbs($menu, ['activeOn' => 'link', 'linkCurrent' => true]);

        $this->assertStringContainsString('<li class="breadcrumb-item active"><a href="/articles" aria-current="page">Articles</a></li>', $html);
        $this->assertStringNotContainsString('<a href="/articles" aria-current="page" class="active">', $html);
    }

    public function testGetOrCreateAndDuplicateCreateProtection(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());
        $menu = $menuHelper->getOrCreate('main');

        $this->assertSame($menu, $menuHelper->getOrCreate('main'));

        $this->expectExceptionMessage('Menu `main` already exists.');
        $menuHelper->create('main');
    }

    public function testRegisterBuildsMenuLazily(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());

        $menu = $menuHelper->register('main', static function ($menu): void {
            $menu->addItem('Articles', '/articles');
        });

        $menuAgain = $menuHelper->register('main', static function ($menu): void {
            $menu->addItem('Users', '/users');
        });

        $this->assertSame($menu, $menuHelper->get('main'));
        $this->assertSame($menuAgain, $menuHelper->get('main'));
        $this->assertCount(1, $menu->getItems());
    }

    public function testRegisterSupportsMenuAndHelperCallbackSignature(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());

        $menu = $menuHelper->register('main', static function ($menu, MenuHelper $helper): void {
            $menu->addItem('Articles', '/articles');
            $helper->has('main');
        });

        $this->assertCount(1, $menu->getItems());
    }

    public function testRegisterCanRebuildExistingMenu(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());

        $menuHelper->register('main', static function ($menu): void {
            $menu->addItem('Articles', '/articles');
        });
        $rebuiltMenu = $menuHelper->register('main', static function ($menu): void {
            $menu->addItem('Users', '/users');
        }, ['rebuild' => true]);

        $this->assertCount(1, $rebuiltMenu->getItems());
        $this->assertSame('Users', $rebuiltMenu->getItems()[0]->getLabel());
    }

    public function testRenderResetsResolverStateBetweenCalls(): void
    {
        $request = (new ServerRequest(['url' => '/articles']))
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'index',
            ])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles'));
        $menuHelper = $this->createHelper($request);

        $menu = $menuHelper->create('main');
        $menu->addItem('Admin', '/admin', ['data' => ['role' => 'admin']]);
        $menu->addItem('Articles', '/articles', [
            'data' => ['section' => ['controller' => 'Articles']],
        ]);

        $firstHtml = $menuHelper->render('main', [
            'resolver' => (new ResolverCollection())
                ->add(new SectionResolver($request))
                ->add(new AuthorizationResolver(static function (ItemInterface $item, ResolverContext $context): ?bool {
                    if ($item->getData('role') === 'admin') {
                        return false;
                    }

                    return null;
                })),
        ]);
        $secondHtml = $menuHelper->render('main', [
            'resolver' => new ResolverCollection(),
        ]);

        $this->assertStringNotContainsString('Admin', $firstHtml);
        $this->assertStringContainsString('class="active"', $firstHtml);
        $this->assertStringContainsString('Admin', $secondHtml);
        $this->assertStringNotContainsString('class="active"', $secondHtml);
        $this->assertTrue($menu->getItems()[0]->isVisible());
        $this->assertFalse($menu->getItems()[1]->isActive());
        $this->assertFalse($menu->getItems()[1]->isExpanded());
    }

    public function testRenderPreservesSelfRenderingCustomItems(): void
    {
        $request = (new ServerRequest(['url' => '/custom']))
            ->withUri((new ServerRequest())->getUri()->withPath('/custom'));
        $menuHelper = $this->createHelper($request);

        $menu = $menuHelper->create('main');
        $item = $this->createSelfRenderingItem('Custom', '/custom', 'custom');
        $menu->add($item);

        $html = $menuHelper->render('main');

        $this->assertStringContainsString('<li class="custom-item">custom</li>', $html);
        $this->assertSame($item, $menu->get('custom'));
    }

    public function testAuthorizationResolverAndDepthLimit(): void
    {
        $request = (new ServerRequest(['url' => '/articles/view/42']))
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => [42],
            ])
            ->withUri((new ServerRequest())->getUri()->withPath('/articles/view/42'));
        $menuHelper = $this->createHelper($request);

        $menu = $menuHelper->create('main');
        $menu->addItem('Admin', '/admin', ['data' => ['role' => 'admin']]);
        $parent = $menu->addItem('Articles', '/articles');
        $parent->getSubMenu()->addItem('View', '/articles/view/42');

        $html = $menuHelper->render('main', [
            'resolveDepth' => 1,
            'resolver' => (new ResolverCollection())
                ->add(new AuthorizationResolver(static function (ItemInterface $item, ResolverContext $context): ?bool {
                    if ($item->getData('role') === 'admin') {
                        return false;
                    }

                    return null;
                })),
        ]);

        $this->assertStringNotContainsString('Admin', $html);
        $this->assertNull($menuHelper->getCurrentItem('main', ['resolveDepth' => 1]));
    }

    public function testAdditionalResolversAugmentDefaults(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest(['url' => '/articles']));

        $menu = Menu::create();
        $menu->addItem('Articles', '/articles');
        $menu->addItem('Secret', '/secret', ['data' => ['hide' => true]]);

        $html = $menuHelper->render($menu, [
            'additionalResolvers' => [
                new AuthorizationResolver(
                    static fn (ItemInterface $item): ?bool => $item->getData('hide') ? false : null,
                ),
            ],
        ]);

        // Default URL resolver still marks the matching item active...
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('Articles', $html);
        // ...and the additional resolver hides the flagged item.
        $this->assertStringNotContainsString('Secret', $html);
    }

    public function testAdditionalResolversRejectInvalidEntries(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest());
        $menu = Menu::create();
        $menu->addItem('Home', '/');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('additionalResolvers');
        $menuHelper->render($menu, ['additionalResolvers' => ['not-a-resolver']]);
    }

    public function testReturnsFirstMatchWithoutSingleActive(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest(['url' => '/articles']));
        $menu = Menu::create();
        $parent = $menu->addItem('Section', '/articles');
        $parent->getSubMenu()->addItem('Articles', '/articles');

        $this->assertSame('Articles', $menuHelper->getCurrentItem($menu)?->getLabel());
    }

    public function testSingleActivePrefersDeepestMatch(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest(['url' => '/articles']));
        $menu = Menu::create();
        $parent = $menu->addItem('Section', '/articles');
        $parent->getSubMenu()->addItem('Articles', '/articles');

        $this->assertSame('Articles', $menuHelper->getCurrentItem($menu, ['singleActive' => true])?->getLabel());
    }

    public function testSingleActiveSkipsHiddenMatches(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest(['url' => '/articles']));
        $menu = Menu::create();
        $parent = $menu->addItem('Section', '/articles');
        $parent->getSubMenu()->addItem('Hidden', '/articles', ['visible' => false]);

        // The deeper match is hidden, so the visible parent stays the active item.
        $this->assertSame('Section', $menuHelper->getCurrentItem($menu, ['singleActive' => true])?->getLabel());
    }

    public function testSingleActiveClearsEarlierHiddenMatch(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest(['url' => '/articles']));
        $menu = Menu::create();
        // A hidden active item appears before the visible match in document order.
        $menu->addItem('HiddenFirst', '/articles', ['visible' => false]);
        $menu->addItem('Visible', '/articles');

        $this->assertSame('Visible', $menuHelper->getCurrentItem($menu, ['singleActive' => true])?->getLabel());
    }

    public function testSingleActiveAffectsBreadcrumbs(): void
    {
        $menuHelper = $this->createHelper(new ServerRequest(['url' => '/articles']));
        $menu = Menu::create();
        $parent = $menu->addItem('Section', '/articles');
        $parent->getSubMenu()->addItem('Articles', '/articles');

        $crumbs = $menuHelper->getBreadcrumbs($menu, ['singleActive' => true]);

        // Breadcrumbs follow the deepest match's trail, not just the first match.
        $this->assertCount(2, $crumbs);
        $this->assertSame('Section', $crumbs[0]['title']);
        $this->assertSame('Articles', $crumbs[1]['title']);
    }

    public function testBuildOptionsDoNotReachRenderer(): void
    {
        $helper = $this->createHelper(new ServerRequest());
        $renderer = new class extends StringTemplateRenderer {
            public function render(MenuInterface $menu, array $options = []): string
            {
                foreach (['cache', 'overwrite', 'rebuild', 'attributes'] as $key) {
                    if (array_key_exists($key, $options)) {
                        throw new RuntimeException('Build option reached renderer: ' . $key);
                    }
                }

                return $options['custom'];
            }
        };
        $options = ['cache' => false, 'overwrite' => true, 'rebuild' => true, 'attributes' => ['class' => 'nav'], 'renderer' => $renderer, 'custom' => 'kept'];
        $helper->create('created', $options);
        $this->assertSame('kept', $helper->render('created'));
        $helper->register('registered', static function ($menu, $receivedHelper) use ($helper): void {
            self::assertSame($helper, $receivedHelper);
            self::assertSame(2, func_num_args());
        }, $options);
        $this->assertSame('kept', $helper->render('registered'));
    }

    public function testBreadcrumbSubclassAndCakeHelper(): void
    {
        $helper = $this->createHelper(new ServerRequest());
        $menu = $helper->create('main');
        $menu->addItem('Current')->setActive(true);
        $renderer = new class extends BreadcrumbRenderer {
            public function render(MenuInterface $menu, array $options = []): string
            {
                return 'custom';
            }
        };
        $this->assertSame('custom', $helper->renderBreadcrumbs('main', ['resolve' => false, 'renderer' => $renderer::class]));
        $this->assertSame('custom', $helper->renderBreadcrumbs('main', ['resolve' => false, 'renderer' => $renderer]));
        $helper->populateBreadcrumbs('main', ['resolve' => false]);
        $this->assertStringContainsString('Current', $helper->Breadcrumbs->render());
    }
}
