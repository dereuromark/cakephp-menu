<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

interface ContextAwareResolverInterface extends ResolverInterface
{
    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void;
}
