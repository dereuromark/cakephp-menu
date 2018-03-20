<?php
declare(strict_types = 1);

namespace Menu\TestCase\Menu;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Menu\Item\Item;
use Menu\Link\Link;
use Menu\Menu;
use Menu\View\Helper\MenuHelper;

class MenuHelperTest extends TestCase {

	/**
	 * @return void
	 */
	public function testRender() {
		$menuHelper = new MenuHelper(new View());

		$item = (new Item())
			->setLabel('First')
			->setLink((new Link())->setUrl('/x'));

		$item2 = (new Item())
			->setLabel('Second')
			->setLink((new Link())->setUrl('/y'));

		$menu = new Menu();
		$menu->add($item)
			->add($item2);

		$html = $menuHelper->render($menu);

		$expected = '<a href="/x">First</a><a href="/y">Second</a>';
		$this->assertSame($expected, $html);
	}

}
