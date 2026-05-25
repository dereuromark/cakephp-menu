<?php

declare(strict_types=1);

namespace Menu;

use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Menu\Item\ItemInterface;
use Traversable;

/**
 * @implements \IteratorAggregate<int, \Menu\Item\ItemInterface>
 */
class ItemCollection implements Countable, IteratorAggregate
{
    /**
     * @var list<\Menu\Item\ItemInterface>
     */
    protected array $items = [];

    /**
     * @param array<mixed> $items
     */
    public function __construct(array $items = [])
    {
        $this->addMany($items);
    }

    public function add(ItemInterface $item): static
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @param array<mixed> $items
     *
     * @throws \InvalidArgumentException
     */
    public function addMany(array $items): static
    {
        foreach ($items as $item) {
            if (!$item instanceof ItemInterface) {
                throw new InvalidArgumentException('All collection items must implement ' . ItemInterface::class);
            }
            $this->add($item);
        }

        return $this;
    }

    /**
     * @return list<\Menu\Item\ItemInterface>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function findById(string $id): ?ItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
        }

        return null;
    }

    public function findByKey(string $key): ?ItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->getKey() === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<\Menu\Item\ItemInterface>
     */
    public function findByParent(string|ItemInterface $parent): array
    {
        $parentId = $parent instanceof ItemInterface ? $parent->getId() : $parent;

        return array_values(array_filter(
            $this->items,
            static fn (ItemInterface $item): bool => $item->getParentId() === $parentId,
        ));
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return \Traversable<int, \Menu\Item\ItemInterface>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
