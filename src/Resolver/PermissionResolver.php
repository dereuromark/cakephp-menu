<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;
use ReflectionMethod;
use function is_string;
use function method_exists;

class PermissionResolver implements ContextAwareResolverInterface
{
    use RuntimeStateTrait;

    protected ?int $parameterCount = null;

    public function __construct(
        protected object $authorizer,
        protected mixed $identity = null,
        protected string $dataKey = 'permission',
        protected string $method = 'can',
    ) {
    }

    public function resolve(ItemInterface $item): void
    {
        $this->resolveWithContext($item, new ResolverContext());
    }

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void
    {
        $permission = $item->getData($this->dataKey);
        if (!is_string($permission) || !method_exists($this->authorizer, $this->method)) {
            return;
        }

        $allowed = $this->invokeAuthorizer($permission, $item, $context);
        if (is_bool($allowed)) {
            $this->applyVisibility($item, $allowed);
        }
    }

    protected function invokeAuthorizer(string $permission, ItemInterface $item, ResolverContext $context): mixed
    {
        if ($this->parameterCount === null) {
            $reflectionMethod = new ReflectionMethod($this->authorizer, $this->method);
            $this->parameterCount = $reflectionMethod->getNumberOfParameters();
        }

        return match (true) {
            $this->parameterCount >= 4 => $this->authorizer->{$this->method}($this->identity, $permission, $item, $context),
            $this->parameterCount === 3 => $this->authorizer->{$this->method}($this->identity, $permission, $item),
            $this->parameterCount === 2 => $this->authorizer->{$this->method}($this->identity, $permission),
            $this->parameterCount === 1 => $this->authorizer->{$this->method}($permission),
            default => $this->authorizer->{$this->method}(),
        };
    }
}
