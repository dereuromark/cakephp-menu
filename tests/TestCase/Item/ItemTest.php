<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Item;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Link\Link;

class ItemTest extends TestCase
{
    public function testLabelEscapingFlag(): void
    {
        $escaped = (new Item())->setLabel('First<>');
        $raw = (new Item())->setLabel('Se<b>co</b>nd', false);

        $this->assertSame('First<>', $escaped->getLabel());
        $this->assertTrue($escaped->shouldEscapeLabel());
        $this->assertSame('Se<b>co</b>nd', $raw->getLabel());
        $this->assertFalse($raw->shouldEscapeLabel());
    }

    public function testAddChildCreatesSubMenuAndParentReference(): void
    {
        $parent = new Item('Parent', '/parent');
        $child = new Item('Child', '/child');

        $parent->add($child);

        $this->assertTrue($parent->hasSubMenu());
        $this->assertSame($parent, $child->getParent());
        $this->assertSame($parent->getId(), $child->getParentId());
        $this->assertCount(1, $parent->getSubMenu()->getItems());
    }

    public function testLinkShortcutAndDecorators(): void
    {
        $item = (new Item('Dashboard'))
            ->setLink('/dashboard')
            ->setBefore('<i class="icon"></i>')
            ->setAfter('<span>!</span>');

        $this->assertInstanceOf(Link::class, $item->getLink());
        $this->assertSame('<i class="icon"></i>', $item->getBefore());
        $this->assertSame('<span>!</span>', $item->getAfter());
    }
}
