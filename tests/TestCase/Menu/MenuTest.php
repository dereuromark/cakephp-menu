<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Menu;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use LogicException;
use Menu\Item\Item;
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

    public function testFromArrayToArrayRoundTrip(): void
    {
        $menu = Menu::fromArray([
            'attributes' => ['class' => 'nav'],
            'data' => ['area' => 'main'],
            'items' => [
                [
                    'id' => 'articles',
                    'label' => 'Articles',
                    'link' => '/articles',
                    'data' => ['weight' => 10],
                    'submenu' => [
                        'items' => [
                            [
                                'id' => 'view',
                                'label' => 'View',
                                'link' => '/articles/view',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'account',
                    'label' => 'Account',
                    'link' => '#',
                    'submenu' => [
                        'attributes' => ['class' => 'dropdown-menu'],
                        'data' => ['kind' => 'account'],
                        'items' => [],
                    ],
                ],
            ],
        ]);

        $export = $menu->toArray();

        $this->assertSame('nav', $export['attributes']['class']);
        $this->assertSame('main', $export['data']['area']);
        $this->assertSame('articles', $export['items'][0]['id']);
        $this->assertSame('view', $export['items'][0]['submenu']['items'][0]['id']);
        $this->assertSame('dropdown-menu', $export['items'][1]['submenu']['attributes']['class']);
        $this->assertSame('account', $export['items'][1]['submenu']['data']['kind']);
    }

    public function testFreezePreventsStructuralMutation(): void
    {
        $menu = Menu::create();
        $menu->addItem('Articles', '/articles');
        $menu->freeze();

        $this->expectException(LogicException::class);
        $menu->addItem('Users', '/users');
    }

    public function testRejectsDuplicateExplicitKeys(): void
    {
        $this->expectExceptionMessage('Duplicate explicit menu item key `content` detected.');

        $menu = Menu::create();
        $menu->addItem('Articles', '/articles', ['key' => 'content']);
        $menu->addItem('Pages', '/pages', ['key' => 'content']);
    }

    public function testSetItemsReparentsMovedSubmenuItems(): void
    {
        $menu = Menu::create();
        $firstParent = $menu->addItem('First', '/first', ['id' => 'first']);
        $secondParent = $menu->addItem('Second', '/second', ['id' => 'second']);
        $child = (new Item('Child', '/child'))->setId('child');

        $firstParent->getSubMenu()->setItems([$child]);
        $this->assertSame($firstParent, $child->getParent());

        $secondParent->getSubMenu()->setItems([$child]);

        $this->assertSame($secondParent, $child->getParent());
        $this->assertSame($child, $secondParent->getSubMenu()->get('child'));
    }
}
