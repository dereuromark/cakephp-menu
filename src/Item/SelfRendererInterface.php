<?php
declare(strict_types = 1);

namespace Menu\Item;

interface SelfRendererInterface {

	/**
	 * @return string
	 */
	public function render();

}
