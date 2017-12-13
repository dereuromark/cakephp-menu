<?php
declare(strict_types = 1);

namespace Cake\Menu\TestCase\Item;

use Cake\Menu\Item\Item;
use Cake\Menu\Link\Link;
use Cake\Menu\Renderer\StringTemplateRenderer;
use Cake\TestSuite\TestCase;

class ItemTest extends TestCase {

	/**
	 * @return void
	 */
	public function testItem() {
		$item = (new Item())
			->setTitle('First')
			->setLink(new Link());
		debug($item);

		$renderer = new StringTemplateRenderer();
		debug($renderer->renderItem($item));
	}

}
