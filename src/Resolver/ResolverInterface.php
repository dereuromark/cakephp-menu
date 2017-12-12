<?php
declare(strict_types = 1);

namespace Cake\Menu\Resolver;

use Cake\Menu\ItemInterface;

interface ResolverInterface {

	/**
	 * @param \Cake\Menu\ItemInterface $item
	 * @return void
	 */
	public function resolve(ItemInterface $item);

}
