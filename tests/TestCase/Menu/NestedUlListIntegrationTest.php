<?php
declare(strict_types = 1);

namespace Menu\TestCase\Menu;

use Cake\TestSuite\TestCase;
use Menu\Link\Link;
use Menu\Menu;

/**
 * This tests builds a complete bootstrap3 like nested menu
 */
class NestedUlListIntegrationTest extends TestCase {

	/**
	 * @return void
	 */
	public function testMenu() {
		$expected = '
		<ul id="menu1" class="dropdown-menu">
			<li><a href="/users/dashboard">Dashboard</a></li>
			<li class="dropdown-submenu">
				<a hef="#">Sub Menu</a>
				<ul class="dropdown-submenu">
					<li>2.1</li>
					<li>2.2</li>
					<li>
						2.3
						<ul>
							<li>3.1</li>
							<li>3.2</li>
						</ul>
					</li>
				</ul>
			<li>
			<li class="divider"></li>
			<li>
				<i class="fa fa-sign-out" aria-hidden="true"></i>
				<a href="/logout">Logout</a>
			</li>
		</ul>';

		$menu = new Menu();

		//TODO
		return;

		$menu->setAttributes(['id' => 'menu1', 'class' => 'dropdown-menu']);

		$menu->addChildren([
			$menu->newItem('Dashboard')
				->setLink((new Link())->setUrl('/users/dashboard')),
			$menu->newItem('Sub Menu')
				->setLink((new Link())->setUrl('#')),
			$menu->divider(),
			$menu->newItem('Logout')
				->before('<i class="fa fa-sign-out" aria-hidden="true"></i>')
				->setLink((new Link())->setUrl('/logout')),
		]);

		$subMenu = $menu->newMenu('sub-menu');
		$subMenu->addChildren([
			$menu->newItem(''),
			$menu->newItem(''),
			$menu->newItem(''),
		]);
		$menu->getChild('x')->setSubMenu($subMenu);
	}

}
