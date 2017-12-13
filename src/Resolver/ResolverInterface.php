<?php
declare(strict_types = 1);

namespace Cake\Menu\Resolver;

use Cake\Menu\Item\ItemInterface;

interface ResolverInterface {

	/**
	 * @param \Cake\Menu\Item\ItemInterface $item
	 * @return void
	 */
	public function resolve(ItemInterface $item);

}
