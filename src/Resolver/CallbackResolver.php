<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;
use Closure;

class CallbackResolver implements ContextAwareResolverInterface
{
    /**
     * @var \Closure(\CakeMenu\Item\ItemInterface, \CakeMenu\Resolver\ResolverContext): void
     */
    protected Closure $callback;

    /**
     * @param callable(\CakeMenu\Item\ItemInterface, \CakeMenu\Resolver\ResolverContext): void $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = Closure::fromCallable($callback);
    }

    public function resolve(ItemInterface $item): void
    {
        ($this->callback)($item, new ResolverContext());
    }

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void
    {
        ($this->callback)($item, $context);
    }
}
