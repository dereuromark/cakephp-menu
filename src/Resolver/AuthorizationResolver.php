<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Closure;
use Menu\Item\ItemInterface;

class AuthorizationResolver implements ContextAwareResolverInterface
{
    /**
     * @var \Closure(\Menu\Item\ItemInterface, \Menu\Resolver\ResolverContext): (bool|null)
     */
    protected Closure $callback;

    /**
     * @param callable(\Menu\Item\ItemInterface, \Menu\Resolver\ResolverContext): (bool|null) $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = Closure::fromCallable($callback);
    }

    public function resolve(ItemInterface $item): void
    {
        $this->resolveWithContext($item, new ResolverContext());
    }

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void
    {
        $allowed = ($this->callback)($item, $context);
        if ($allowed !== null) {
            $item->setVisibility($allowed);
        }
    }
}
