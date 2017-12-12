<?php
declare(strict_types = 1);

namespace Cake\Menu\Item;

interface SelfRendererInterface {

	/**
	 * @return string
	 */
	public function render();

}
