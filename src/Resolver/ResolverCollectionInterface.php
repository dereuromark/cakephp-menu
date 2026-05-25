<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

interface ResolverCollectionInterface
{
    public function add(ResolverInterface $resolver): static;

    /**
     * @param list<\Menu\Resolver\ResolverInterface> $resolvers
     *
     * @return $this
     */
    public function addMany(array $resolvers): static;

    /**
     * @return list<\Menu\Resolver\ResolverInterface>
     */
    public function all(): array;

    public function resolve(ItemInterface $item): void;
}
