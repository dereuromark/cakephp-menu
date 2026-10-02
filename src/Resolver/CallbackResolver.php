<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;
use Closure;

class CallbackResolver implements ResolverInterface
{
    /**
     * @var \Closure(\CakeMenu\Item\ItemInterface, \CakeMenu\Resolver\ResolverContext): void
     */
    protected Closure $callback;

    /**
     * @param \Closure(\CakeMenu\Item\ItemInterface, \CakeMenu\Resolver\ResolverContext): void $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function resolve(ItemInterface $item, ResolverContext $context): void
    {
        ($this->callback)($item, $context);
    }
}
