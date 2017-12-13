<?php
declare(strict_types = 1);

namespace Cake\Menu\TestCase\Menu;

use Cake\Menu\Item\Item;
use Cake\Menu\Link\Link;
use Cake\Menu\Menu;
use Cake\Menu\View\Helper\MenuHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class MenuHelperTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRender() {
		$menuHelper = new MenuHelper(new View());

		$item = (new Item())
			->setTitle('First')
			->setLink(new Link());

		$item2 = (new Item())
			->setTitle('Second')
			->setLink(new Link());

		$menu = new Menu();
		$menu->add($item)
			->add($item2);

		$html = $menuHelper->render($menu);

		debug($html);
		//$this->assertHtml($expected, $html);
	}

}
