<?php

declare(strict_types=1);

namespace Menu\Resolver;

use InvalidArgumentException;
use Menu\Item\ItemInterface;

class ResolverCollection implements ResolverCollectionInterface, ContextAwareResolverInterface
{
    /**
     * @var list<\Menu\Resolver\ResolverInterface>
     */
    protected array $resolvers = [];

    public function add(ResolverInterface $resolver): static
    {
        $this->resolvers[] = $resolver;

        return $this;
    }

    /**
     * @phpstan-param array<mixed> $resolvers
     *
     * @throws \InvalidArgumentException
     */
    public function addMany(array $resolvers): static
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof ResolverInterface) {
                throw new InvalidArgumentException('All resolvers must implement ' . ResolverInterface::class);
            }
            $this->add($resolver);
        }

        return $this;
    }

    /**
     * @return list<\Menu\Resolver\ResolverInterface>
     */
    public function all(): array
    {
        return $this->resolvers;
    }

    public function resolve(ItemInterface $item): void
    {
        $this->resolveWithContext($item, new ResolverContext());
    }

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver instanceof ContextAwareResolverInterface) {
                $resolver->resolveWithContext($item, $context);

                continue;
            }

            $resolver->resolve($item);
        }
    }
}
