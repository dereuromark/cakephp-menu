<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Menu;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use LogicException;
use Menu\Menu;

class MenuManipulationTest extends TestCase
{
    /**
     * @param \Menu\MenuInterface $menu
     *
     * @return list<string>
     */
    protected function keys($menu): array
    {
        return array_map(static fn ($item): string => $item->getKey(), $menu->getItems());
    }

    public function testGetByKeyExplicitAndSlug(): void
    {
        $menu = Menu::create();
        $dashboard = $menu->addItem('Dashboard', '/d');
        $profile = $menu->addItem('Profile', '/p', ['key' => 'profile']);

        $this->assertSame($profile, $menu->getByKey('profile'));
        $this->assertSame($dashboard, $menu->getByKey('dashboard'));
        $this->assertNull($menu->getByKey('missing'));
    }

    public function testGetByKeyRecursive(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#');
        $child = $parent->getSubMenu()->addItem('Child', '/c', ['key' => 'child']);

        $this->assertSame($child, $menu->getByKey('child'));
        $this->assertTrue($menu->hasKey('child'));
        $this->assertFalse($menu->hasKey('nope'));
    }

    public function testRemoveByKey(): void
    {
        $menu = Menu::create();
        $menu->addItem('Keep', '/k', ['key' => 'keep']);
        $menu->addItem('Drop', '/d', ['key' => 'drop']);

        $menu->removeByKey('drop');

        $this->assertNull($menu->getByKey('drop'));
        $this->assertSame(['keep'], $this->keys($menu));
    }

    public function testRemoveByKeyRemovesOnlyFirstMatch(): void
    {
        $menu = Menu::create();
        $menu->addItem('Settings', '/a'); // implicit slug key "settings"
        $menu->addItem('Settings', '/b'); // same slug key
        $menu->addItem('Other', '/c');

        $menu->removeByKey('settings');

        $this->assertSame(['settings', 'other'], $this->keys($menu));
        $this->assertSame('/b', $menu->getItems()[0]->getLink()?->getRawUrl());
    }

    public function testRemoveByKeyRecursesIntoSubmenu(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#');
        $parent->getSubMenu()->addItem('Child', '/c', ['key' => 'child']);
        $parent->getSubMenu()->addItem('Keep', '/k', ['key' => 'keep']);

        $menu->removeByKey('child');

        $this->assertNull($menu->getByKey('child'));
        $this->assertNotNull($menu->getByKey('keep'));
    }

    public function testIndexResolutionPrefersIdOverCollidingKey(): void
    {
        $menu = Menu::create();
        // Item labeled "Settings" gets the implicit slug key "settings".
        $menu->addItem('Settings', '/settings');
        // A later item explicitly uses the id "settings".
        $menu->addItem('Other', '/other', ['id' => 'settings']);

        $menu->moveToFirstPosition('settings');

        // The explicit id wins, so "Other" moves first rather than the slug-keyed item.
        $this->assertSame('Other', $menu->getItems()[0]->getLabel());
    }

    public function testInsertBefore(): void
    {
        $menu = Menu::create();
        $menu->addItem('A', '/a', ['key' => 'a']);
        $menu->addItem('C', '/c', ['key' => 'c']);

        $b = $menu->newItem('B', '/b', ['key' => 'b']);
        $menu->insertBefore($b, 'c');

        $this->assertSame(['a', 'b', 'c'], $this->keys($menu));
    }

    public function testInsertAfter(): void
    {
        $menu = Menu::create();
        $menu->addItem('A', '/a', ['key' => 'a']);
        $menu->addItem('C', '/c', ['key' => 'c']);

        $b = $menu->newItem('B', '/b', ['key' => 'b']);
        $menu->insertAfter($b, 'a');

        $this->assertSame(['a', 'b', 'c'], $this->keys($menu));
    }

    public function testInsertBeforeUnknownReferenceThrows(): void
    {
        $menu = Menu::create();
        $menu->addItem('A', '/a', ['key' => 'a']);

        $this->expectException(InvalidArgumentException::class);
        $menu->insertBefore($menu->newItem('B', '/b'), 'missing');
    }

