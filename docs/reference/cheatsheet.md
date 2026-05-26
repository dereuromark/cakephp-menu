---
description: A condensed reference of the cakephp-menu public API — Menu, Item, Link, and ItemCollection methods at a glance.
---

# API Cheat Sheet

The public methods of the core classes. See [Helper & Render Options](/reference/helper-options) for
the `Menu` helper.

## Menu

Factories, building, querying, and lifecycle. (`MenuInterface`)

```php
Menu::create(array $attributes = []): static
Menu::fromArray(array $config): static          // build from a nested config array
Menu::fromFlat(iterable $rows, Closure $mapper): static  // build a tree from flat rows
```

| Method | Purpose |
|--------|---------|
| `add(ItemInterface $item)` | Add an existing item. |
| `addItem(string $label, $link = null, array $options = [])` | Create and add an item. |
| `addRaw(string $html, array $options = [])` | Add a raw-HTML item. |
| `addDivider(array $options = [])` | Add a divider. |
| `addHeader(string $label, array $options = [])` | Add a non-link section header. |
| `newItem(?string $label = null, $link = null, array $options = [])` | Build an item without adding it. |
| `getItems()` / `setItems(array)` | Root items as an array. |
| `collect()` | All items (root + descendants) as an `ItemCollection`. |
| `get(string $id)` / `has(string $id)` / `remove(string $id)` | Find/check/remove by id (recursive). |
| `getActiveItem()` | Deepest active item. |
| `clearActive()` | Deactivate all items. |
| `getAttributes()` / `setAttribute()` / `setAttributes()` | Root HTML attributes. |
| `getData()` / `setData()` | Menu-level metadata. |
| `filter(callable)` | Keep only matching items. |
| `sortBy(callable\|string $by, string $direction = Menu::SORT_ASC)` | Sort items. |
| `resolve(ResolverInterface\|ResolverCollectionInterface)` | Apply a resolver. |
| `resetState()` | Reset active/visible/expanded to defaults. |
| `freeze()` / `isFrozen()` | Make immutable / check. |
| `toArray()` | Serialize (round-trips with `fromArray()`). |

## Item

A single entry. (`ItemInterface`)

| Group | Methods |
|-------|---------|
| Identity | `setId()` / `getId()`, `setKey()` / `getKey()`, `hasExplicitKey()` |
| Label | `setLabel(string, bool $escape = true)` / `getLabel()`, `shouldEscapeLabel()` |
| Link | `setLink()` / `getLink()` |
| Content | `setRaw()` / `getRaw()` / `isRaw()`, `setBefore()` / `getBefore()`, `setAfter()` / `getAfter()` |
| Type | `setDivider()` / `isDivider()`, `setHeader()` / `isHeader()` |
| Icon/badge | `setIcon()` / `getIcon()`, `setBadge($badge, $type)` / `getBadge()` / `getBadgeType()` |
| State | `setActive()` / `isActive()`, `setVisibility()` / `isVisible()`, `setExpanded()` / `isExpanded()` |
| Submenu | `add()`, `setSubMenu()` / `getSubMenu()` / `hasSubMenu()` |
| Tree | `setParent()` / `getParent()` / `hasParent()` / `getParentId()` |
| Matching | `setMatchRoutes()` / `addMatchRoute()` / `getMatchRoutes()`, `setIgnoreQueryString()` / `getIgnoreQueryString()`, `setFuzzyMatch()` / `isFuzzyMatch()` |
| Attributes/data | `setAttribute()` / `setAttributes()` / `getAttributes()`, `setData()` / `getData()` |
| Lifecycle | `freeze()` / `isFrozen()`, `resetState()`, `toArray()` |
| Runtime (resolver-safe) | `setRuntimeActive()`, `setRuntimeVisibility()`, `setRuntimeExpanded()` |

## Link

The URL of an item. (`LinkInterface`)

```php
Link::create(string|array|null $url = null, array $attributes = [], bool $external = false): static
```

| Method | Purpose |
|--------|---------|
| `setUrl($url, bool $external = false)` | Set the URL (string/array) and external flag. |
| `getRawUrl()` | The raw URL (string, array, or null). |
| `getUrl()` | The resolved URL string (via the Router). |
| `isExternal()` | Whether the link is external. |
| `setAttribute()` / `setAttributes()` / `getAttributes()` | Link HTML attributes. |

## ItemCollection

A flat collection returned by `Menu::collect()`. Iterable and countable.

| Method | Purpose |
|--------|---------|
| `add()` / `addMany()` | Add item(s). |
| `all()` | Items as an array. |
| `findById(string $id)` | Find by id. |
| `findByKey(string $key)` | Find by key. |
| `findByParent(string\|ItemInterface $parent)` | Direct children of a parent. |
| `count()` | Number of items. |
