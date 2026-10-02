<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

use CakeMenu\Item\ItemInterface;

interface ResolverCollectionInterface
{
    public function add(ResolverInterface $resolver): static;

    /**
     * @param list<\CakeMenu\Resolver\ResolverInterface> $resolvers
     *
     * @return $this
     */
    public function addMany(array $resolvers): static;

    /**
     * @return list<\CakeMenu\Resolver\ResolverInterface>
     */
    public function all(): array;

    public function resolve(ItemInterface $item): void;

    public function resolveWithContext(ItemInterface $item, ResolverContext $context): void;
}
