<?php
declare(strict_types = 1);

namespace Cake\Menu\TestCase\Menu;

use Cake\Menu\Item;
use Cake\Menu\Link;
use Cake\Menu\Menu;
use Cake\TestSuite\TestCase;

/**
 * Item Test Case
 */
class MenuTest extends TestCase {

    /**
     * @return void
     */
	public function testMenu() {
		$item = (new Item())
			->setTitle('First')
			->setLink(new Link());

		$item2 = (new Item())
			->setTitle('Second')
			->setLink(new Link());

		$menu = new Menu();
		$menu->add($item);
		$menu->add($item2);

		//Render menu

        $attributes = $menu->getAttributes();
        debug($attributes);
	}

}
