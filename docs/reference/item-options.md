---
description: Every option key accepted by addItem()/newItem() in cakephp-menu, with defaults and the fluent ItemInterface equivalents.
---

# Item Options

The third argument to `addItem()` / `newItem()` (and each entry's keys in `Menu::fromArray()`) is an
options array. Every key is optional.

```php
$menu->addItem('Inbox', ['controller' => 'Messages', 'action' => 'index'], [
    'icon' => 'fa fa-inbox',
    'badge' => $unread,
    'badgeType' => 'bg-danger',
    'data' => ['section' => ['controller' => 'Messages']],
]);
```

## Options

| Key | Type | Default | Meaning |
|-----|------|---------|---------|
| `id` | `string` | auto UUID | Unique item identifier. |
| `key` | `string` | slug of label | Explicit keys take precedence in lookup; an unset key falls back to the label slug. |
| `label` | `string` | (from arg) | Item text. |
| `escape` | `bool` | `true` | HTML-escape the label. |
| `link` | `string\|array\|LinkInterface\|null` | `null` | URL; string, CakePHP route array, or `LinkInterface`. |
| `linkAttributes` | `array` | `[]` | HTML attributes on the `<a>`. |
| `external` | `bool` | `false` | Mark the link external (skips routing). |
| `before` | `string` | `''` | Trusted markup before the label. |
| `after` | `string` | `''` | Trusted markup after the label. |
| `icon` | `string\|null` | `null` | Icon class, escaped before insertion into `iconTemplate`. |
| `escapeIcon` | `bool` | `true` | Set `false` to insert trusted icon markup as-is, e.g. `'<i class="bi bi-house"></i>'`. Same as `setIcon($icon, false)`. |
| `badge` | `string\|int\|null` | `null` | Badge text, HTML-escaped by default. |
| `escapeBadge` | `bool` | `true` | Set `false` to insert trusted badge markup as-is. Same as `setBadge($badge, $type, false)`. |
| `badgeType` | `string\|null` | `null` | Badge CSS class/type. |
| `attributes` | `array` | `[]` | HTML attributes on the `<li>`. |
| `data` | `array` | `[]` | Arbitrary metadata (read by resolvers, e.g. `section`, `permission`, `roles`). |
| `visible` | `bool` | `true` | Initial visibility. |
| `active` | `bool` | `false` | Initial active state. |
| `raw` | `string\|null` | `null` | Custom HTML content (bypasses the label/link). |
| `divider` | `bool` | `false` | Render as a divider only. |
| `header` | `bool` | `false` | Render as a non-link section header. |
| `submenuAttributes` | `array` | `[]` | HTML attributes on the submenu `<ul>`. |
| `matchRoutes` | `array` | `[]` | Extra route patterns that mark the item active. |
| `ignoreQueryString` | `bool\|null` | `null` | Ignore the query string when matching (overrides the resolver default). |
| `fuzzy` | `bool\|null` | `null` | Fuzzy (prefix) route matching for this item. `null` inherits the resolver's setting, which defaults to `true`; `true` or `false` overrides it. |
| `expanded` | `bool` | `false` | Show the submenu expanded by default. |
| `displayChildren` | `bool` | `true` | When `false`, render the item but not its submenu (treated as a leaf). |
| `labelAttributes` | `array` | `[]` | HTML attributes on the rendered link/label element (classes merge). |

::: warning Trusted markup
`before`, `after`, and `raw` are emitted **as-is**. Icon and badge values are escaped by default;
their `Item` setters can opt out for trusted markup. Cast or escape dynamic values in raw fields
yourself (e.g. `(int)$count`). The label is escaped unless `escape` is `false`.
:::

## Fluent equivalents

Anything you can pass as an option has a setter on `ItemInterface`, so you can build incrementally:

```php
$item = $menu->addItem('Profile', '/profile')
    ->setIcon('fa fa-user')
    ->setBadge('new', 'bg-success')
    ->setData('roles', ['admin'])
    ->setFuzzy(true)
    ->setExpanded();
```

| Option | Setter |
|--------|--------|
| `id` | `setId()` |
| `key` | `setKey()` |
| `label` / `escape` | `setLabel($label, $escape)` |
| `link` / `external` | `setLink()` (external via `Link::create($url, [], true)`) |
| `before` / `after` | `setBefore()` / `setAfter()` |
| `icon` | `setIcon($icon, $escape)` |
| `badge` / `badgeType` | `setBadge($badge, $type, $escape)` |
| `attributes` | `setAttribute()` / `setAttributes()` |
| `data` | `setData($name, $value)` |
| `visible` | `setVisible()` |
| `active` | `setActive()` |
| `raw` | `setRaw()` |
| `divider` | `setDivider()` |
| `header` | `setHeader()` |
| `matchRoutes` | `setMatchRoutes()` / `addMatchRoute()` |
| `ignoreQueryString` | `setIgnoreQueryString()` |
| `fuzzy` | `setFuzzy(true)` |
| `expanded` | `setExpanded()` |
| `displayChildren` | `setDisplayChildren()` |
| `labelAttributes` | `setLabelAttributes()` |

See the full method list in the [API Cheat Sheet](/reference/cheatsheet#item).

`setVisible()`, `setActive()`, and `setExpanded()` change authoring defaults. Runtime
setters override those defaults until `resetState()`. `toArray()` serializes defaults.

`setFuzzy(?bool)` and `getFuzzy(): ?bool` use `null` to inherit the resolver setting,
`true` for fuzzy matching, and `false` for exact matching. The option key remains `fuzzy`.
