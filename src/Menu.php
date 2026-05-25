<?php

declare(strict_types=1);

namespace Menu;

use InvalidArgumentException;
use LogicException;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Menu\Item\StateResetInterface;
use Menu\Link\Link;
use Menu\Link\LinkInterface;
use Menu\Resolver\ContextAwareResolverInterface;
use Menu\Resolver\ResolverCollectionInterface;
use Menu\Resolver\ResolverContext;
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

    protected bool $frozen = false;

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

    public static function fromArray(array $config): static
    {
        $menu = static::create((array)($config['attributes'] ?? []));
        foreach ((array)($config['data'] ?? []) as $name => $value) {
            $menu->setData((string)$name, $value);
        }

        foreach ((array)($config['items'] ?? []) as $itemConfig) {
            if (!is_array($itemConfig)) {
                continue;
            }

            $item = $menu->newItem(
                $itemConfig['label'] ?? null,
                $itemConfig['link'] ?? null,
                [
                    'id' => $itemConfig['id'] ?? null,
                    'key' => $itemConfig['key'] ?? null,
                    'escape' => $itemConfig['escape'] ?? true,
                    'before' => $itemConfig['before'] ?? null,
                    'after' => $itemConfig['after'] ?? null,
                    'icon' => $itemConfig['icon'] ?? null,
                    'badge' => $itemConfig['badge'] ?? null,
                    'badgeType' => $itemConfig['badgeType'] ?? null,
                    'attributes' => $itemConfig['attributes'] ?? [],
                    'data' => $itemConfig['data'] ?? [],
                    'visible' => $itemConfig['visible'] ?? true,
                    'active' => $itemConfig['active'] ?? false,
                    'raw' => $itemConfig['raw'] ?? null,
                    'divider' => $itemConfig['divider'] ?? false,
                    'submenuAttributes' => $itemConfig['submenu']['attributes'] ?? [],
                    'matchRoutes' => $itemConfig['matchRoutes'] ?? [],
                    'ignoreQueryString' => $itemConfig['ignoreQueryString'] ?? null,
                    'fuzzy' => $itemConfig['fuzzy'] ?? false,
                ],
            );

            if (!empty($itemConfig['linkAttributes']) && $item->getLink() !== null) {
                $item->getLink()->setAttributes((array)$itemConfig['linkAttributes']);
            }
            if (!empty($itemConfig['external']) && $item->getLink() !== null) {
                $item->setLink(Link::create(
                    $item->getLink()->getRawUrl(),
                    (array)$itemConfig['linkAttributes'],
                    true,
                ));
            }
            if (!empty($itemConfig['expanded'])) {
                $item->setExpanded();
            }
            if (!empty($itemConfig['submenu']) && is_array($itemConfig['submenu'])) {
                $item->setSubMenu(static::fromArray((array)$itemConfig['submenu']));
            }

            $menu->add($item);
        }

        return $menu;
    }

    public function add(ItemInterface $item): static
    {
        $this->assertMutable();
        if ($this->ownerItem !== null && !$item->hasParent()) {
            $item->setParent($this->ownerItem);
        }

        $this->assertUniqueItemTree($item);
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
        if (isset($options['icon']) && $item instanceof Item) {
            $item->setIcon((string)$options['icon']);
        }
        if (isset($options['badge']) && $item instanceof Item) {
            $item->setBadge((string)$options['badge'], isset($options['badgeType']) ? (string)$options['badgeType'] : null);
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

    public function collect(): ItemCollection
    {
        $collection = new ItemCollection();
        $this->collectInto($collection, $this->items);

        return $collection;
    }

    /**
     * @param \Menu\ItemCollection $collection
     * @param list<\Menu\Item\ItemInterface> $items
     */
    protected function collectInto(ItemCollection $collection, array $items): void
    {
        foreach ($items as $item) {
            $collection->add($item);
            if ($item->hasSubMenu()) {
                $this->collectInto($collection, $item->getSubMenu()->getItems());
            }
        }
    }

    /**
     * @phpstan-param array<mixed> $items
     *
     * @throws \InvalidArgumentException
     */
    public function setItems(array $items): static
    {
        $this->assertMutable();
        $validatedItems = [];
        foreach ($items as $item) {
            if (!$item instanceof ItemInterface) {
                throw new InvalidArgumentException('All menu items must implement ' . ItemInterface::class);
            }
            $validatedItems[] = $item;
        }

        $this->assertUniqueItems($validatedItems);
        $this->items = $validatedItems;
        if ($this->ownerItem !== null) {
            foreach ($this->items as $item) {
                $item->setParent($this->ownerItem);
            }
        }

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
        $this->assertMutable();
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
            if ($item instanceof StateResetInterface) {
                $item->setRuntimeActive(false);
            } else {
                $item->setActive(false);
            }
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
        $this->assertMutable();
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
        $this->assertMutable();
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes, bool $merge = false): static
    {
        $this->assertMutable();
        $this->attributes = $merge ? $attributes + $this->attributes : $attributes;

        return $this;
    }

    public function filter(callable $callback): static
    {
        $this->assertMutable();
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
        $this->assertMutable();
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
        $this->resolveItems($resolver, $this->items, 1, $this->ownerItem);

        return $this;
    }

    /**
     * @param \Menu\Resolver\ResolverInterface|\Menu\Resolver\ResolverCollectionInterface $resolver
     * @param list<\Menu\Item\ItemInterface> $items
     * @param \Menu\Item\ItemInterface|null $parent
     * @param int $depth
     */
    protected function resolveItems(
        ResolverInterface|ResolverCollectionInterface $resolver,
        array $items,
        int $depth,
        ?ItemInterface $parent,
    ): void {
        $context = new ResolverContext($depth, $parent);
        foreach ($items as $item) {
            if ($resolver instanceof ContextAwareResolverInterface) {
                $resolver->resolveWithContext($item, $context);
            } else {
                $resolver->resolve($item);
            }
            if ($item->hasSubMenu()) {
                $subMenu = $item->getSubMenu();
                if ($subMenu instanceof self) {
                    $subMenu->resolveItems($resolver, $subMenu->getItems(), $depth + 1, $item);
                } else {
                    $subMenu->resolve($resolver);
                }
            }
        }
    }

    /**
     * @param list<\Menu\Item\ItemInterface> $items
     */
    protected function assertUniqueItems(array $items): void
    {
        $ids = [];
        foreach ($items as $item) {
            $this->collectIdentifiers($item, $ids);
        }
    }

    public function setOwnerItem(ItemInterface $ownerItem): static
    {
        $this->assertMutable();
        $this->ownerItem = $ownerItem;
        foreach ($this->items as $item) {
            if (!$item->hasParent()) {
                $item->setParent($ownerItem);
            }
        }

        return $this;
    }

    public function resetState(): static
    {
        foreach ($this->items as $item) {
            if ($item instanceof StateResetInterface) {
                $item->resetState();
            } else {
                $item->setActive(false);
            }
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->resetState();
            }
        }

        return $this;
    }

    public function freeze(): static
    {
        $this->frozen = true;
        foreach ($this->items as $item) {
            $item->freeze();
        }

        return $this;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    public function toArray(): array
    {
        return [
            'attributes' => $this->attributes,
            'data' => $this->data,
            'items' => array_map(
                static fn (ItemInterface $item): array => $item->toArray(),
                $this->items,
            ),
        ];
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

    protected function assertUniqueItemTree(ItemInterface $candidate): void
    {
        $ids = [];
        foreach ($this->items as $item) {
            $this->collectIdentifiers($item, $ids);
        }

        $this->collectIdentifiers($candidate, $ids);
    }

    /**
     * @param \Menu\Item\ItemInterface $item
     * @param array<string, true> $ids
     *
     * @throws \InvalidArgumentException
     */
    protected function collectIdentifiers(ItemInterface $item, array &$ids): void
    {
        $id = $item->getId();
        if (isset($ids[$id])) {
            throw new InvalidArgumentException(sprintf('Duplicate menu item id `%s` detected.', $id));
        }
        $ids[$id] = true;

        if ($item->hasExplicitKey()) {
            $keyId = '__key__' . $item->getKey();
            if (isset($ids[$keyId])) {
                throw new InvalidArgumentException(sprintf('Duplicate explicit menu item key `%s` detected.', $item->getKey()));
            }
            $ids[$keyId] = true;
        }

        if ($item->hasSubMenu()) {
            foreach ($item->getSubMenu()->getItems() as $child) {
                $this->collectIdentifiers($child, $ids);
            }
        }
    }

    protected function assertMutable(): void
    {
        if ($this->frozen) {
            throw new LogicException('Cannot mutate a frozen menu.');
        }
    }
}
