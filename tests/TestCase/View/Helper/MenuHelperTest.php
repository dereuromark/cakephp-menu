<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\View\Helper;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\Route;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Menu\Item\ItemInterface;
use Menu\Menu;
use Menu\Renderer\BreadcrumbRenderer;
use Menu\Resolver\AuthorizationResolver;
use Menu\Resolver\ResolverCollection;
use Menu\Resolver\ResolverContext;
use Menu\View\Helper\MenuHelper;

class MenuHelperTest extends TestCase
{
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

        $this->assertSame($menu, $menuHelper->get('main'));
        $this->assertCount(1, $menu->getItems());
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
