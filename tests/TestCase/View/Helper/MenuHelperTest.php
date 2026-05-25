<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\View\Helper;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Menu\Menu;
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
}
