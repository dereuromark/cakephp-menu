<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;

interface ContextAwareResolverInterface extends ResolverInterface
{
    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void;
}
