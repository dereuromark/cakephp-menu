<?php

declare(strict_types=1);

namespace Menu;

use InvalidArgumentException;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Menu\Link\LinkInterface;
use Menu\Resolver\ResolverCollectionInterface;
use Menu\Resolver\ResolverInterface;

class Menu implements MenuInterface
{
    /**
     * @var list<\Menu\Item\ItemInterface>
     */
    protected array $items = [];

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @var class-string<\Menu\Item\ItemInterface>
     */
    protected string $itemClass = Item::class;

    protected ?ItemInterface $ownerItem = null;

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public static function create(array $attributes = []): static
    {
        $menu = new static();
        if ($attributes) {
            $menu->setAttributes($attributes);
        }

        return $menu;
    }

    public function add(ItemInterface $item): static
    {
        if ($this->ownerItem !== null && !$item->hasParent()) {
            $item->setParent($this->ownerItem);
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * @phpstan-param \Menu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     * @phpstan-param array<string, mixed> $options
     */
    public function addItem(
        string $label,
        LinkInterface|string|array|null $link = null,
        array $options = [],
    ): ItemInterface {
        $item = $this->newItem($label, $link, $options);
        $this->add($item);

        return $item;
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function addRaw(string $html, array $options = []): ItemInterface
    {
        $item = $this->newItem(null, null, $options);
        $item->setRaw($html);
        $this->add($item);

        return $item;
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function addDivider(array $options = []): ItemInterface
    {
        $item = $this->newItem(null, null, $options);
        $item->setDivider();
        $this->add($item);

        return $item;
    }

    /**
     * @phpstan-param \Menu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     * @phpstan-param array<string, mixed> $options
     */
    public function newItem(
        ?string $label = null,
        LinkInterface|string|array|null $link = null,
        array $options = [],
    ): ItemInterface {
        $className = $this->itemClass;
        $item = new $className($label, $link);

        if (isset($options['id'])) {
            $item->setId((string)$options['id']);
        }
        if (isset($options['key'])) {
            $item->setKey((string)$options['key']);
        }
        if (isset($options['escape'])) {
            $item->setLabel($item->getLabel() ?? '', (bool)$options['escape']);
        }
        if (isset($options['before'])) {
            $item->setBefore((string)$options['before']);
        }
        if (isset($options['after'])) {
            $item->setAfter((string)$options['after']);
        }
        if (isset($options['attributes']) && is_array($options['attributes'])) {
            $item->setAttributes($options['attributes']);
        }
        if (isset($options['data']) && is_array($options['data'])) {
            foreach ($options['data'] as $name => $value) {
                $item->setData((string)$name, $value);
            }
        }
        if (isset($options['visible'])) {
            $item->setVisibility((bool)$options['visible']);
        }
        if (isset($options['active'])) {
            $item->setActive((bool)$options['active']);
        }
        if (isset($options['raw'])) {
            $item->setRaw((string)$options['raw']);
        }
        if (!empty($options['divider'])) {
            $item->setDivider();
        }
        if (isset($options['submenuAttributes']) && is_array($options['submenuAttributes'])) {
            $item->getSubMenu()->setAttributes($options['submenuAttributes']);
        }
        if (isset($options['matchRoutes']) && is_array($options['matchRoutes'])) {
            $item->setMatchRoutes(array_values($options['matchRoutes']));
        }
        if (array_key_exists('ignoreQueryString', $options)) {
            $ignoreQueryString = $options['ignoreQueryString'];
            $item->setIgnoreQueryString(is_bool($ignoreQueryString) ? $ignoreQueryString : null);
        }
        if (!empty($options['fuzzy'])) {
            $item->setFuzzyMatch();
        }

        return $item;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @phpstan-param array<mixed> $items
     *
     * @throws \InvalidArgumentException
     */
    public function setItems(array $items): static
    {
        $validatedItems = [];
        foreach ($items as $item) {
            if (!$item instanceof ItemInterface) {
                throw new InvalidArgumentException('All menu items must implement ' . ItemInterface::class);
            }
            $validatedItems[] = $item;
        }

        $this->items = $validatedItems;

        return $this;
    }

    public function get(string $id): ?ItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->getId() === $id) {
                return $item;
            }
            if ($item->hasSubMenu()) {
                $found = $item->getSubMenu()->get($id);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    public function has(string $id): bool
    {
        return $this->get($id) !== null;
    }

    public function remove(string $id): static
    {
        $items = [];
        foreach ($this->items as $item) {
            if ($item->getId() === $id) {
                continue;
            }
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->remove($id);
            }
            $items[] = $item;
        }

        $this->items = $items;

        return $this;
    }

    public function clearActive(): static
    {
        foreach ($this->items as $item) {
            $item->setActive(false);
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->clearActive();
            }
        }

        return $this;
    }

    public function getActiveItem(): ?ItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->isActive()) {
                return $item;
            }
            if ($item->hasSubMenu()) {
                $activeItem = $item->getSubMenu()->getActiveItem();
                if ($activeItem !== null) {
                    return $activeItem;
                }
            }
        }

        return null;
    }

    public function setData(string $name, mixed $value): static
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function getData(?string $name = null): mixed
    {
        if ($name === null) {
            return $this->data;
        }

        return $this->data[$name] ?? null;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes, bool $merge = false): static
    {
        $this->attributes = $merge ? $attributes + $this->attributes : $attributes;

        return $this;
    }

    public function filter(callable $callback): static
    {
        $items = [];
        foreach ($this->items as $item) {
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->filter($callback);
            }
            if ($callback($item) !== false) {
                $items[] = $item;
            }
        }

        $this->items = $items;

        return $this;
    }

    public function sortBy(callable|string $by, string $direction = self::SORT_ASC): static
    {
        usort($this->items, function (ItemInterface $left, ItemInterface $right) use ($by, $direction): int {
            $leftValue = $this->extractSortValue($left, $by);
            $rightValue = $this->extractSortValue($right, $by);

            $result = $leftValue <=> $rightValue;

            return $direction === self::SORT_DESC ? -$result : $result;
        });

        foreach ($this->items as $item) {
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->sortBy($by, $direction);
            }
        }

        return $this;
    }

    public function resolve(ResolverInterface|ResolverCollectionInterface $resolver): static
    {
        foreach ($this->items as $item) {
            $resolver->resolve($item);
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->resolve($resolver);
            }
        }

        return $this;
    }

    public function setOwnerItem(ItemInterface $ownerItem): static
    {
        $this->ownerItem = $ownerItem;

        return $this;
    }

    protected function extractSortValue(ItemInterface $item, callable|string $by): mixed
    {
        if (is_callable($by)) {
            return $by($item);
        }

        return match ($by) {
            'id' => $item->getId(),
            'key' => $item->getKey(),
            'label' => $item->getLabel(),
            default => $item->getData($by),
        };
    }
}
