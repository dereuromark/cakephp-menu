<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;

interface ResolverInterface
{
    public function resolve(ItemInterface $item, ResolverContext $context): void;
}
