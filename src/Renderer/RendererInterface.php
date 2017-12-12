<?php
declare(strict_types = 1);

namespace Cake\Menu\Renderer;

use Cake\Menu\MenuInterface;

interface RendererInterface {

    /**
     * @param \Cake\Menu\MenuInterface $menu
     * @return string
     */
	public function render(MenuInterface $menu);

}
