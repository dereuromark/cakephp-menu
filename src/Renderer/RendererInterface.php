<?php
declare(strict_types = 1);

namespace Cake\Menu\Renderer;

use Cake\Menu\MenuInterface;

interface RendererInterface {

	/**
	 * @param \Cake\Menu\MenuInterface $menu
	 * @param array $options
	 *
	 * @return string
	 */
	public function render(MenuInterface $menu, array $options = []);

}
