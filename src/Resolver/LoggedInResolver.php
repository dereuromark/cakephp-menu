<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;

class LoggedInResolver implements ResolverInterface
{
    public function __construct(protected bool $loggedIn)
    {
    }

    public function resolve(ItemInterface $item, ResolverContext $context): void
    {
        $auth = $item->getData('auth');
        if ($auth === 'loggedIn') {
            $item->setRuntimeVisible($this->loggedIn);
        } elseif ($auth === 'loggedOut') {
            $item->setRuntimeVisible(!$this->loggedIn);
        }
    }
}
