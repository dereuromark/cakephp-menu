<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;
use Closure;

class AuthorizationResolver implements ContextAwareResolverInterface
{
    use RuntimeStateTrait;

    /**
     * @var \Closure(\CakeMenu\Item\ItemInterface, \CakeMenu\Resolver\ResolverContext): (bool|null)
     */
    protected Closure $callback;

    /**
     * @param callable(\CakeMenu\Item\ItemInterface, \CakeMenu\Resolver\ResolverContext): (bool|null) $callback
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
            $this->applyVisibility($item, $allowed);
        }
    }
}
