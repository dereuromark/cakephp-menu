<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

class ResolverContext
{
    public function __construct(
        protected int $depth = 1,
        protected ?ItemInterface $parent = null,
    ) {
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getParent(): ?ItemInterface
    {
        return $this->parent;
    }
}
