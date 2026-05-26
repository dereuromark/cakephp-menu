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
| `key` | `string` | slug of label | Lookup key (auto-slugified from the label if unset). |
| `label` | `string` | (from arg) | Item text. |
| `escape` | `bool` | `true` | HTML-escape the label. |
| `link` | `string\|array\|LinkInterface\|null` | `null` | URL — string, CakePHP route array, or `LinkInterface`. |
| `linkAttributes` | `array` | `[]` | HTML attributes on the `<a>`. |
| `external` | `bool` | `false` | Mark the link external (skips routing). |
| `before` | `string` | `''` | Trusted markup before the label. |
| `after` | `string` | `''` | Trusted markup after the label. |
| `icon` | `string\|null` | `null` | Icon class or markup. |
| `badge` | `string\|int\|null` | `null` | Badge text. |
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
| `fuzzy` | `bool` | `false` | Enable fuzzy (prefix) route matching for this item. |
| `expanded` | `bool` | `false` | Show the submenu expanded by default. |

::: warning Trusted markup
`before`, `after`, `raw`, and the icon/badge markup are emitted **as-is** — they are not escaped.
Cast or escape any dynamic value you put there yourself (e.g. `(int)$count`). The `label` is escaped
unless `escape` is `false`.
:::

## Fluent equivalents

Anything you can pass as an option has a setter on `ItemInterface`, so you can build incrementally:

```php
$item = $menu->addItem('Profile', '/profile')
    ->setIcon('fa fa-user')
    ->setBadge('new', 'bg-success')
    ->setData('roles', ['admin'])
    ->setFuzzyMatch()
    ->setExpanded();
```

| Option | Setter |
|--------|--------|
| `id` | `setId()` |
| `key` | `setKey()` |
| `label` / `escape` | `setLabel($label, $escape)` |
| `link` / `external` | `setLink()` (external via `Link::create($url, [], true)`) |
| `before` / `after` | `setBefore()` / `setAfter()` |
| `icon` | `setIcon()` |
| `badge` / `badgeType` | `setBadge($badge, $type)` |
| `attributes` | `setAttribute()` / `setAttributes()` |
| `data` | `setData($name, $value)` |
| `visible` | `setVisibility()` |
| `active` | `setActive()` |
| `raw` | `setRaw()` |
| `divider` | `setDivider()` |
| `header` | `setHeader()` |
| `matchRoutes` | `setMatchRoutes()` / `addMatchRoute()` |
| `ignoreQueryString` | `setIgnoreQueryString()` |
| `fuzzy` | `setFuzzyMatch()` |
| `expanded` | `setExpanded()` |

See the full method list in the [API Cheat Sheet](/reference/cheatsheet#item).
