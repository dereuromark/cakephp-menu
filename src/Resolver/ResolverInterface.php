<?php
declare(strict_types = 1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

interface ResolverInterface {

	/**
	 * @param \Menu\Item\ItemInterface $item
	 * @return void
	 */
	public function resolve(ItemInterface $item);

}
