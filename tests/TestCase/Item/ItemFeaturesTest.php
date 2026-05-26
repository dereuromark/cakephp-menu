<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Item;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Menu;

class ItemFeaturesTest extends TestCase
{
    public function testDisplayChildrenDefaultsTrue(): void
    {
        $item = new Item('Parent', '/parent');

        $this->assertTrue($item->displaysChildren());
        $this->assertTrue($item->toArray()['displayChildren']);
    }

    public function testSetDisplayChildren(): void
    {
        $item = (new Item('Parent', '/parent'))->setDisplayChildren(false);

        $this->assertFalse($item->displaysChildren());
        $this->assertFalse($item->toArray()['displayChildren']);
    }

    public function testLabelAttributes(): void
    {
        $item = (new Item('Home', '/home'))
            ->setLabelAttributes(['title' => 'Go home', 'class' => 'lbl']);

        $this->assertSame(['title' => 'Go home', 'class' => 'lbl'], $item->getLabelAttributes());
        $this->assertSame(['title' => 'Go home', 'class' => 'lbl'], $item->toArray()['labelAttributes']);
    }

    public function testLabelAttributesMerge(): void
    {
        $item = (new Item('Home', '/home'))
            ->setLabelAttributes(['title' => 'A'])
            ->setLabelAttributes(['data-x' => '1'], true);

        $this->assertSame(['data-x' => '1', 'title' => 'A'], $item->getLabelAttributes());
    }

    public function testNewItemOptions(): void
    {
        $menu = Menu::create();
        $item = $menu->addItem('X', '/x', [
            'displayChildren' => false,
            'labelAttributes' => ['title' => 'tip'],
        ]);

        $this->assertFalse($item->displaysChildren());
        $this->assertSame(['title' => 'tip'], $item->getLabelAttributes());
    }

    public function testFromArrayRoundTrip(): void
    {
        $menu = Menu::create();
        $menu->addItem('Parent', '/parent', [
            'key' => 'parent',
            'displayChildren' => false,
            'labelAttributes' => ['title' => 'tip'],
        ]);

        $rebuilt = Menu::fromArray($menu->toArray());
        $item = $rebuilt->getByKey('parent');

        $this->assertNotNull($item);
        $this->assertFalse($item->displaysChildren());
        $this->assertSame(['title' => 'tip'], $item->getLabelAttributes());
    }

    public function testFromArrayRoundTripPreservesExplicitNonFuzzySetting(): void
    {
        $menu = Menu::create();
        $menu->addItem('Parent', '/parent', [
            'key' => 'parent',
            'fuzzy' => false,
        ]);

        $rebuilt = Menu::fromArray($menu->toArray());
        $item = $rebuilt->getByKey('parent');

        $this->assertNotNull($item);
        $this->assertFalse($item->isFuzzyMatch());
    }
}
