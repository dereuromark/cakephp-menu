<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Item;

use Cake\TestSuite\TestCase;
use LogicException;
use Menu\Item\Item;
use Menu\Link\Link;
use Menu\Menu;
use Menu\MenuInterface;
use RuntimeException;

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
        $this->assertSame($parent->getSubMenu(), $child->getOwnerMenu());
        $this->assertCount(1, $parent->getSubMenu()->getItems());
    }

    public function testParentAndOwnerMenuCanBeDetached(): void
    {
        $item = new Item('Child', '/child');
        $parent = new Item('Parent', '/parent');
        $ownerMenu = Menu::create();

        $item->setParent($parent);
        $item->setOwnerMenu($ownerMenu);

        $item->setParent(null);
        $item->setOwnerMenu(null);

        $this->assertNull($item->getParent());
        $this->assertFalse($item->hasParent());
        $this->assertNull($item->getOwnerMenu());
        $this->assertFalse($item->hasOwnerMenu());
    }

    public function testDetachRemovesRootItemFromOwnerMenu(): void
    {
        $menu = Menu::create();
        $item = $menu->addItem('Child', '/child', ['id' => 'child']);

        $item->detach();

        $this->assertNull($menu->get('child'));
        $this->assertNull($item->getParent());
        $this->assertNull($item->getOwnerMenu());
    }

    public function testDetachRemovesChildFromParentSubMenu(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '/parent', ['id' => 'parent']);
        $child = $parent->getSubMenu()->addItem('Child', '/child', ['id' => 'child']);

        $child->detach();

        $this->assertNull($parent->getSubMenu()->get('child'));
        $this->assertNull($menu->get('child'));
        $this->assertNull($child->getParent());
        $this->assertNull($child->getOwnerMenu());
    }

    public function testDetachIsNoOpForAlreadyDetachedItem(): void
    {
        $item = new Item('Child', '/child');

        $this->assertSame($item, $item->detach());
        $this->assertNull($item->getParent());
        $this->assertNull($item->getOwnerMenu());
    }

    public function testDetachClearsStaleParentWithoutThrowing(): void
    {
        $parent = new Item('Parent', '/parent');
        $parent->getSubMenu();
        $item = new Item('Child', '/child');
        $item->setParent($parent);

        $this->assertSame($item, $item->detach());
        $this->assertNull($item->getParent());
        $this->assertNull($item->getOwnerMenu());
    }

    public function testDetachFrozenItemThrowsCleanly(): void
    {
        $menu = Menu::create();
        $item = $menu->addItem('Child', '/child', ['id' => 'child'])->freeze();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot mutate a frozen menu item.');
        $item->detach();
    }

    public function testAddRestoresParentWhenCustomSubMenuRejectsItem(): void
    {
        $parent = new Item('Parent', '/parent');
        $child = new Item('Child', '/child');
        $subMenu = $this->createMock(MenuInterface::class);
        $subMenu->method('setOwnerItem')->willReturnSelf();
        $subMenu->expects($this->once())
            ->method('add')
            ->with($child)
            ->willThrowException(new RuntimeException('Nope.'));
        $parent->setSubMenu($subMenu);

        try {
            $parent->add($child);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Nope.', $exception->getMessage());
        }

        $this->assertNull($child->getParent());
    }

    public function testSetSubMenuThrowsClearErrorForPopulatedForeignSubMenu(): void
    {
        $firstParent = new Item('First', '/first');
        $secondParent = new Item('Second', '/second');
        $foreignMenu = $firstParent->getSubMenu();
        $foreignMenu->addItem('Child', '/child', ['id' => 'child']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot reassign a populated submenu that already belongs to another item. Detach or clone its items first.',
        );
        $secondParent->setSubMenu($foreignMenu);
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

    public function testHeaderFlag(): void
    {
        $item = (new Item('Account'))->setHeader();

        $this->assertTrue($item->isHeader());
        $this->assertTrue($item->toArray()['header']);

        $item->setHeader(false);
        $this->assertFalse($item->isHeader());
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
