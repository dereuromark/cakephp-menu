<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Menu;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Resolver\CallbackResolver;
use Menu\Resolver\UrlArrayResolver;

class MenuTest extends TestCase
{
    public function testAttributeMerging(): void
    {
        $menu = Menu::create()
            ->setAttributes(['class' => 'primary'])
            ->setAttributes(['id' => 'main-nav'], true);

        $this->assertSame([
            'id' => 'main-nav',
            'class' => 'primary',
        ], $menu->getAttributes());
    }

    public function testAddGetAndRemove(): void
    {
        $menu = Menu::create();
        $dashboard = $menu->addItem('Dashboard', '/dashboard', ['id' => 'dashboard']);
        $settings = $menu->addItem('Settings', '/settings', ['id' => 'settings']);

        $this->assertSame($dashboard, $menu->get('dashboard'));
        $this->assertTrue($menu->has('settings'));

        $menu->remove('settings');

        $this->assertNull($menu->get('settings'));
        $this->assertCount(1, $menu->getItems());
        $this->assertSame([$dashboard], $menu->getItems());
    }

    public function testFilterAndSort(): void
    {
        $menu = Menu::create();
        $menu->addItem('Users', '/users', ['id' => 'users'])->setData('weight', 20);
        $menu->addItem('Dashboard', '/dashboard', ['id' => 'dashboard'])->setData('weight', 10);
        $menu->addItem('Hidden', '/hidden', ['id' => 'hidden'])->setVisibility(false);

        $menu->filter(static fn ($item) => $item->isVisible());
        $menu->sortBy('weight');

        $items = $menu->getItems();
        $this->assertCount(2, $items);
        $this->assertSame('dashboard', $items[0]->getId());
        $this->assertSame('users', $items[1]->getId());
    }

    public function testResolveRecursively(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'view',
            'pass' => [42],
        ]);

        $menu = Menu::create();
        $parent = $menu->addItem('Articles', ['controller' => 'Articles', 'action' => 'index'], ['id' => 'articles']);
        $parent->add((new Menu())->newItem('View', ['controller' => 'Articles', 'action' => 'view', 42], ['id' => 'view']));

        $menu->resolve(new UrlArrayResolver($request));

        $this->assertFalse($parent->isActive());
        $this->assertTrue($menu->get('view')?->isActive());
    }

    public function testClearActiveAndGetActiveItem(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Articles', '/articles', ['id' => 'articles']);
        $child = $parent->getSubMenu()->addItem('View', '/articles/view', ['id' => 'view', 'active' => true]);

        $this->assertSame($child, $menu->getActiveItem());

        $menu->clearActive();

        $this->assertNull($menu->getActiveItem());
        $this->assertFalse($child->isActive());
    }

    public function testRejectsDuplicateIds(): void
    {
        $this->expectExceptionMessage('Duplicate menu item id `articles` detected.');

        $menu = Menu::create();
        $menu->addItem('Articles', '/articles', ['id' => 'articles']);
        $menu->addItem('Articles 2', '/articles-2', ['id' => 'articles']);
    }

    public function testResolveWithCallbackResolverIncludesContextDepth(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Articles', '/articles', ['id' => 'articles']);
        $parent->getSubMenu()->addItem('View', '/articles/view', ['id' => 'view']);

        $depths = [];
        $menu->resolve(new CallbackResolver(function ($item, $context) use (&$depths): void {
            $depths[$item->getId()] = $context->getDepth();
        }));

        $this->assertSame([
            'articles' => 1,
            'view' => 2,
        ], $depths);
    }
}
