<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;
use Menu\Item\StateResetInterface;

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
            if ($item instanceof StateResetInterface) {
                $item->setRuntimeVisibility($this->loggedIn);
            } else {
                $item->setVisibility($this->loggedIn);
            }
        } elseif ($auth === 'loggedOut') {
            if ($item instanceof StateResetInterface) {
                $item->setRuntimeVisibility(!$this->loggedIn);
            } else {
                $item->setVisibility(!$this->loggedIn);
            }
        }
    }
}
