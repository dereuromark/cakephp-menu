---
description: Render cakephp-menu menus with the string-template, Bootstrap 5, navbar, sidebar, breadcrumb, and JSON renderers, and customize their templates and options.
---

# Rendering

## Helper Rendering

```php
echo $this->Menu->render($menu);
```

You can also fetch the resolved active item and extract its path:

```php
$current = $this->Menu->getCurrentItem('main');
$path = $current ? $this->Menu->extractPath($current) : [];
```

### Hiding Empty Branches

When a resolver hides all of a branch's children (for example access filtering), the parent would
otherwise render as an empty dropdown. Enable `hideEmptyBranches` to skip any branch with no
visible, renderable descendants:

```php
echo $this->Menu->render('main', [
    'hideEmptyBranches' => true,
]);
```

This looks at child *visibility*, not the `depth` cutoff: a branch with visible children is still a
valid top-level entry when its submenu is truncated by `depth`, so it is kept.

### Single Active Item

When several items match the current URL (e.g. a parent and a child both pointing at the same
route), `getActiveItem()` and breadcrumbs follow the first match in document order. Enable
`singleActive` to keep only the **best** match active — the deepest *visible* item that actually
renders (items hidden, or under hidden ancestors, are skipped), breaking ties by document order — so
the active trail is unambiguous:

```php
echo $this->Menu->render('main', [
    'singleActive' => true,
]);
```

### Breadcrumb Integration

```php
$crumbs = $this->Menu->getBreadcrumbs('main');
$this->Menu->populateBreadcrumbs('main');

echo $this->Breadcrumbs->render();
```

Or use the built-in breadcrumb renderer:

```php
echo $this->Menu->renderBreadcrumbs('main', [
    'renderer' => \Menu\Renderer\BreadcrumbRenderer::class,
]);
```

### Alternate Renderers

JSON export:

```php
echo $this->Menu->render($menu, [
    'renderer' => \Menu\Renderer\JsonRenderer::class,
    'pretty' => true,
]);
```

Bootstrap-flavored markup:

```php
echo $this->Menu->render($menu, [
    'renderer' => \Menu\Renderer\Bootstrap5Renderer::class,
]);
```

Collapsible Bootstrap 5 sidebar — a vertical `nav` whose branches are Bootstrap `collapse` regions:

```php
echo $this->Menu->render('sidebar', [
    'renderer' => \Menu\Renderer\Bootstrap5SidebarRenderer::class,
]);
```

The branch containing the active item is expanded (`collapse show`, `aria-expanded="true"`), all
other branches start collapsed, and the active leaf gets the active class plus `aria-current="page"`.
Each branch is wired to its `collapse` element through a unique id, so it works with the standard
Bootstrap bundle and needs no custom JavaScript. Item and submenu attributes from the menu
definition are preserved on the `<li>` and nested `<ul>`.

Full Bootstrap 5 navbar — the complete `<nav>` chrome (brand, responsive toggler, and the
collapsible `navbar-nav` with dropdowns), rather than just the inner `<ul>`:

```php
echo $this->Menu->render('main', [
    'renderer' => \Menu\Renderer\NavbarRenderer::class,
    'brand' => 'MyApp',
    'brandUrl' => '/',
    'expand' => 'lg',                 // navbar-expand-lg (collapse breakpoint)
    'theme' => 'bg-body-tertiary',
    'containerClass' => 'container-fluid',
    'collapseId' => 'navbarNav',      // set per navbar if you have more than one on a page
]);
```

`NavbarRenderer` generates the toggler/collapse wiring (matching `data-bs-target`, `id`, and
`aria-controls`) for you. For just the `<ul class="navbar-nav">` to drop inside your own navbar
markup, use `Bootstrap5Renderer` directly.

A branch that only groups children (placeholder link `#`/none) renders a single toggle. A branch
that *also* has a real URL stays navigable: it renders the link plus a separate collapse toggle
button (`data-bs-target`), so the destination is reachable and its link attributes are kept.

Besides the shared `activeClass`, `currentAsLink`, `addAriaCurrent` and `hideEmptyBranches` options,
the sidebar exposes framework-specific keys (`idPrefix`, `navClass`, `toggleClass`, `collapseClass`,
`expandedClass`, `toggleAttribute`, `caret`, …). They all default to Bootstrap 5 and can be
overridden to target Bootstrap 4 or another setup without subclassing — for example
`toggleAttribute => 'data-toggle'`, `expandedClass => 'is-open'`.

::: tip
See the complete list with defaults in the
[Renderer Options reference](/reference/renderer-options#bootstrap5sidebarrenderer).
:::

You can override templates per render call:

```php
echo $this->Menu->render($menu, [
    'templates' => [
        'menuWrapper' => '<nav><ul{{attributes}}>{{items}}</ul></nav>',
    ],
]);
```

## Renderer Options

Every renderer option — class names, ARIA flags, depth limits, and the Bootstrap-specific keys — is
documented with its default value in the **[Renderer Options reference](/reference/renderer-options)**.
Pass options per render call, as constructor config, or via `setConfig()`.

`Bootstrap5Renderer` overrides some `StringTemplateRenderer` defaults (e.g. `ancestorClass` →
`active`, `branchClass` → `dropdown`, `nestedMenuClass` → `dropdown-menu`) and adds link-level keys
(`linkClass`, `childLinkClass`, `toggleClass`, `toggleAttribute`, `toggleValue`) so Bootstrap 4 or
other setups work without subclassing.

## Good to know

::: warning Escaping
Item labels are escaped by default. `before`, `after`, `raw`, and the icon/badge markup are treated
as **trusted** — escape or cast dynamic values yourself.
:::

::: info State is restored automatically
Each helper render (or request-state lookup) applies resolvers temporarily and restores the original
`active`, `visible`, and `expanded` item state afterward — so a registered menu renders safely many
times per request. Custom item classes should extend `Menu\Item\Item` or implement
`Menu\Item\StateResetInterface` for `Menu::resetState()` to restore their runtime defaults.
:::

- String URLs and array URLs are both supported; active matching is automatic and uses both array and
  string resolvers.
- The default renderer emits `aria-current="page"` for the active item (link or label) and
  `aria-expanded` for branch items.

