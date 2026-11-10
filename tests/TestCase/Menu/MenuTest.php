<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Menu;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Item\ItemInterface;
use CakeMenu\ItemCollection;
use CakeMenu\Link\LinkInterface;
use CakeMenu\Menu;
use CakeMenu\MenuInterface;
use CakeMenu\Renderer\StringTemplateRenderer;
use CakeMenu\Resolver\CallbackResolver;
use CakeMenu\Resolver\UrlArrayResolver;
use CakeMenu\SortDirection;
use InvalidArgumentException;
use LogicException;
use ReflectionMethod;
use stdClass;

class MenuTest extends TestCase
{
    public function testRejectedAddDoesNotAttachOrRemoveExistingItem(): void
    {
        $menu = Menu::create();
        $owner = $menu->addItem('Owner', options: ['id' => 'owner']);
        $original = $owner->getSubMenu()->addItem('Original', options: ['id' => 'shared']);
        $rejected = $menu->newItem('Rejected', options: ['id' => 'shared']);

        try {
            $owner->getSubMenu()->add($rejected);
            $this->fail('Expected duplicate id.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('shared', $exception->getMessage());
        }

        $this->assertNull($rejected->getOwnerMenu());
        $this->assertNull($rejected->getParent());
        $rejected->detach();
        $this->assertSame($original, $menu->get('shared'));
    }

    public function testIdsAreUniqueAcrossSubmenusAndReusableAfterRemoval(): void
    {
        $menu = Menu::create();
        $first = $menu->addItem('First');
        $second = $menu->addItem('Second');
        $first->getSubMenu()->addItem('Child', options: ['id' => 'shared']);
        try {
            $second->getSubMenu()->addItem('Duplicate', options: ['id' => 'shared']);
            $this->fail('Expected duplicate id.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('shared', $exception->getMessage());
        }
        $first->getSubMenu()->remove('shared');
        $second->getSubMenu()->addItem('Replacement', options: ['id' => 'shared']);
        $this->assertSame('Replacement', $menu->get('shared')?->getLabel());
    }

    public function testIndexTracksIdentifierChangesAndDetachedSubtrees(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', options: ['id' => 'parent']);
        $child = $parent->getSubMenu()->addItem('Child', options: ['id' => 'old', 'key' => 'old-key']);
        $child->setId('new')->setKey('new-key');
        $menu->addItem('Old', options: ['id' => 'old', 'key' => 'old-key']);

        try {
            $child->setId('parent');
            $this->fail('Expected duplicate id.');
        } catch (InvalidArgumentException) {
            $this->assertSame('new', $child->getId());
        }

        $parent->detach();
        $menu->addItem('New', options: ['id' => 'new', 'key' => 'new-key']);
        try {
            $parent->getSubMenu()->addItem('Duplicate', options: ['id' => 'new']);
            $this->fail('Expected duplicate id in detached submenu.');
        } catch (InvalidArgumentException) {
            $this->assertCount(1, $parent->getSubMenu()->getItems());
        }
        Menu::create()->add($parent);
        $this->assertSame($child, $parent->getSubMenu()->get('new'));
        $this->assertSame($parent, $child->getParent());
    }

    public function testIndexTracksSetItemsFilterAndSplit(): void
    {
        $menu = Menu::create();
        $menu->addItem('First', options: ['id' => 'first']);
        $menu->addItem('Second', options: ['id' => 'second']);
        $menu->filter(static fn (ItemInterface $item): bool => $item->getId() !== 'first');
        $menu->addItem('Replacement', options: ['id' => 'first']);
        $parts = $menu->split(1);
        $parts['primary']->addItem('Other', options: ['id' => 'first']);
        $menu->setItems([$menu->get('first')]);
        $menu->addItem('Second again', options: ['id' => 'second']);
        $this->assertCount(2, $menu->getItems());
    }

    public function testBuildingFourThousandItemsIsFast(): void
    {
        $menu = Menu::create();
        $start = microtime(true);
        for ($i = 0; $i < 4000; $i++) {
            $menu->addItem('Item ' . $i, options: ['id' => 'item-' . $i]);
        }
        $this->assertLessThan(1.5, microtime(true) - $start);
        $this->assertCount(4000, $menu->getItems());
    }

    public function testRoundTripPreservesEveryItemOptionAndAbsentSubmenu(): void
    {
        $menu = Menu::create(['class' => 'nav']);
        $menu->setData('section', 'main');
        $menu->addDivider(['id' => 'divider']);
        $menu->addRaw('<b>Raw</b>', ['id' => 'raw']);
        $menu->addHeader('Section', ['id' => 'header']);
        $item = $menu->addItem('Docs', 'https://example.com', [
            'id' => 'docs',
            'key' => 'docs',
            'escape' => false,
            'before' => '<b>',
            'after' => '</b>',
            'icon' => 'icon',
            'badge' => '2',
            'badgeType' => 'count',
            'attributes' => ['class' => 'entry'],
            'data' => ['section' => 'docs'],
            'visible' => false,
            'active' => true,
            'expanded' => true,
            'matchRoutes' => ['/docs'],
            'ignoreQueryString' => true,
            'fuzzy' => false,
            'displayChildren' => false,
            'labelAttributes' => ['class' => 'label'],
            'linkAttributes' => ['target' => '_blank'],
            'external' => true,
            'submenuAttributes' => ['class' => 'children'],
        ]);
        $item->getSubMenu()->addItem('Child', '/child', ['id' => 'child']);

        $export = $menu->toArray();
        $rebuilt = Menu::fromArray($export);
        $this->assertSame($export, $rebuilt->toArray());
        $this->assertNull($rebuilt->get('divider')?->getLabel());
        $this->assertNull($rebuilt->get('raw')?->toArray()['submenu']);
    }

    public function testNewItemOptionsRenderOnExternalLink(): void
    {
        $menu = Menu::create();
        $item = $menu->addItem('Docs', 'https://book.cakephp.org', [
            'linkAttributes' => ['target' => '_blank', 'rel' => 'noopener'],
            'external' => true,
            'expanded' => true,
        ]);
        $html = (new StringTemplateRenderer())->render($menu);

        $this->assertTrue($item->getLink()?->isExternal());
        $this->assertTrue($item->isExpanded());
        $this->assertStringContainsString('<a target="_blank" rel="noopener" href="https://book.cakephp.org">Docs</a>', $html);
        $this->assertStringNotContainsString('<li target=', $html);
    }

    public function testGetActiveItemPrefersDeepestDescendantInFirstMatchingBranch(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent');
        $child = $parent->getSubMenu()->addItem('Child');
        $grandchild = $child->getSubMenu()->addItem('Grandchild');
        $later = $menu->addItem('Later');
        foreach ([$parent, $child, $grandchild, $later] as $item) {
            $item->setRuntimeActive(true);
        }

        $this->assertSame($grandchild, $menu->getActiveItem());
        $grandchild->setRuntimeActive(false);
        $this->assertSame($child, $menu->getActiveItem());
        $child->setRuntimeActive(false);
        $this->assertSame($parent, $menu->getActiveItem());
    }

    public function testFromArrayExternalLinkWithoutAttributes(): void
    {
        $menu = Menu::fromArray([
            'items' => [
                ['label' => 'External', 'link' => 'https://example.com', 'external' => true],
            ],
        ]);
        $link = $menu->getItems()[0]->getLink();

        $this->assertNotNull($link);
        $this->assertTrue($link->isExternal());
        $this->assertSame('https://example.com', $link->getRawUrl());
        $this->assertSame([], $link->getAttributes());
    }

    public function testSerializationDoesNotPersistResolvedState(): void
    {
        $menu = Menu::create();
        $item = $menu->addItem('Articles', '/articles');
        $menu->resolve(new CallbackResolver(static function ($item, $context): void {
            $item->setRuntimeActive(true);
            $item->setRuntimeVisible(false);
            $item->setRuntimeExpanded(true);
        }));

        $this->assertTrue($item->isActive());
        $rebuilt = Menu::fromArray($menu->toArray());
        $rebuiltItem = $rebuilt->getItems()[0];
        $this->assertFalse($rebuiltItem->isActive());
        $this->assertTrue($rebuiltItem->isVisible());
        $this->assertFalse($rebuiltItem->isExpanded());
    }

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

    public function testRemoveThrowsWhenMissing(): void
    {
        $menu = Menu::create();
        $menu->addItem('Dashboard', '/dashboard', ['id' => 'dashboard']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown menu item `missing`.');
        $menu->remove('missing');
    }

    public function testFilterAndSort(): void
    {
        $menu = Menu::create();
        $menu->addItem('Users', '/users', ['id' => 'users'])->setData('weight', 20);
        $menu->addItem('Dashboard', '/dashboard', ['id' => 'dashboard'])->setData('weight', 10);
        $menu->addItem('Hidden', '/hidden', ['id' => 'hidden'])->setVisible(false);

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

    public function testClearActiveDoesNotDestroyResetStateBaseline(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Articles', '/articles', ['id' => 'articles']);
        $child = $parent->getSubMenu()->addItem('View', '/articles/view', ['id' => 'view', 'active' => true]);

        $menu->clearActive();
        $menu->resetState();

        $this->assertSame($child, $menu->getActiveItem());
        $this->assertTrue($child->isActive());
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

    public function testSetItemsRejectsMovedSubmenuItems(): void
    {
        $menu = Menu::create();
        $firstParent = $menu->addItem('First', '/first', ['id' => 'first']);
        $secondParent = $menu->addItem('Second', '/second', ['id' => 'second']);
        $child = (new Item('Child', '/child'))->setId('child');

        $firstParent->getSubMenu()->setItems([$child]);
        $this->assertSame($firstParent, $child->getParent());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already belongs to a menu tree');
        $secondParent->getSubMenu()->setItems([$child]);
    }

    public function testSetItemsRejectsItemsMovedToRootMenu(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#', ['id' => 'parent']);
        $child = (new Item('Child', '/child'))->setId('child');

        $parent->getSubMenu()->setItems([$child]);
        $this->assertSame($parent, $child->getParent());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already belongs to a menu tree');
        $menu->setItems([$child]);
    }

    public function testAddRejectsItemMovedBetweenSubmenus(): void
    {
        $menu = Menu::create();
        $firstParent = $menu->addItem('First', '#', ['id' => 'first']);
        $secondParent = $menu->addItem('Second', '#', ['id' => 'second']);
        $child = (new Item('Child', '/child'))->setId('child');

        $firstParent->getSubMenu()->add($child);
        $this->assertSame($firstParent, $child->getParent());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already belongs to a menu tree');
        $secondParent->getSubMenu()->add($child);
    }

    public function testAddRejectsRootItemMovedToAnotherRootMenu(): void
    {
        $firstMenu = Menu::create();
        $child = $firstMenu->addItem('Child', '/child', ['id' => 'child']);

        $secondMenu = Menu::create();
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already belongs to a menu tree');
        $secondMenu->add($child);
    }

    public function testAddRejectsRootItemMovedIntoSubmenu(): void
    {
        $menu = Menu::create();
        $rootItem = $menu->addItem('Root', '/root', ['id' => 'root']);
        $parent = $menu->addItem('Parent', '#', ['id' => 'parent']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('already belongs to a menu tree');
        $parent->add($rootItem);
    }

    public function testItemCanBeReattachedAfterExplicitRemoval(): void
    {
        $firstMenu = Menu::create();
        $child = $firstMenu->addItem('Child', '/child', ['id' => 'child']);

        $firstMenu->remove('child');

        $secondMenu = Menu::create();
        $secondMenu->add($child);

        $this->assertSame($child, $secondMenu->get('child'));
        $this->assertNull($child->getParent());
        $this->assertSame($secondMenu, $child->getOwnerMenu());
    }

    public function testItemCanBeReattachedAfterDetach(): void
    {
        $firstMenu = Menu::create();
        $child = $firstMenu->addItem('Child', '/child', ['id' => 'child']);

        $secondMenu = Menu::create();
        try {
            $secondMenu->add($child);
            $this->fail('Expected LogicException was not thrown.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('already belongs to a menu tree', $exception->getMessage());
        }

        $child->detach();
        $secondMenu->add($child);

        $this->assertNull($firstMenu->get('child'));
        $this->assertSame($child, $secondMenu->get('child'));
        $this->assertNull($child->getParent());
        $this->assertSame($secondMenu, $child->getOwnerMenu());
    }

    public function testAddRejectsCycleAfterDetach(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#', ['id' => 'parent']);
        $child = $parent->getSubMenu()->addItem('Child', '/child', ['id' => 'child']);

        $menu->remove('parent');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot attach an item beneath one of its own descendants.');
        $child->add($parent);
    }

    public function testRemoveDetachesRemovedItem(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#', ['id' => 'parent']);
        $child = $parent->getSubMenu()->addItem('Child', '/child', ['id' => 'child']);

        $parent->getSubMenu()->remove('child');

        $this->assertNull($child->getParent());
        $this->assertNull($child->getOwnerMenu());
        $this->assertNull($menu->get('child'));
    }

    public function testRemoveDetachesRemovedSubtree(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#', ['id' => 'parent']);
        $child = $parent->getSubMenu()->addItem('Child', '/child', ['id' => 'child']);

        $menu->remove('parent');

        $this->assertNull($parent->getParent());
        $this->assertNull($parent->getOwnerMenu());
        $this->assertNull($child->getParent());
        $this->assertNull($child->getOwnerMenu());
        $this->assertNull($menu->get('parent'));
        $this->assertNull($menu->get('child'));
    }

    public function testFilterDetachesRemovedRootItem(): void
    {
        $menu = Menu::create();
        $child = $menu->addItem('Child', '/child', ['id' => 'child']);

        $menu->filter(static fn (): bool => false);

        $this->assertNull($child->getParent());
        $this->assertNull($child->getOwnerMenu());
        $this->assertNull($menu->get('child'));
    }

    public function testFilterDetachesRemovedSubtree(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#', ['id' => 'parent']);
        $child = $parent->getSubMenu()->addItem('Child', '/child', ['id' => 'child']);

        $menu->filter(static fn ($item): bool => $item->getId() !== 'parent');

        $this->assertNull($parent->getParent());
        $this->assertNull($parent->getOwnerMenu());
        $this->assertNull($child->getParent());
        $this->assertNull($child->getOwnerMenu());
        $this->assertNull($menu->get('parent'));
        $this->assertNull($menu->get('child'));
    }

    public function testAddItemsRejectsAttachedBatchWithoutMutatingMenu(): void
    {
        $targetMenu = Menu::create();
        $targetMenu->addItem('Existing', '/existing', ['id' => 'existing']);

        $sourceMenu = Menu::create();
        $attached = $sourceMenu->addItem('Attached', '/attached', ['id' => 'attached']);

        try {
            $targetMenu->addItems([
                $targetMenu->newItem('New', '/new', ['id' => 'new']),
                $attached,
            ]);
            $this->fail('Expected LogicException was not thrown.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('already belongs to a menu tree', $exception->getMessage());
        }

        $this->assertSame(['existing'], array_map(static fn (ItemInterface $item): string => $item->getId(), $targetMenu->getItems()));
        $this->assertSame($sourceMenu, $attached->getOwnerMenu());
    }

    public function testCollectFlattensTheTree(): void
    {
        $menu = Menu::create();
        $account = $menu->addItem('Account', '#', ['key' => 'account']);
        $account->getSubMenu()->addItem('Profile', '/profile', ['key' => 'profile']);
        $account->getSubMenu()->addItem('Logout', '/logout', ['key' => 'logout']);
        $menu->addItem('Home', '/', ['key' => 'home']);

        $collection = $menu->collect();

        $this->assertInstanceOf(ItemCollection::class, $collection);
        $this->assertCount(4, $collection);
        $this->assertSame('profile', $collection->findByKey('profile')?->getKey());
        $this->assertCount(2, $collection->findByParent($account));
    }

    public function testFindReturnsMatchingItemsRecursively(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('X Parent', '/parent', ['key' => 'x-parent']);
        $parent->getSubMenu()->addItem('Child', '/child', ['key' => 'child']);
        $parent->getSubMenu()->addItem('X Child', '/x-child', ['key' => 'x-child']);
        $menu->addItem('X Root', '/x-root', ['key' => 'x-root']);
        $beforeCount = count($menu->getItems());

        $collection = $menu->find(
            static fn ($item): bool => $item->getLabel() !== null && str_contains($item->getLabel(), 'X'),
        );

        $this->assertSame(['x-parent', 'x-child', 'x-root'], array_map(
            static fn ($item): string => $item->getKey(),
            $collection->all(),
        ));
        $this->assertSame($beforeCount, count($menu->getItems()));
    }

    public function testFindReturnsEmptyCollectionWhenNoMatch(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home');

        $collection = $menu->find(static fn ($item): bool => $item->getLabel() === 'Missing');

        $this->assertCount(0, $collection);
    }

    public function testAddItemsAddsInOrder(): void
    {
        $menu = Menu::create();
        $first = $menu->newItem('First', '/first', ['key' => 'first']);
        $second = $menu->newItem('Second', '/second', ['key' => 'second']);
        $third = $menu->newItem('Third', '/third', ['key' => 'third']);

        $menu->addItems([$first, $second, $third]);

        $this->assertSame([$first, $second, $third], $menu->getItems());
    }

    public function testAddItemsRejectsNonItem(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $menu = Menu::create();
        $menu->addItems([
            $menu->newItem('First', '/first'),
            'not-an-item',
        ]);
    }

    public function testAddItemsIsAtomicOnInvalidBatch(): void
    {
        $menu = Menu::create();
        $valid = $menu->newItem('First', '/first');

        try {
            $menu->addItems([$valid, 'not-an-item']);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        // The valid entry was not partially applied before the throw.
        $this->assertSame([], $menu->getItems());
    }

    public function testAddItemsRollbackLeavesItemParentsUntouched(): void
    {
        // Add to a submenu, where add() reparents items to the owner — verify a mid-batch failure
        // doesn't leave the earlier item with a rewritten parent pointer.
        $menu = Menu::create();
        $owner = $menu->addItem('Owner', '#');
        $submenu = $owner->getSubMenu();
        $submenu->addItem('Existing', '/existing', ['id' => 'shared']);

        $valid = $submenu->newItem('Valid', '/valid');
        $duplicate = $submenu->newItem('Dup', '/dup')->setId('shared');

        try {
            $submenu->addItems([$valid, $duplicate]);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        // The valid item's parent was never rewritten because the batch was rejected pre-mutation.
        $this->assertNull($valid->getParent());
        $this->assertCount(1, $submenu->getItems());
    }

    public function testAddItemsRejectsFrozenItemBatchIntoSubmenu(): void
    {
        $menu = Menu::create();
        $owner = $menu->addItem('Owner', '#');
        $submenu = $owner->getSubMenu();

        $frozen = $submenu->newItem('Frozen', '/frozen');
        $frozen->freeze();

        try {
            $submenu->addItems([$frozen]);
            $this->fail('Expected LogicException');
        } catch (LogicException) {
            // Expected — reparenting a frozen item would mutate it.
        }

        $this->assertSame([], $submenu->getItems(), 'No item was added on rejection.');
    }

    public function testAddItemsIsAtomicOnDuplicateId(): void
    {
        $menu = Menu::create();
        $menu->addItem('Existing', '/existing', ['id' => 'shared']);

        $valid = $menu->newItem('First', '/first');
        $duplicate = $menu->newItem('Second', '/second')->setId('shared');

        try {
            $menu->addItems([$valid, $duplicate]);
            $this->fail('Expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
            // Expected: add() rejects the duplicate id.
        }

        // Roll-back: only the pre-existing item remains; the first batch entry did not stick.
        $this->assertCount(1, $menu->getItems());
        $this->assertSame('shared', $menu->getItems()[0]->getId());
    }

    public function testFromFlatBuildsTreeFromUnorderedRows(): void
    {
        $rows = [
            ['id' => 3, 'parent' => 1, 'title' => 'Profile', 'url' => '/profile'],
            ['id' => 1, 'parent' => null, 'title' => 'Account', 'url' => '#'],
            ['id' => 2, 'parent' => null, 'title' => 'Home', 'url' => '/'],
            ['id' => 4, 'parent' => 1, 'title' => 'Logout', 'url' => '/logout'],
        ];

        $menu = Menu::fromFlat($rows, fn (array $row): array => [
            'key' => (string)$row['id'],
            'parent' => $row['parent'] !== null ? (string)$row['parent'] : null,
            'label' => $row['title'],
            'link' => $row['url'],
        ]);

        $this->assertCount(2, $menu->getItems());
        $account = $menu->getItems()[0];
        $this->assertSame('Account', $account->getLabel());
        $this->assertSame('Home', $menu->getItems()[1]->getLabel());
        $this->assertSame(['Profile', 'Logout'], [
            $account->getSubMenu()->getItems()[0]->getLabel(),
            $account->getSubMenu()->getItems()[1]->getLabel(),
        ]);
    }

    public function testFromFlatRejectsDuplicateKeysBeforeCreatingItems(): void
    {
        $itemClass = new class extends Item {
            public static int $created = 0;

            public function __construct(?string $label = null, LinkInterface|array|string|null $link = null)
            {
                self::$created++;
                parent::__construct($label, $link);
            }
        };
        $itemClass::$created = 0;

        try {
            Menu::fromFlat([
                ['key' => 'same', 'parent' => null],
                ['key' => 'other', 'parent' => 'same'],
                ['key' => 'same', 'parent' => 'other'],
            ], static fn (array $row): array => $row, $itemClass::class);
            $this->fail('Expected duplicate key.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('same', $exception->getMessage());
        }
        $this->assertSame(0, $itemClass::$created);
    }

    public function testImportFactoriesAcceptCustomItemClass(): void
    {
        $custom = new class extends Item {
        };
        $fromArray = Menu::fromArray([

            'items' => [
                ['label' => 'Parent', 'submenu' => ['items' => [['label' => 'Child']]]],
            ],
        ], $custom::class);
        $fromFlat = Menu::fromFlat([
            ['key' => 'parent', 'parent' => null],
            ['key' => 'child', 'parent' => 'parent'],
        ], static fn (array $row): array => $row, $custom::class);

        foreach ([$fromArray, $fromFlat] as $menu) {
            $this->assertInstanceOf($custom::class, $menu->getItems()[0]);
            $this->assertInstanceOf($custom::class, $menu->getItems()[0]->getSubMenu()->getItems()[0]);
        }
    }

    public function testFromFlatTreatsUnknownParentAsRoot(): void
    {
        $menu = Menu::fromFlat(
            [['id' => 5, 'parent' => 99, 'title' => 'Lost', 'url' => '/lost']],
            fn (array $row): array => [
                'key' => (string)$row['id'],
                'parent' => (string)$row['parent'],
                'label' => $row['title'],
                'link' => $row['url'],
            ],
        );

        $this->assertCount(1, $menu->getItems());
        $this->assertSame('Lost', $menu->getItems()[0]->getLabel());
    }

    public function testFromFlatBreaksCyclicParentReferences(): void
    {
        $rows = [
            ['id' => 1, 'parent' => 2, 'title' => 'A', 'url' => '/a'],
            ['id' => 2, 'parent' => 1, 'title' => 'B', 'url' => '/b'],
        ];

        $menu = Menu::fromFlat($rows, fn (array $row): array => [
            'key' => (string)$row['id'],
            'parent' => (string)$row['parent'],
            'label' => $row['title'],
            'link' => $row['url'],
        ]);

        // The cycle is broken, so tree traversal terminates and keeps both items.
        $this->assertCount(2, $menu->collect());
    }

    public function testAddHeader(): void
    {
        $menu = Menu::create();
        $menu->addHeader('Section');

        $this->assertTrue($menu->getItems()[0]->toArray()['header']);
        $this->assertSame('Section', $menu->getItems()[0]->getLabel());
    }

    public function testCustomItemClassPropagates(): void
    {
        $custom = new class extends Item {
        };
        $menu = Menu::create()->setItemClass($custom::class);
        $parent = $menu->addItem('Parent', options: ['submenuAttributes' => ['class' => 'nested']]);
        $child = $parent->getSubMenu()->addItem('Child');
        $grandchild = $child->getSubMenu()->addItem('Grandchild');
        $this->assertInstanceOf($custom::class, $parent);
        $this->assertInstanceOf($custom::class, $child);
        $this->assertInstanceOf($custom::class, $grandchild);
        $this->assertSame($custom::class, $child->getSubMenu()->getItemClass());
        $this->assertCount(3, $menu->collect());
    }

    public function testSortDirections(): void
    {
        $menu = Menu::create();
        $menu->addItem('A')->setData('weight', 1);
        $menu->addItem('B')->setData('weight', 2);
        $menu->sortBy('weight', SortDirection::Desc);
        $this->assertSame('B', $menu->getItems()[0]->getLabel());
        $menu->sortBy(static fn ($item) => $item->getData('weight'), SortDirection::Asc);
        $this->assertSame('A', $menu->getItems()[0]->getLabel());
    }

    public function testInvalidItemClassThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $menu = Menu::create();
        $method = new ReflectionMethod($menu, 'setItemClass');
        $method->invoke($menu, stdClass::class);
    }

    public function testFreezeDoesNotFreezeLinkSharedWithAnotherItem(): void
    {
        $menu = new Menu();
        $first = $menu->addItem('First', '/shared');
        $second = $menu->addItem('Second', '/other');
        $shared = $first->getLink();
        $this->assertNotNull($shared);
        $second->setLink($shared);

        $first->freeze();
        $shared->setUrl('/changed');

        $this->assertSame('/changed', $second->getLink()?->getRawUrl());
        $this->assertSame('/shared', $first->getLink()?->getRawUrl());
    }

    public function testKeyLookupDescendsIntoCustomSubMenu(): void
    {
        $target = (new Item('Target'))->setKey('explicit-target');
        $custom = $this->createStub(MenuInterface::class);
        $custom->method('getByKey')->willReturnCallback(static fn (string $key) => $key === 'explicit-target' ? $target : null);
        $custom->method('getItems')->willReturn([$target]);

        $menu = new Menu();
        $menu->addItem('Parent')->setSubMenu($custom);

        $this->assertSame($target, $menu->getByKey('explicit-target'));
    }
}
