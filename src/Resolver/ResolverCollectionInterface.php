<?php

declare(strict_types=1);

namespace CakeMenu\Resolver;

interface ResolverCollectionInterface extends ResolverInterface
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
}
