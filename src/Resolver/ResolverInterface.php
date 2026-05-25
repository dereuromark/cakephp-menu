<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

interface ResolverInterface
{
    public function resolve(ItemInterface $item): void;
}
