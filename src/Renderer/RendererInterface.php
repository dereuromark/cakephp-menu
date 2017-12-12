<?php
declare(strict_types = 1);

namespace Cake\Menu\Renderer;

interface RendererInterface {

	public function render(MenuInterface $menu);

}
