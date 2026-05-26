<?php

declare(strict_types=1);

namespace Menu;

use Closure;
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

    /**
     * @phpstan-param array<string, mixed> $config
     */
    public static function fromArray(array $config): static;

    /**
     * @phpstan-param iterable<mixed> $rows
     * @phpstan-param \Closure(mixed): array<string, mixed> $mapper
     */
    public static function fromFlat(iterable $rows, Closure $mapper): static;

    public function add(ItemInterface $item): static;

    /**
     * @phpstan-param array<mixed> $items
     */
    public function addItems(array $items): static;

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
     * @phpstan-param array<string, mixed> $options
     */
    public function addHeader(string $label, array $options = []): ItemInterface;

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

    public function getByKey(string $key): ?ItemInterface;

    public function hasKey(string $key): bool;

    public function removeByKey(string $key): static;

    public function insertBefore(ItemInterface $item, string $idOrKey): static;

    public function insertAfter(ItemInterface $item, string $idOrKey): static;

    public function moveToPosition(string $idOrKey, int $position): static;

    public function moveToFirstPosition(string $idOrKey): static;

    public function moveToLastPosition(string $idOrKey): static;

    /**
     * @phpstan-param list<string> $order
     */
    public function reorder(array $order): static;

    public function merge(MenuInterface $menu, bool $mergeAttributes = false): static;

    public function slice(int|string $offset, int|string|null $length = null): static;

    /**
     * @phpstan-return array{primary: static, secondary: static}
     */
    public function split(int|string $length): array;

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

    public function find(callable $callback): ItemCollection;

    public function sortBy(callable|string $by, string $direction = self::SORT_ASC): static;

    public function resolve(ResolverInterface|ResolverCollectionInterface $resolver): static;

    public function resetState(): static;

    public function freeze(): static;

    public function isFrozen(): bool;

    /**
     * @phpstan-return array<string, mixed>
     */
    public function toArray(): array;
}
