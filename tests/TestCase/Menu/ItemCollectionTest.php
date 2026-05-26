<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Menu;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\ItemCollection;

class ItemCollectionTest extends TestCase
{
    public function testFinders(): void
    {
        $parent = (new Item('Parent'))->setId('parent')->setKey('parent');
        $child = (new Item('Child'))->setId('child')->setKey('child')->setParent($parent);

        $collection = new ItemCollection([$parent, $child]);

        $this->assertCount(2, $collection);
        $this->assertSame($child, $collection->findById('child'));
        $this->assertSame($child, $collection->findByKey('child'));
        $this->assertSame([$child], $collection->findByParent($parent));
    }

    public function testFindByKeyIgnoresImplicitSlugKeys(): void
    {
        $explicit = (new Item('Profile'))->setId('profile-explicit')->setKey('profile');
        $implicit = (new Item('Settings'))->setId('settings-implicit');

        $collection = new ItemCollection([$explicit, $implicit]);

        $this->assertSame($explicit, $collection->findByKey('profile'));
        $this->assertNull($collection->findByKey('settings'));
    }
}
