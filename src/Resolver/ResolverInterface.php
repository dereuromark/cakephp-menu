<?php
declare(strict_types = 1);

namespace Cake\Menu\Resolver;

use Cake\Menu\ItemInterface;

interface ResolverInterface {

	public function resolve(ItemInterface $item);

}
