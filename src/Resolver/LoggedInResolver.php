<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;
use InvalidArgumentException;

class LoggedInResolver implements ResolverInterface
{
    public function __construct(protected bool $loggedIn)
    {
    }

    public function resolve(ItemInterface $item, ResolverContext $context): void
    {
        $auth = $item->getData('auth');
        if (is_string($auth)) {
            $auth = AuthState::tryFrom($auth) ?? throw new InvalidArgumentException('Unknown auth state: ' . $auth);
        }
        if ($auth === AuthState::LoggedIn) {
            $item->setRuntimeVisible($this->loggedIn);
        } elseif ($auth === AuthState::LoggedOut) {
            $item->setRuntimeVisible(!$this->loggedIn);
        }
    }
}
