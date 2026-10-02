<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;
use function is_string;
use function method_exists;

class PermissionResolver implements ResolverInterface
{
    public function __construct(
        protected object $authorizer,
        protected mixed $identity = null,
        protected string $dataKey = 'permission',
        protected string $method = 'can',
    ) {
    }

    public function resolve(ItemInterface $item, ResolverContext $context): void
    {
        $permission = $item->getData($this->dataKey);
        if (!is_string($permission) || !method_exists($this->authorizer, $this->method)) {
            return;
        }

        $allowed = $this->authorizer->{$this->method}($this->identity, $permission, $item);
        if (is_bool($allowed)) {
            $item->setRuntimeVisible($allowed);
        }
    }
}
