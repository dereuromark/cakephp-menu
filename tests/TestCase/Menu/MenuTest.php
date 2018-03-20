<?php
declare(strict_types = 1);

namespace Menu\TestCase\Menu;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Link\Link;
use Menu\Menu;

/**
 * Item Test Case
 */
class MenuTest extends TestCase {

	public function testGetAttributes() {
		$menu = Menu::create();
		$menu->setAttributes(['foo' => 'bar']);
		$menu->setAttributes(['fo' => 'ba']);

		$attributes = $menu->getAttributes();
		$expected = [
			'fo' => 'ba',
		];
		$this->assertSame($expected, $attributes);
	}

	public function testGetAttributesMerge() {
		$menu = Menu::create();
		$menu->setAttributes(['foo' => 'bar']);
		$menu->setAttributes(['fo' => 'ba'], true);

		$attributes = $menu->getAttributes();
		$expected = [
			'fo' => 'ba',
			'foo' => 'bar'
		];
		$this->assertSame($expected, $attributes);
	}

	/**
	 * @return void
	 */
	public function testMenu() {
		$item = (new Item())
			->setLabel('First<>')
			->setLink(new Link());

		$item2 = (new Item())
			->setLabel('Se<b>co</b>nd', true)
			->setLink(new Link());

		$menu = new Menu();
		$menu->add($item);
		$menu->add($item2);

		//Render menu
		$this->assertCount(2, $menu->getItems());
	}

}
