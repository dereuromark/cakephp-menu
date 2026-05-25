<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\View\Helper;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\Route;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Menu\Item\SelfRendererInterface;
use Menu\Menu;
use Menu\Renderer\BreadcrumbRenderer;
use Menu\Resolver\AuthorizationResolver;
use Menu\Resolver\ResolverCollection;
use Menu\Resolver\ResolverContext;
use Menu\Resolver\SectionResolver;
use Menu\View\Helper\MenuHelper;

class MenuHelperTest extends TestCase
{
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
            'menuAttributes' => ['class' => 'nav'],
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

        $this->assertSame($child, $currentItem);
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
        $this->assertStringContainsString('<ul>', $html);
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
}
