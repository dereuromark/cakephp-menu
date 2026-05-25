<?php

declare(strict_types=1);

namespace Menu;

use Menu\Item\ItemInterface;
use Menu\Link\LinkInterface;
use Menu\Resolver\ResolverCollectionInterface;
use Menu\Resolver\ResolverInterface;

interface MenuInterface
{
    /**
     * @var string
     */
    public const SORT_ASC = 'asc';

    /**
     * @var string
     */
    public const SORT_DESC = 'desc';

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public static function create(array $attributes = []): static;

    public function add(ItemInterface $item): static;

    /**
     * @phpstan-param \Menu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     * @phpstan-param array<string, mixed> $options
     */
    public function addItem(
        string $label,
        LinkInterface|string|array|null $link = null,
        array $options = [],
    ): ItemInterface;

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function addRaw(string $html, array $options = []): ItemInterface;

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function addDivider(array $options = []): ItemInterface;

    /**
     * @phpstan-param \Menu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     * @phpstan-param array<string, mixed> $options
     */
    public function newItem(
        ?string $label = null,
        LinkInterface|string|array|null $link = null,
        array $options = [],
    ): ItemInterface;

    /**
     * @return list<\Menu\Item\ItemInterface>
     */
    public function getItems(): array;

    /**
     * @phpstan-param array<mixed> $items
     *
     * @return $this
     */
    public function setItems(array $items): static;

    public function get(string $id): ?ItemInterface;

    public function has(string $id): bool;

    public function remove(string $id): static;

    public function clearActive(): static;

    public function getActiveItem(): ?ItemInterface;

    public function setData(string $name, mixed $value): static;

    public function getData(?string $name = null): mixed;

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getAttributes(): array;

    public function setAttribute(string $name, mixed $value): static;

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes, bool $merge = false): static;

    public function filter(callable $callback): static;

    public function sortBy(callable|string $by, string $direction = self::SORT_ASC): static;

    public function resolve(ResolverInterface|ResolverCollectionInterface $resolver): static;
}