    public function testMoveToPosition(): void
    {
        $menu = Menu::create();
        $menu->addItem('A', '/a', ['key' => 'a']);
        $menu->addItem('B', '/b', ['key' => 'b']);
        $menu->addItem('C', '/c', ['key' => 'c']);

        $menu->moveToPosition('c', 0);

        $this->assertSame(['c', 'a', 'b'], $this->keys($menu));
    }

    public function testMoveToFirstAndLastPosition(): void
    {
        $menu = Menu::create();
        $menu->addItem('A', '/a', ['key' => 'a']);
        $menu->addItem('B', '/b', ['key' => 'b']);
        $menu->addItem('C', '/c', ['key' => 'c']);

        $menu->moveToLastPosition('a');
        $this->assertSame(['b', 'c', 'a'], $this->keys($menu));

        $menu->moveToFirstPosition('c');
        $this->assertSame(['c', 'b', 'a'], $this->keys($menu));
    }

    public function testReorderAppendsUnlisted(): void
    {
        $menu = Menu::create();
        $menu->addItem('A', '/a', ['key' => 'a']);
        $menu->addItem('B', '/b', ['key' => 'b']);
        $menu->addItem('C', '/c', ['key' => 'c']);

        $menu->reorder(['c', 'a']);

        $this->assertSame(['c', 'a', 'b'], $this->keys($menu));
    }

    public function testMergeClonesAndLeavesSourceIntact(): void
    {
        $main = Menu::create();
        $main->addItem('A', '/a', ['key' => 'a']);
        $main->addItem('B', '/b', ['key' => 'b']);

        $other = Menu::create();
        $other->addItem('C', '/c', ['key' => 'c']);
        $other->addItem('D', '/d', ['key' => 'd']);

        $main->merge($other);

        $this->assertSame(['a', 'b', 'c', 'd'], $this->keys($main));
        $this->assertSame(['c', 'd'], $this->keys($other));
    }

    public function testMergeAttributes(): void
    {
        $main = Menu::create(['class' => 'nav']);
        $other = Menu::create(['id' => 'main', 'class' => 'ignored']);

        $main->merge($other, true);

        $this->assertSame(['class' => 'nav', 'id' => 'main'], $main->getAttributes());
    }

    public function testSliceByIndex(): void
    {
        $menu = Menu::create(['class' => 'nav']);
        foreach (['a', 'b', 'c', 'd'] as $key) {
            $menu->addItem(strtoupper($key), '/' . $key, ['key' => $key]);
        }

        $slice = $menu->slice(1, 2);

        $this->assertNotSame($menu, $slice);
        $this->assertSame(['b', 'c'], $this->keys($slice));
        $this->assertSame(['class' => 'nav'], $slice->getAttributes());
        // Original untouched.
        $this->assertSame(['a', 'b', 'c', 'd'], $this->keys($menu));
    }

    public function testSliceByKeyBoundaries(): void
    {
        $menu = Menu::create();
        foreach (['a', 'b', 'c', 'd'] as $key) {
            $menu->addItem(strtoupper($key), '/' . $key, ['key' => $key]);
        }

        $slice = $menu->slice('b', 'd');

        $this->assertSame(['b', 'c'], $this->keys($slice));
    }

    public function testSplit(): void
    {
        $menu = Menu::create();
        foreach (['a', 'b', 'c', 'd'] as $key) {
            $menu->addItem(strtoupper($key), '/' . $key, ['key' => $key]);
        }

        $parts = $menu->split(2);

        $this->assertSame(['a', 'b'], $this->keys($parts['primary']));
        $this->assertSame(['c', 'd'], $this->keys($parts['secondary']));
    }

    public function testFrozenMenuRejectsManipulation(): void
    {
        $menu = Menu::create();
        $menu->addItem('A', '/a', ['key' => 'a']);
        $menu->freeze();

        $this->expectException(LogicException::class);
        $menu->moveToFirstPosition('a');
    }
}
