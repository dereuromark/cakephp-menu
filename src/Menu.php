<?php

declare(strict_types=1);

namespace Menu;

use Closure;
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
                    'header' => $itemConfig['header'] ?? false,
                    'submenuAttributes' => $itemConfig['submenu']['attributes'] ?? [],
                    'matchRoutes' => $itemConfig['matchRoutes'] ?? [],
                    'ignoreQueryString' => $itemConfig['ignoreQueryString'] ?? null,
                    'fuzzy' => $itemConfig['fuzzy'] ?? false,
                    'displayChildren' => $itemConfig['displayChildren'] ?? true,
                    'labelAttributes' => $itemConfig['labelAttributes'] ?? [],
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

    /**
     * Builds a menu from a flat list of rows (e.g. database records), linking children to parents.
     *
     * The mapper receives each row and returns a spec with `key`, optional `parent` (the key of the
     * parent row, or `null` for a root item), `label`, `link`, and optional `options` (any
     * `newItem()` option). Rows may arrive in any order; children are attached once all rows are
     * read, and rows whose `parent` key is unknown are treated as root items.
     *
     * @phpstan-param iterable<mixed> $rows
     * @phpstan-param \Closure(mixed): array<string, mixed> $mapper
     */
    public static function fromFlat(iterable $rows, Closure $mapper): static
    {
        $menu = static::create();
        /** @var array<string, \Menu\Item\ItemInterface> $byKey */
        $byKey = [];
        /** @var list<array{item: \Menu\Item\ItemInterface, parent: string|null}> $pending */
        $pending = [];

        foreach ($rows as $row) {
            $spec = $mapper($row);

            $link = $spec['link'] ?? null;
            if ($link !== null && !is_string($link) && !is_array($link) && !$link instanceof LinkInterface) {
                $link = null;
            }

            $item = $menu->newItem(
                isset($spec['label']) ? (string)$spec['label'] : null,
                $link,
                (array)($spec['options'] ?? []),
            );

            $key = isset($spec['key']) ? (string)$spec['key'] : '';
            if ($key !== '') {
                $item->setKey($key);
                $byKey[$key] = $item;
            }

            $parent = isset($spec['parent']) ? (string)$spec['parent'] : null;
            $pending[] = ['item' => $item, 'parent' => $parent];
        }

        foreach ($pending as $entry) {
            $parent = $entry['parent'] !== null ? ($byKey[$entry['parent']] ?? null) : null;
            if ($parent !== null && !static::wouldCycle($entry['item'], $parent)) {
                $parent->add($entry['item']);

                continue;
            }
            $menu->add($entry['item']);
        }

        return $menu;
    }

    /**
     * Whether attaching $item under $parent would create a cycle — i.e. $parent is $item itself or
     * already a descendant of $item (from a self-reference or a loop in the source data). Such an
     * edge is dropped (the item becomes a root) so the tree stays acyclic and finite.
     */
    protected static function wouldCycle(ItemInterface $item, ItemInterface $parent): bool
    {
        $ancestor = $parent;
        while ($ancestor !== null) {
            if ($ancestor === $item) {
                return true;
            }
            $ancestor = $ancestor->getParent();
        }

        return false;
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
     * Adds a non-link section header (a group label).
     *
     * @phpstan-param array<string, mixed> $options
     */
    public function addHeader(string $label, array $options = []): ItemInterface
    {
        $item = $this->newItem($label, null, $options);
        $item->setHeader();
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
        if (isset($options['icon'])) {
            $item->setIcon((string)$options['icon']);
        }
        if (isset($options['badge'])) {
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
        if (!empty($options['header'])) {
            $item->setHeader();
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
        if (array_key_exists('displayChildren', $options)) {
            $item->setDisplayChildren((bool)$options['displayChildren']);
        }
        if (isset($options['labelAttributes']) && is_array($options['labelAttributes'])) {
            $item->setLabelAttributes($options['labelAttributes']);
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

    /**
     * Returns the first item (depth-first) whose key matches. Keys are explicit when set, otherwise
     * a slug of the label, so labels that collide share a key — assign explicit keys when you need
     * to target a specific item unambiguously.
     */
    public function getByKey(string $key): ?ItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->getKey() === $key) {
                return $item;
            }
            if ($item->hasSubMenu()) {
                $found = $item->getSubMenu()->getByKey($key);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    public function hasKey(string $key): bool
    {
        return $this->getByKey($key) !== null;
    }

    /**
     * Removes the first item (depth-first) whose key matches. As with getByKey(), matching uses the
     * explicit key or the label slug, so prefer explicit keys when labels may collide.
     */
    public function removeByKey(string $key): static
    {
        $this->assertMutable();
        $this->removeFirstByKey($key);

        return $this;
    }

    /**
     * Removes the first item matching the key, searching this level before descending. Returns
     * whether an item was removed.
     */
    protected function removeFirstByKey(string $key): bool
    {
        foreach ($this->items as $index => $item) {
            if ($item->getKey() === $key) {
                array_splice($this->items, $index, 1);

                return true;
            }
        }
        foreach ($this->items as $item) {
            if ($item->hasSubMenu() && $item->getSubMenu()->getByKey($key) !== null) {
                $item->getSubMenu()->removeByKey($key);

                return true;
            }
        }

        return false;
    }

    public function insertBefore(ItemInterface $item, string $idOrKey): static
    {
        $this->assertMutable();
        $index = $this->indexOf($idOrKey);
        if ($index === null) {
            throw new InvalidArgumentException(sprintf('Unknown menu item `%s` to insert before.', $idOrKey));
        }

        $this->insertItemAt($item, $index);

        return $this;
    }

    public function insertAfter(ItemInterface $item, string $idOrKey): static
    {
        $this->assertMutable();
        $index = $this->indexOf($idOrKey);
        if ($index === null) {
            throw new InvalidArgumentException(sprintf('Unknown menu item `%s` to insert after.', $idOrKey));
        }

        $this->insertItemAt($item, $index + 1);

        return $this;
    }

    public function moveToPosition(string $idOrKey, int $position): static
    {
        $this->assertMutable();
        $index = $this->indexOf($idOrKey);
        if ($index === null) {
            throw new InvalidArgumentException(sprintf('Unknown menu item `%s` to move.', $idOrKey));
        }

        $item = $this->items[$index];
        array_splice($this->items, $index, 1);
        $position = max(0, min($position, count($this->items)));
        array_splice($this->items, $position, 0, [$item]);

        return $this;
    }

    public function moveToFirstPosition(string $idOrKey): static
    {
        return $this->moveToPosition($idOrKey, 0);
    }

    public function moveToLastPosition(string $idOrKey): static
    {
        return $this->moveToPosition($idOrKey, count($this->items));
    }

    public function reorder(array $order): static
    {
        $this->assertMutable();
        $ordered = [];
        $used = [];
        foreach ($order as $idOrKey) {
            $index = $this->indexOf((string)$idOrKey);
            if ($index === null || isset($used[$index])) {
                continue;
            }
            $used[$index] = true;
            $ordered[] = $this->items[$index];
        }
        foreach ($this->items as $index => $item) {
            if (!isset($used[$index])) {
                $ordered[] = $item;
            }
        }

        $this->items = $ordered;

        return $this;
    }

    public function merge(MenuInterface $menu, bool $mergeAttributes = false): static
    {
        $this->assertMutable();
        foreach ($this->cloneItems($menu->getItems()) as $item) {
            $this->add($item);
        }

        if ($mergeAttributes) {
            $this->attributes += $menu->getAttributes();
            foreach ((array)$menu->getData() as $name => $value) {
                if (!array_key_exists((string)$name, $this->data)) {
                    $this->data[(string)$name] = $value;
                }
            }
        }

        return $this;
    }

    public function slice(int|string $offset, int|string|null $length = null): static
    {
        $start = $this->resolveBoundary($offset);
        if ($length === null) {
            $end = count($this->items);
        } elseif (is_int($length)) {
            $end = $start + $length;
        } else {
            $end = $this->resolveBoundary($length);
        }

        $slice = array_slice($this->items, $start, max(0, $end - $start));

        $new = static::create($this->attributes);
        foreach ($this->data as $name => $value) {
            $new->setData((string)$name, $value);
        }
        $new->setItems($this->cloneItems($slice));

        return $new;
    }

    /**
     * @phpstan-return array{primary: static, secondary: static}
     */
    public function split(int|string $length): array
    {
        $boundary = $this->resolveBoundary($length);

        return [
            'primary' => $this->slice(0, $boundary),
            'secondary' => $this->slice($boundary),
        ];
    }

    /**
     * Finds the index of a direct child by its id or key, or null when absent. Ids take precedence
     * so an explicit id can always be targeted, even when another item's slug key collides with it.
     */
    protected function indexOf(string $idOrKey): ?int
    {
        foreach ($this->items as $index => $item) {
            if ($item->getId() === $idOrKey) {
                return $index;
            }
        }
        foreach ($this->items as $index => $item) {
            if ($item->getKey() === $idOrKey) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Resolves a position argument (an integer index, or an id/key of a direct child) to an index.
     *
     * @throws \InvalidArgumentException When a string id/key is not found.
     */
    protected function resolveBoundary(int|string $value): int
    {
        if (is_int($value)) {
            return max(0, min($value, count($this->items)));
        }

        $index = $this->indexOf($value);
        if ($index === null) {
            throw new InvalidArgumentException(sprintf('Unknown menu item `%s`.', $value));
        }

        return $index;
    }

    protected function insertItemAt(ItemInterface $item, int $position): void
    {
        if ($this->ownerItem !== null && !$item->hasParent()) {
            $item->setParent($this->ownerItem);
        }
        $this->assertUniqueItemTree($item);
        $position = max(0, min($position, count($this->items)));
        array_splice($this->items, $position, 0, [$item]);
    }

    /**
     * Deep-clones a set of items (via array round-trip) so derived menus from slice()/split()/merge()
     * own independent item objects and leave the source tree untouched.
     *
     * Note: cloning goes through toArray()/fromArray(), so items are rebuilt as the base item class;
     * custom ItemInterface implementations (e.g. SelfRendererInterface) are not preserved in the
     * derived menu.
     *
     * @param list<\Menu\Item\ItemInterface> $items
     *
     * @return list<\Menu\Item\ItemInterface>
     */
    protected function cloneItems(array $items): array
    {
        $config = [
            'items' => array_map(
                static fn (ItemInterface $item): array => $item->toArray(),
                $items,
            ),
        ];

        return static::fromArray($config)->getItems();
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
