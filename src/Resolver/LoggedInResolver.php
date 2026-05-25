<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

class LoggedInResolver implements ContextAwareResolverInterface
{
    public function __construct(protected bool $loggedIn)
    {
    }

    public function resolve(ItemInterface $item): void
    {
        $this->resolveWithContext($item, new ResolverContext());
    }

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void
    {
        $auth = $item->getData('auth');
        if ($auth === 'loggedIn') {
            $item->setVisibility($this->loggedIn);
        } elseif ($auth === 'loggedOut') {
            $item->setVisibility(!$this->loggedIn);
        }
    }
}
