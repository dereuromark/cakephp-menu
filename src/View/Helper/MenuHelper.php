<?php
declare(strict_types = 1);

namespace Cake\Menu;

use Cake\View\Helper;

/**
 * Menu Helper
 *
 * A simple CakePHPish wrapper to render menu objects
 */
class MenuHelper extends Helper {

	/**
	 * Renders a menu
	 *
	 * @param \Cake\Menu\Menu $menu
	 * @param array $options Options
	 *
	 * @return string
	 */
	public function render(Menu $menu, array $options = []) {
		// Construct renderer and so on

		return $renderer->render($menu);
	}

}
