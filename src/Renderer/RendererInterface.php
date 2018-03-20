<?php
declare(strict_types = 1);

namespace Menu\Renderer;

use Menu\MenuInterface;

interface RendererInterface {

	/**
	 * @param \Menu\MenuInterface $menu
	 * @param array $options
	 *
	 * @return string
	 */
	public function render(MenuInterface $menu, array $options = []);

}
