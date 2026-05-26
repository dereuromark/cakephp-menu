<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Menu;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Menu;

class MenuCloneTest extends TestCase
{
    public function testCloneIsDeep(): void
    {
        $menu = Menu::create();
        $item = $menu->addItem('Home', '/home', ['key' => 'home']);
        $item->getSubMenu()->addItem('Child', '/child', ['key' => 'child']);

        $clone = clone $menu;
        $clone->getItems()[0]->setLabel('Changed');
        $clone->getItems()[0]->getSubMenu()->getItems()[0]->setLabel('Changed Child');

        $this->assertSame('Home', $menu->getItems()[0]->getLabel());
        $this->assertSame('Child', $menu->getItems()[0]->getSubMenu()->getItems()[0]->getLabel());
        $this->assertSame('Changed', $clone->getItems()[0]->getLabel());
        $this->assertSame('Changed Child', $clone->getItems()[0]->getSubMenu()->getItems()[0]->getLabel());
    }

    public function testCloneOfNestedMenuReparents(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '/parent', ['key' => 'parent']);
        $parent->getSubMenu()->addItem('First', '/first', ['key' => 'first']);
        $parent->getSubMenu()->addItem('Second', '/second', ['key' => 'second']);

        $clone = clone $menu;
        $clonedParent = $clone->getItems()[0];
        $clonedChildren = $clonedParent->getSubMenu()->getItems();

        $this->assertSame($clonedParent, $clonedChildren[0]->getParent());
        $this->assertSame($clonedParent, $clonedChildren[1]->getParent());
        $this->assertNotSame($parent, $clonedChildren[0]->getParent());
    }

    public function testClonePreservesCustomItemSubclass(): void
    {
        $menu = Menu::create();
        $item = new class ('Custom', '/custom') extends Item {
        };
        $menu->add($item);

        $clone = clone $menu;

        $this->assertInstanceOf($item::class, $clone->getItems()[0]);
    }

    public function testSliceAndSplitPreserveCustomItemSubclass(): void
    {
        $menu = Menu::create();
        $customItem = new class ('Custom', '/custom') extends Item {
        };
        $menu->addItem('Home', '/home', ['key' => 'home']);
        $menu->add($customItem->setKey('custom'));
        $menu->addItem('Tail', '/tail', ['key' => 'tail']);

        $slice = $menu->slice(1, 1);
        $parts = $menu->split(1);

        $this->assertInstanceOf($customItem::class, $slice->getItems()[0]);
        $this->assertInstanceOf($customItem::class, $parts['secondary']->getItems()[0]);
    }

    public function testCloneOfFrozenMenuProducesMutableWorkingCopy(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '/parent', ['key' => 'parent']);
        $parent->getSubMenu()->addItem('Child', '/child', ['key' => 'child']);
        $menu->freeze();

        $clone = clone $menu;

        // A clone is a mutable working copy: the menu, its items, and submenus are all editable
        // (so slice()/split()/merge() consumers can derive editable copies from frozen sources).
        $this->assertFalse($clone->isFrozen());
        $this->assertCount(1, $clone->getItems());
        $clonedParent = $clone->getItems()[0];
        $this->assertFalse($clonedParent->isFrozen());
        $clonedChild = $clonedParent->getSubMenu()->getItems()[0];
        $this->assertFalse($clonedChild->isFrozen());

        // Editing the clone works; the original stays frozen.
        $clonedChild->setLabel('Edited');
        $this->assertSame('Edited', $clonedChild->getLabel());
        $this->assertTrue($menu->isFrozen());
    }

    public function testCloningSubMenuStandaloneClearsOwner(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#');
        $parent->getSubMenu()->addItem('Child', '/c');

        $clonedSub = clone $parent->getSubMenu();
        // Cloned submenu's existing items are detached from the source-tree owner.
        $clonedChild = $clonedSub->getItems()[0];
        $this->assertNull($clonedChild->getParent());

        // Adding to the standalone clone does not reach into the source tree.
        $newItem = $clonedSub->newItem('New', '/n');
        $clonedSub->add($newItem);
        $this->assertNull($newItem->getParent());
        // Source tree untouched.
        $this->assertCount(1, $parent->getSubMenu()->getItems());
    }

    public function testCloneOfNestedItemDetachesFromSourceParent(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#');
        $child = $parent->getSubMenu()->addItem('Child', '/c');

        $this->assertSame($parent, $child->getParent());

        $cloned = clone $child;

        $this->assertNull(
            $cloned->getParent(),
            'Cloning a nested item must detach it from the source-tree parent; the clone is a standalone item until reattached.',
        );
        // Source tree is untouched.
        $this->assertSame($parent, $child->getParent());
    }
}
