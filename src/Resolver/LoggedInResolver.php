<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

class LoggedInResolver implements ResolverInterface
{
    public function __construct(protected bool $loggedIn)
    {
    }

    public function resolve(ItemInterface $item): void
    {
        $auth = $item->getData('auth');
        if ($auth === 'loggedIn') {
            $item->setVisibility($this->loggedIn);
        } elseif ($auth === 'loggedOut') {
            $item->setVisibility(!$this->loggedIn);
        }
    }
}
