<?php
declare(strict_types = 1);

namespace Cake\Menu\TestCase\Menu;

use Cake\Menu\Item;
use Cake\Menu\Link;
use Cake\Menu\Menu;
use Cake\Menu\Renderer\StringTemplateRenderer;
use Cake\TestSuite\TestCase;

/**
 * Item Test Case
 */
class ItemTestCase extends TestCase {

	public function testItem() {
		$renderer = new StringTemplateRenderer();

		$item = (new Item())
			->setTitle('First')
			->setLink(new Link());

		debug($item);

		debug($renderer->renderItem($item));

		$menu = new Menu();
		$menu->add(new Item('foo'));


	}
}
