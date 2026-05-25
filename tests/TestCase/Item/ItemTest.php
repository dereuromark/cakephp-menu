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

    public function testMatchRouteMetadata(): void
    {
        $item = (new Item('Articles', ['controller' => 'Articles', 'action' => 'index']))
            ->addMatchRoute('/articles')
            ->addMatchRoute(['controller' => 'Articles', 'action' => 'view', 42])
            ->setIgnoreQueryString(true)
            ->setFuzzyMatch();

        $this->assertSame([
            '/articles',
            ['controller' => 'Articles', 'action' => 'view', 42],
        ], $item->getMatchRoutes());
        $this->assertTrue($item->getIgnoreQueryString());
        $this->assertTrue($item->isFuzzyMatch());
    }

    public function testGetKeyDoesNotPersistDerivedSlug(): void
    {
        $item = new Item('Some Label');

        $this->assertSame('some-label', $item->getKey());
        // A label-derived key is not promoted to an explicit key, and is not exported as one.
        $this->assertFalse($item->hasExplicitKey());
        $this->assertNull($item->toArray()['key']);
    }

    public function testGetKeyDoesNotMutateFrozenItem(): void
    {
        $item = (new Item('Some Label'))->freeze();

        $this->assertSame('some-label', $item->getKey());
        $this->assertNull($item->toArray()['key']);
    }

    public function testIconAndBadge(): void
    {
        $item = (new Item('Inbox', '/inbox'))
            ->setIcon('fa fa-inbox')
            ->setBadge(5, 'bg-danger');

        $this->assertSame('fa fa-inbox', $item->getIcon());
        $this->assertSame('5', $item->getBadge());
        $this->assertSame('bg-danger', $item->getBadgeType());

        $array = $item->toArray();
        $this->assertSame('fa fa-inbox', $array['icon']);
        $this->assertSame('5', $array['badge']);
        $this->assertSame('bg-danger', $array['badgeType']);

        $item->setBadge(null);
        $this->assertNull($item->getBadge());
    }
}
