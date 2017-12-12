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
		$item = (new Item())
			->setTitle('First')
			->setLink(new Link());
		debug($item);

        $renderer = new StringTemplateRenderer();
        debug($renderer->renderItem($item));

        $item2 = (new Item())
            ->setTitle('Second')
            ->setLink(new Link());

		$menu = new Menu();
		$menu->add(new Item('foo'));
		$menu->add($item);
        $menu->add($item2);

        //Render menu
	}
}
