---
description: The Menu helper's methods and the options accepted by render(), getBreadcrumbs(), and create()/register() in cakephp-menu.
---

# Helper & Render Options

The `Menu` helper builds, resolves, and renders named menus. Load it in `AppView`:

```php
$this->loadHelper('Menu.Menu');
```

## Methods

| Method | Purpose |
|--------|---------|
| `create(string $name, array $options = [])` | Create a new named menu. |
| `register(string $name, callable $cb, array $options = [])` | Idempotently build a named menu via a callback. |
| `getOrCreate(string $name, array $options = [])` | Return an existing menu or create it. |
| `has(string $name)` | Whether a named menu exists. |
| `get(string $name)` | Return a named menu (throws if missing). |
| `remove(string $name)` / `reset()` | Drop one / all named menus. |
| `render($menu = null, array $options = [])` | Resolve and render a menu (by name, instance, or the last-created). |
| `getCurrentItem($menu = null, array $options = [])` | The deepest active item after resolution. |
| `extractPath(ItemInterface $item)` | Root-to-item path (for breadcrumbs). |
| `getBreadcrumbs($menu = null, array $options = [])` | Active path as an array of crumbs. |
| `populateBreadcrumbs($menu = null, array $options = [])` | Push crumbs into Cake's `Breadcrumbs` helper. |
| `renderBreadcrumbs($menu = null, $options = [], $attributes = [], $separator = [])` | Render breadcrumbs (via the `BreadcrumbRenderer` or Cake's helper). |

## render() options

These come from the helper's defaults and may be overridden per call. Unrecognized keys are passed
straight through to the renderer as [renderer options](/reference/renderer-options).

| Option | Default | Description |
|--------|---------|-------------|
| `renderer` | `StringTemplateRenderer::class` | Renderer class name or instance. |
| `resolve` | `true` | Set `false` to render without resolving active state. |
| `resolver` | (built-in URL resolvers) | A `ResolverInterface`/`ResolverCollectionInterface` that **replaces** the defaults. |
| `additionalResolvers` | `[]` | Extra `ResolverInterface`s appended **after** the defaults (keeps active-state matching). |
| `singleActive` | `false` | Keep only the deepest active item active (best-match arbitration). |
| `fuzzy` | `true` | Fuzzy (prefix) matching for the default `UrlArrayResolver`. |
| `ignoreQueryString` | `true` | Ignore the query string in the default `Psr7UrlResolver`. |
| `resolveDepth` | `null` | Max depth the default URL resolvers scan (`null` = unlimited). |
| `currentAsLink` | `true` | Passed through: render the active item as a link. |

::: info Option precedence
Options merge in this order (later wins): **helper config** → **create()/register() options** →
**this call's `$options`**. So defaults set at `create()` time apply to every later `render()` unless
overridden in the call.
:::

::: warning `resolver` vs `additionalResolvers`
`resolver` replaces the built-in URL resolvers (you lose automatic active-state matching). To keep
them and add your own, use `additionalResolvers`. See [Resolvers](/guide/resolvers#adding-resolvers-to-the-defaults).
:::

## create() / register() options

| Option | Description |
|--------|-------------|
| `attributes` / `menuAttributes` | HTML attributes for the root `<ul>`. |
| `overwrite` | Allow `create()` to replace an existing menu of the same name. |
| `rebuild` | Make `register()` rebuild even if the menu already exists. |
| `cache` | Cache the built structure (see below). `true`, a string key, or `['key' => ..., 'config' => ...]`. |

Any other keys (e.g. `renderer`, `singleActive`) set here become defaults for later `render()` calls
on that menu.

### Caching a built menu

`register()` with a `cache` option builds the menu through the callback once and caches its
**structure** (not its active state); later requests skip the build and load the tree from cache,
while active state is always resolved fresh per request. Pass `rebuild => true` to refresh.

```php
$this->Menu->register('main', function ($menu): void {
    // ...expensive build, e.g. a DB/ACL query...
}, ['cache' => 'menu_main']); // or ['cache' => true] (keyed by menu name), or ['key' => ..., 'config' => ...]
```

::: warning
The cached form is the serialized array, so custom item classes are restored as the base item class.
Cache data-driven menus, not menus that rely on custom `ItemInterface` implementations.
:::

## Config-defined menus

The helper auto-registers menus declared in `Configure::read('Menu.menus')` (each value a
`Menu::fromArray()` spec keyed by name), so they render without any wiring:

```php
// config/app.php (or a dedicated config/menu.php loaded via Configure::load)
'Menu' => [
    'menus' => [
        'main' => [
            'attributes' => ['class' => 'nav'],
            'items' => [
                ['label' => 'Home', 'link' => '/'],
                ['label' => 'Articles', 'link' => ['controller' => 'Articles', 'action' => 'index']],
            ],
        ],
    ],
],
```

```php
echo $this->Menu->render('main'); // no create()/register() needed
```

An explicit `create()`/`register()` of the same name overrides the configured menu entirely.

## Breadcrumb options

| Option | Default | Description |
|--------|---------|-------------|
| `linkCurrent` | `false` | Link the current (last) crumb too. |
| `resetBreadcrumbs` | `true` | Reset Cake's `Breadcrumbs` helper before populating. |
| `renderer` | — | Set to `BreadcrumbRenderer::class` to render directly instead of via Cake's helper. |

Per-item breadcrumb attributes can be set via the item's `data['breadcrumbOptions']`.
