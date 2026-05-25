<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Closure;
use Menu\Item\ItemInterface;

class CallbackResolver implements ContextAwareResolverInterface
{
    /**
     * @var \Closure(\Menu\Item\ItemInterface, \Menu\Resolver\ResolverContext): void
     */
    protected Closure $callback;

    /**
     * @param callable(\Menu\Item\ItemInterface, \Menu\Resolver\ResolverContext): void $callback
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
