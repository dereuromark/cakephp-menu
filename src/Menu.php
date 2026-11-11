<?php

declare(strict_types=1);

namespace CakeMenu;

use CakeMenu\Item\Item;
use CakeMenu\Item\ItemInterface;
use CakeMenu\Link\Link;
use CakeMenu\Link\LinkInterface;
use CakeMenu\Resolver\ResolverContext;
use CakeMenu\Resolver\ResolverInterface;
use Closure;
use InvalidArgumentException;
use LogicException;
use Stringable;

class Menu implements MenuInterface
{
    /**
     * @var list<\CakeMenu\Item\ItemInterface>
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
     * @var class-string<\CakeMenu\Item\ItemInterface>
     */
    protected string $itemClass = Item::class;

    protected ?ItemInterface $ownerItem = null;

    protected bool $frozen = false;

    /**
     * @var array<string, \CakeMenu\Item\ItemInterface>
     */
    protected array $idIndex = [];

    /**
     * @var array<string, \CakeMenu\Item\ItemInterface>
     */
    protected array $keyIndex = [];

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

    /**
     * @phpstan-param class-string<\CakeMenu\Item\ItemInterface>|null $itemClass
     */
    public static function fromArray(array $config, ?string $itemClass = null): static
    {
        $menu = static::create((array)($config['attributes'] ?? []));
        if ($itemClass !== null) {
            $menu->setItemClass($itemClass);
        }
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
                    'escapeIcon' => $itemConfig['escapeIcon'] ?? true,
                    'badge' => $itemConfig['badge'] ?? null,
                    'escapeBadge' => $itemConfig['escapeBadge'] ?? true,
                    'badgeType' => $itemConfig['badgeType'] ?? null,
                    'attributes' => $itemConfig['attributes'] ?? [],
                    'data' => $itemConfig['data'] ?? [],
                    'visible' => $itemConfig['visible'] ?? true,
                    'active' => $itemConfig['active'] ?? false,
                    'raw' => $itemConfig['raw'] ?? null,
                    'divider' => $itemConfig['divider'] ?? false,
                    'header' => $itemConfig['header'] ?? false,
                    'submenuAttributes' => $itemConfig['submenu']['attributes'] ?? null,
                    'matchRoutes' => $itemConfig['matchRoutes'] ?? [],
                    'ignoreQueryString' => $itemConfig['ignoreQueryString'] ?? null,
                    'fuzzy' => $itemConfig['fuzzy'] ?? null,
                    'displayChildren' => $itemConfig['displayChildren'] ?? true,
                    'labelAttributes' => $itemConfig['labelAttributes'] ?? [],
                    'linkAttributes' => $itemConfig['linkAttributes'] ?? [],
                    'external' => $itemConfig['external'] ?? false,
                    'expanded' => $itemConfig['expanded'] ?? false,
                ],
            );
            if (isset($itemConfig['submenu']) && is_array($itemConfig['submenu'])) {
                $item->setSubMenu(static::fromArray($itemConfig['submenu'], $itemClass));
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
     * @phpstan-param class-string<\CakeMenu\Item\ItemInterface>|null $itemClass
     *
     * @throws \InvalidArgumentException
     */
    public static function fromFlat(iterable $rows, Closure $mapper, ?string $itemClass = null): static
    {
        $menu = static::create();
        if ($itemClass !== null) {
            $menu->setItemClass($itemClass);
        }
        /** @var array<string, \CakeMenu\Item\ItemInterface> $byKey */
        $byKey = [];
        /** @var list<array{item: \CakeMenu\Item\ItemInterface, parent: string|null}> $pending */
        $pending = [];

        $specs = [];
        $seenKeys = [];
        foreach ($rows as $row) {
            $spec = $mapper($row);
            $key = isset($spec['key']) ? (string)$spec['key'] : '';
            if ($key !== '' && isset($seenKeys[$key])) {
                throw new InvalidArgumentException(sprintf('Duplicate flat menu key `%s` detected.', $key));
            }
            $seenKeys[$key] = true;
            $specs[] = $spec;
        }

        foreach ($specs as $spec) {
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
     * already appears somewhere in $item's subtree. Such an edge is dropped (fromFlat()) or
     * rejected (mutating attach operations) so the tree stays acyclic and finite.
     */
    protected static function wouldCycle(ItemInterface $item, ItemInterface $parent): bool
    {
        if ($item === $parent) {
            return true;
        }
        if (!$item->hasSubMenu()) {
            return false;
        }

        foreach ($item->getSubMenu()->getItems() as $child) {
            if (static::wouldCycle($child, $parent)) {
                return true;
            }
        }

        return false;
    }

    public function add(ItemInterface $item): static
    {
        $this->assertMutable();
        $this->assertAttachableItem($item);
        $this->assertUniqueItemTree($item);
        $this->synchronizeItemParent($item);
        $this->items[] = $item;
        $this->rootMenu()->registerItemTree($item);

        return $this;
    }

    /**
     * @phpstan-param array<mixed> $items
     *
     * @throws \InvalidArgumentException
     */
    public function addItems(array $items): static
    {
        $this->assertMutable();
        // Validate the entire batch up-front so a bad entry never leaves the menu half-mutated.
        foreach ($items as $item) {
            if (!$item instanceof ItemInterface) {
                throw new InvalidArgumentException('All menu items must implement ' . ItemInterface::class);
            }
        }
        // Pre-check id/key uniqueness against the current tree and within the batch itself, so a
        // duplicate fails before any mutation (avoiding partial parent-pointer rewriting from add()).
        $root = $this->rootMenu();
        $ids = $root->idIndex;
        $keys = $root->keyIndex;
        foreach ($items as $item) {
            $this->collectIdentifiers($item, $ids, $keys);
        }
        foreach ($items as $item) {
            $this->assertAttachableItem($item);
        }
        foreach ($items as $item) {
            $this->add($item);
        }

        return $this;
    }

    /**
     * @phpstan-param \CakeMenu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     * @phpstan-param array<string, mixed> $options
     */
    public function addItem(
        Stringable|string $label,
        LinkInterface|array|string|null $link = null,
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
    public function addHeader(Stringable|string $label, array $options = []): ItemInterface
    {
        $item = $this->newItem($label, null, $options);
        $item->setHeader();
        $this->add($item);

        return $item;
    }

    /**
     * @phpstan-param \CakeMenu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     * @phpstan-param array<string, mixed> $options
     */
    public function newItem(
        Stringable|string|null $label = null,
        LinkInterface|array|string|null $link = null,
        array $options = [],
    ): ItemInterface {
        // Trusted values become plain strings plus an escape flag, so custom item classes with
        // string-typed constructors keep working.
        if (TrustedHtml::isTrusted($label)) {
            $options += ['escape' => false];
        }
        foreach (['icon' => 'escapeIcon', 'badge' => 'escapeBadge'] as $field => $flag) {
            if (TrustedHtml::isTrusted($options[$field] ?? null)) {
                $options += [$flag => false];
            }
        }
        $className = $this->itemClass;
        $item = new $className($label instanceof Stringable ? (string)$label : $label, $link);

        if (isset($options['id'])) {
            $item->setId((string)$options['id']);
        }
        if (isset($options['key'])) {
            $item->setKey((string)$options['key']);
        }
        if (isset($options['escape'])) {
            if ($item->getLabel() !== null) {
                $item->setLabel($item->getLabel(), (bool)$options['escape']);
            }
        }
        if (isset($options['before'])) {
            $item->setBefore((string)$options['before']);
        }
        if (isset($options['after'])) {
            $item->setAfter((string)$options['after']);
        }
        if (isset($options['icon'])) {
            $item->setIcon((string)$options['icon'], (bool)($options['escapeIcon'] ?? true));
        }
        if (isset($options['badge'])) {
            $item->setBadge(
                (string)$options['badge'],
                isset($options['badgeType']) ? (string)$options['badgeType'] : null,
                (bool)($options['escapeBadge'] ?? true),
            );
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
            $item->setVisible((bool)$options['visible']);
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
            $item->getSubMenu()->setItemClass($this->itemClass)->setAttributes($options['submenuAttributes']);
        }
        if (isset($options['matchRoutes']) && is_array($options['matchRoutes'])) {
            $item->setMatchRoutes(array_values($options['matchRoutes']));
        }
        if (array_key_exists('ignoreQueryString', $options)) {
            $ignoreQueryString = $options['ignoreQueryString'];
            $item->setIgnoreQueryString(is_bool($ignoreQueryString) ? $ignoreQueryString : null);
        }
        if (array_key_exists('fuzzy', $options) && $options['fuzzy'] !== null) {
            $item->setFuzzy((bool)$options['fuzzy']);
        }
        if (array_key_exists('displayChildren', $options)) {
            $item->setDisplayChildren((bool)$options['displayChildren']);
        }
        if (isset($options['labelAttributes']) && is_array($options['labelAttributes'])) {
            $item->setLabelAttributes($options['labelAttributes']);
        }
        if ($item->getLink() !== null) {
            $attributes = (array)($options['linkAttributes'] ?? []);
            if (!empty($options['external'])) {
                $item->setLink(Link::create($item->getLink()->getRawUrl(), $attributes, true));
            } elseif ($attributes !== []) {
                $item->getLink()->setAttributes($attributes, true);
            }
        }
        if (!empty($options['expanded'])) {
            $item->setExpanded();
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
     * Deep-clone direct items while preserving their concrete classes.
     */
    public function __clone(): void
    {
        // A clone is detached from any source-tree owner; if the clone is itself the submenu of a
        // cloned Item, Item::__clone reattaches it via setOwnerItemDuringClone().
        $this->ownerItem = null;
        // A clone is a mutable working copy (the source's frozen flag tracked the source object);
        // re-freeze the clone explicitly when you want the structure locked again.
        $this->frozen = false;
        $this->items = array_map(static fn (ItemInterface $item): ItemInterface => clone $item, $this->items);
        foreach ($this->items as $item) {
            $item->setOwnerMenu($this);
        }
        $this->refreshIndex();
    }

    /**
     * @param \CakeMenu\ItemCollection $collection
     * @param list<\CakeMenu\Item\ItemInterface> $items
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

        foreach ($validatedItems as $item) {
            $this->assertAttachableItem($item);
        }
        $this->assertUniqueItems($validatedItems);
        $preserved = [];
        foreach ($validatedItems as $item) {
            $preserved[spl_object_id($item)] = true;
        }
        foreach ($this->items as $existingItem) {
            if (!isset($preserved[spl_object_id($existingItem)])) {
                $this->clearItemOwnership($existingItem);
            }
        }
        $this->items = $validatedItems;
        foreach ($this->items as $item) {
            $this->synchronizeItemParent($item);
        }
        $this->rootMenu()->refreshIndex();

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
        if (!$this->removeFirstById($id)) {
            throw new InvalidArgumentException(sprintf('Unknown menu item `%s`.', $id));
        }

        return $this;
    }

    /**
     * Removes the first item matching the id, searching this level before descending. Returns
     * whether an item was removed.
     */
    protected function removeFirstById(string $id): bool
    {
        foreach ($this->items as $index => $item) {
            if ($item->getId() === $id) {
                $this->clearItemOwnership($item);
                array_splice($this->items, $index, 1);
                $this->rootMenu()->refreshIndex();

                return true;
            }
        }
        foreach ($this->items as $item) {
            if ($item->hasSubMenu() && $item->getSubMenu()->get($id) !== null) {
                $item->getSubMenu()->remove($id);

                return true;
            }
        }

        return false;
    }

    public function getByKey(string $key): ?ItemInterface
    {
        return $this->findKey($key, true) ?? $this->findKey($key, false);
    }

    public function hasKey(string $key): bool
    {
        return $this->getByKey($key) !== null;
    }

    public function removeByKey(string $key): static
    {
        $this->assertMutable();
        if (!$this->removeFirstByKey($key)) {
            throw new InvalidArgumentException(sprintf('Unknown menu item key `%s`.', $key));
        }

        return $this;
    }

    /**
     * Removes the first item matching the key, searching this level before descending. Returns
     * whether an item was removed.
     */
    protected function removeFirstByKey(string $key): bool
    {
        $item = $this->getByKey($key);

        return $item !== null && $this->removeFirstById($item->getId());
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
        $incoming = $this->cloneItems($menu->getItems());
        $ids = $this->rootMenu()->idIndex;
        $keys = $this->rootMenu()->keyIndex;
        foreach ($incoming as $item) {
            $this->collectIdentifiers($item, $ids, $keys);
            $this->assertAttachableItem($item);
        }
        foreach ($incoming as $item) {
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

    public function slice(string|int $offset, string|int|null $length = null): static
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
        $new->setItemClass($this->itemClass);
        foreach ($this->data as $name => $value) {
            $new->setData((string)$name, $value);
        }
        $new->setItems($this->cloneItems($slice));

        return $new;
    }

    /**
     * @phpstan-return array{primary: static, secondary: static}
     */
    public function split(string|int $length): array
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
            if ($item->hasExplicitKey() && $item->getKey() === $idOrKey) {
                return $index;
            }
        }
        foreach ($this->items as $index => $item) {
            if (!$item->hasExplicitKey() && $item->getKey() === $idOrKey) {
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
    protected function resolveBoundary(string|int $value): int
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
        $this->assertUniqueItemTree($item);
        $this->synchronizeItemParent($item);
        $position = max(0, min($position, count($this->items)));
        array_splice($this->items, $position, 0, [$item]);
        $this->rootMenu()->registerItemTree($item);
    }

    /**
     * Deep-clones a set of items so derived menus from slice()/split()/merge() own independent item
     * objects and leave the source tree untouched.
     *
     * @param list<\CakeMenu\Item\ItemInterface> $items
     *
     * @return list<\CakeMenu\Item\ItemInterface>
     */
    protected function cloneItems(array $items): array
    {
        return array_map(static fn (ItemInterface $item): ItemInterface => clone $item, $items);
    }

    public function clearActive(): static
    {
        foreach ($this->items as $item) {
            $item->setRuntimeActive(false);

            if ($item->hasSubMenu()) {
                $item->getSubMenu()->clearActive();
            }
        }

        return $this;
    }

    public function getActiveItem(): ?ItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->hasSubMenu()) {
                $activeItem = $item->getSubMenu()->getActiveItem();
                if ($activeItem !== null) {
                    return $activeItem;
                }
            }
            if ($item->isActive()) {
                return $item;
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

    protected static function isItemClass(string $class): bool
    {
        return class_exists($class) && is_a($class, ItemInterface::class, true);
    }

    /**
     * @param class-string<\CakeMenu\Item\ItemInterface> $class
     *
     * @throws \InvalidArgumentException
     */
    public function setItemClass(string $class): static
    {
        $this->assertMutable();
        if (!self::isItemClass($class)) {
            throw new InvalidArgumentException('Item class must implement ItemInterface.');
        }
        $this->itemClass = $class;

        return $this;
    }

    /**
     * @return class-string<\CakeMenu\Item\ItemInterface>
     */
    public function getItemClass(): string
    {
        return $this->itemClass;
    }

    public function filter(Closure $callback): static
    {
        $this->assertMutable();
        $items = [];
        foreach ($this->items as $item) {
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->filter($callback);
            }
            if ($callback($item) !== false) {
                $items[] = $item;
            } else {
                $this->clearItemOwnership($item);
            }
        }

        $this->items = $items;
        $this->rootMenu()->refreshIndex();

        return $this;
    }

    public function find(Closure $callback): ItemCollection
    {
        $collection = new ItemCollection();
        $this->findInto($collection, $callback, $this->items);

        return $collection;
    }

    public function sortBy(Closure|string $by, SortDirection $direction = SortDirection::Asc): static
    {
        $this->assertMutable();
        usort($this->items, function (ItemInterface $left, ItemInterface $right) use ($by, $direction): int {
            $leftValue = $this->extractSortValue($left, $by);
            $rightValue = $this->extractSortValue($right, $by);

            $result = $leftValue <=> $rightValue;

            return $direction === SortDirection::Desc ? -$result : $result;
        });

        foreach ($this->items as $item) {
            if ($item->hasSubMenu()) {
                $item->getSubMenu()->sortBy($by, $direction);
            }
        }

        return $this;
    }

    public function resolve(ResolverInterface $resolver): static
    {
        $this->resolveItems($resolver, $this->items, 1, $this->ownerItem);

        return $this;
    }

    /**
     * @param \CakeMenu\Resolver\ResolverInterface $resolver
     * @param list<\CakeMenu\Item\ItemInterface> $items
     * @param \CakeMenu\Item\ItemInterface|null $parent
     * @param int $depth
     */
    protected function resolveItems(
        ResolverInterface $resolver,
        array $items,
        int $depth,
        ?ItemInterface $parent,
    ): void {
        $context = new ResolverContext($depth, $parent);
        foreach ($items as $item) {
            $resolver->resolve($item, $context);
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
     * @param list<\CakeMenu\Item\ItemInterface> $items
     */
    protected function assertUniqueItems(array $items): void
    {
        $root = $this->rootMenu();
        $ids = $root->idIndex;
        $keys = $root->keyIndex;
        foreach ($this->items as $existing) {
            $this->removeIdentifiers($existing, $ids, $keys);
        }
        foreach ($items as $item) {
            $this->collectIdentifiers($item, $ids, $keys);
        }
    }

    public function setOwnerItem(?ItemInterface $ownerItem): static
    {
        $this->assertMutable();
        $this->ownerItem = $ownerItem;
        foreach ($this->items as $item) {
            $this->synchronizeItemParent($item);
        }
        if ($ownerItem === null) {
            $this->refreshIndex();
        }

        return $this;
    }

    public function getOwnerItem(): ?ItemInterface
    {
        return $this->ownerItem;
    }

    protected function synchronizeItemParent(ItemInterface $item): void
    {
        $this->assertAttachableItem($item);
        $item->setParent($this->ownerItem);
        $item->setOwnerMenu($this);
        if ($item->hasSubMenu() && $item->getSubMenu() instanceof self) {
            $item->getSubMenu()->setOwnerItem($item);
        }
    }

    protected function assertAttachableItem(ItemInterface $item): void
    {
        $currentOwnerMenu = $item->getOwnerMenu();
        $currentParent = $item->getParent();
        $desiredParent = $this->ownerItem;
        if ($currentOwnerMenu === $this) {
            return;
        }
        if ($item->isFrozen()) {
            throw new LogicException('Cannot attach a frozen item to a menu tree.');
        }
        if ($currentOwnerMenu !== null || $currentParent !== null) {
            throw new LogicException(
                'Cannot attach an item that already belongs to a menu tree. Remove it first or clone it.',
            );
        }
        if ($desiredParent !== null && static::wouldCycle($item, $desiredParent)) {
            throw new LogicException('Cannot attach an item beneath one of its own descendants.');
        }
    }

    protected function clearItemParent(ItemInterface $item): void
    {
        $item->setParent(null);
    }

    protected function clearItemOwnership(ItemInterface $item): void
    {
        if ($item->hasSubMenu()) {
            foreach ($item->getSubMenu()->getItems() as $child) {
                $this->clearItemOwnership($child);
            }
            if ($item->getSubMenu() instanceof self) {
                $item->getSubMenu()->refreshIndex();
            }
        }
        $this->clearItemParent($item);
        $item->setOwnerMenu(null);
    }

    /**
     * @param \CakeMenu\ItemCollection $collection
     * @param \Closure(\CakeMenu\Item\ItemInterface): mixed $callback
     * @param list<\CakeMenu\Item\ItemInterface> $items
     */
    protected function findInto(ItemCollection $collection, Closure $callback, array $items): void
    {
        foreach ($items as $item) {
            if ($callback($item)) {
                $collection->add($item);
            }
            if ($item->hasSubMenu()) {
                $this->findInto($collection, $callback, $item->getSubMenu()->getItems());
            }
        }
    }

    public function resetState(): static
    {
        foreach ($this->items as $item) {
            $item->resetState();

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

    protected function extractSortValue(ItemInterface $item, Closure|string $by): mixed
    {
        if ($by instanceof Closure) {
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
        $root = $this->rootMenu();
        $ids = [];
        $keys = [];
        $this->collectIdentifiers($candidate, $ids, $keys);
        foreach (array_keys($ids) as $id) {
            if (isset($root->idIndex[$id])) {
                throw new InvalidArgumentException(sprintf('Duplicate menu item id `%s` detected.', $id));
            }
        }
        foreach (array_keys($keys) as $key) {
            if (isset($root->keyIndex[$key])) {
                throw new InvalidArgumentException(sprintf('Duplicate explicit menu item key `%s` detected.', $key));
            }
        }
    }

    /**
     * @param \CakeMenu\Item\ItemInterface $item
     * @param array<string, \CakeMenu\Item\ItemInterface> $ids
     * @param array<string, \CakeMenu\Item\ItemInterface> $keys
     *
     * @throws \InvalidArgumentException
     */
    protected function collectIdentifiers(ItemInterface $item, array &$ids, array &$keys): void
    {
        $id = $item->getId();
        if (isset($ids[$id])) {
            throw new InvalidArgumentException(sprintf('Duplicate menu item id `%s` detected.', $id));
        }
        $ids[$id] = $item;

        if ($item->hasExplicitKey()) {
            $key = $item->getKey();
            if (isset($keys[$key])) {
                throw new InvalidArgumentException(sprintf('Duplicate explicit menu item key `%s` detected.', $item->getKey()));
            }
            $keys[$key] = $item;
        }

        if ($item->hasSubMenu()) {
            foreach ($item->getSubMenu()->getItems() as $child) {
                $this->collectIdentifiers($child, $ids, $keys);
            }
        }
    }

    /**
     * @param \CakeMenu\Item\ItemInterface $item
     * @param array<string, \CakeMenu\Item\ItemInterface> $ids
     * @param array<string, \CakeMenu\Item\ItemInterface> $keys
     */
    protected function removeIdentifiers(ItemInterface $item, array &$ids, array &$keys): void
    {
        unset($ids[$item->getId()]);
        if ($item->hasExplicitKey()) {
            unset($keys[$item->getKey()]);
        }
        if ($item->hasSubMenu()) {
            foreach ($item->getSubMenu()->getItems() as $child) {
                $this->removeIdentifiers($child, $ids, $keys);
            }
        }
    }

    protected function rootMenu(): self
    {
        $menu = $this;
        while ($menu->ownerItem?->getOwnerMenu() instanceof self) {
            $menu = $menu->ownerItem->getOwnerMenu();
        }

        return $menu;
    }

    protected function registerItemTree(ItemInterface $item): void
    {
        $this->collectIdentifiers($item, $this->idIndex, $this->keyIndex);
    }

    /**
     * @internal Keeps the tree index in sync for Item; not part of the public API.
     */
    public function refreshTreeIndex(): void
    {
        $this->rootMenu()->refreshIndex();
    }

    protected function refreshIndex(): void
    {
        $ids = [];
        $keys = [];
        foreach ($this->items as $item) {
            $this->collectIdentifiers($item, $ids, $keys);
        }
        $this->idIndex = $ids;
        $this->keyIndex = $keys;
    }

    /**
     * @internal Keeps the tree index in sync for Item; not part of the public API.
     *
     * @throws \InvalidArgumentException
     */
    public function assertItemIdAvailable(ItemInterface $item, string $id): void
    {
        $existing = $this->rootMenu()->idIndex[$id] ?? null;
        if ($existing !== null && $existing !== $item) {
            throw new InvalidArgumentException(sprintf('Duplicate menu item id `%s` detected.', $id));
        }
    }

    /**
     * @internal Keeps the tree index in sync for Item; not part of the public API.
     *
     * @throws \InvalidArgumentException
     */
    public function assertItemKeyAvailable(ItemInterface $item, string $key): void
    {
        $existing = $this->rootMenu()->keyIndex[$key] ?? null;
        if ($existing !== null && $existing !== $item) {
            throw new InvalidArgumentException(sprintf('Duplicate explicit menu item key `%s` detected.', $key));
        }
    }

    /**
     * @internal Keeps the tree index in sync for Item; not part of the public API.
     */
    public function assertSubMenuReplacement(ItemInterface $owner, MenuInterface $next): void
    {
        $root = $this->rootMenu();
        $ids = $root->idIndex;
        $keys = $root->keyIndex;
        if ($owner->hasSubMenu()) {
            foreach ($owner->getSubMenu()->getItems() as $child) {
                $this->removeIdentifiers($child, $ids, $keys);
            }
        }
        foreach ($next->getItems() as $child) {
            $this->collectIdentifiers($child, $ids, $keys);
        }
    }

    protected function findKey(string $key, bool $explicit): ?ItemInterface
    {
        foreach ($this->items as $item) {
            if ($item->hasExplicitKey() === $explicit && $item->getKey() === $key) {
                return $item;
            }
            if (!$item->hasSubMenu()) {
                continue;
            }
            $subMenu = $item->getSubMenu();
            $found = $subMenu instanceof self ? $subMenu->findKey($key, $explicit) : $subMenu->getByKey($key);
            if ($found !== null && $found->hasExplicitKey() === $explicit) {
                return $found;
            }
        }

        return null;
    }

    protected function assertMutable(): void
    {
        if ($this->frozen) {
            throw new LogicException('Cannot mutate a frozen menu.');
        }
    }
}
