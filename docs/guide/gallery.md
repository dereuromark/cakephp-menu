---
description: See every cakephp-menu renderer side by side — the same menu rendered as a string template, Bootstrap 5 nav, navbar, sidebar, breadcrumb, and JSON, with real output.
---

# Renderer Gallery

Every renderer consumes the **same** resolved menu and produces different output. Pick the one that
matches your layout, or write your own (see [Extending](/guide/extending)).

::: tip Try it live
All renderers are wired up in the **[live sandbox](https://sandbox.dereuromark.de/menu-sandbox)** —
tweak a menu and see each renderer's output instantly.
:::

::: info
The HTML below is copied from the renderer test suite and pretty-printed for readability; the
renderers emit it without the extra whitespace.
:::

## String Template (default)

The dependency-free default. Plain, semantic `<ul>`/`<li>` markup with configurable classes — style
it however you like. Active items render as a `<span>` (not a link) when `currentAsLink` is `false`.

```php
$menu = Menu::create();
$parent = $menu->addItem('Articles', '/articles');
$parent->getSubMenu()->addItem('View', '/articles/view', ['active' => true]);

echo (new StringTemplateRenderer(['currentAsLink' => false]))->render($menu);
```

```html
<ul>
  <li class="active-ancestor has-children" aria-expanded="true">
    <a href="/articles">Articles</a>
    <ul class="submenu">
      <li class="active"><span aria-current="page">View</span></li>
    </ul>
  </li>
</ul>
```

## Bootstrap 5

Adds Bootstrap 5 dropdown classes (`nav-link`, `dropdown`, `dropdown-menu`, `dropdown-item`,
`data-bs-toggle`) so the menu drops into any Bootstrap layout. Wrap it in the [Navbar](#navbar)
renderer for the full responsive chrome.

```php
$menu = Menu::create(['class' => 'navbar-nav']);
$menu->addItem('Home', '/');
$parent = $menu->addItem('Account', '#');
$settings = $parent->getSubMenu()->addItem('Settings', '#');
$settings->getSubMenu()->addItem('Profile', '/profile', ['active' => true]);

echo (new Bootstrap5Renderer())->render($menu);
```

```html
<ul class="navbar-nav">
  <li><a href="/" class="nav-link">Home</a></li>
  <li class="active dropdown">
    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="true">Account</a>
    <ul class="dropdown-menu">
      <li class="active dropdown">
        <a href="#" class="dropdown-item dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="true">Settings</a>
        <ul class="dropdown-menu">
          <li class="active"><a href="/profile" class="dropdown-item" aria-current="page">Profile</a></li>
        </ul>
      </li>
    </ul>
  </li>
</ul>
```

## Navbar

A complete Bootstrap 5 `<nav class="navbar">` — brand, responsive toggler, and collapse wrapper
around the menu. Set `brand`, `brandUrl`, `expand`, `theme`, and a unique `collapseId` per navbar on
the page.

![Bootstrap 5 navbar rendered by cakephp-menu, with an open Account dropdown](/renderers/navbar.png)

```php
$menu = Menu::create();
$menu->addItem('Home', '/');
$account = $menu->addItem('Account', '#');
$account->getSubMenu()->addItem('Profile', '/profile');

echo (new NavbarRenderer())->render($menu, [
    'brand' => 'MyApp',
    'brandUrl' => '/',
    'collapseId' => 'navbarNav',
]);
```

```html
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    <a class="navbar-brand" href="/">MyApp</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li><a href="/" class="nav-link">Home</a></li>
        <li class="dropdown">
          <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="false">Account</a>
          <ul class="dropdown-menu">
            <li><a href="/profile" class="dropdown-item">Profile</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
```

## Sidebar (collapsible)

A vertical Bootstrap 5 sidebar where branches collapse/expand. The branch containing the active item
is expanded automatically (`collapse show`) with an open caret; others stay collapsed. Give each
branch a unique `id` so the collapse targets are unique.

![Collapsible Bootstrap 5 sidebar rendered by cakephp-menu, with the active branch expanded](/renderers/sidebar.png)

```php
$menu = Menu::create();
$menu->addItem('Dashboard', '/dashboard');
$features = $menu->addItem('Features', '#', ['id' => 'features']);
$features->getSubMenu()->addItem('Resolvers', '/resolvers');
$features->getSubMenu()->addItem('Renderers', '/renderers', ['active' => true]);
$more = $menu->addItem('More', '#', ['id' => 'more']);
$more->getSubMenu()->addItem('Docs', '/docs');

echo (new Bootstrap5SidebarRenderer())->render($menu);
```

```html
<ul class="nav flex-column">
  <li class="nav-item"><a class="nav-link" href="/dashboard">Dashboard</a></li>
  <li class="nav-item">
    <a class="nav-link d-flex justify-content-between align-items-center active" data-bs-toggle="collapse" role="button" href="#menu-collapse-features" aria-controls="menu-collapse-features" aria-expanded="true">Features<span class="menu-caret ms-2" aria-hidden="true">▾</span></a>
    <div class="collapse show" id="menu-collapse-features">
      <ul class="nav flex-column ms-3">
        <li class="nav-item"><a class="nav-link" href="/resolvers">Resolvers</a></li>
        <li class="nav-item"><a class="nav-link active" aria-current="page" href="/renderers">Renderers</a></li>
      </ul>
    </div>
  </li>
  <li class="nav-item">
    <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" role="button" href="#menu-collapse-more" aria-controls="menu-collapse-more" aria-expanded="false">More<span class="menu-caret ms-2" aria-hidden="true">▸</span></a>
    <div class="collapse" id="menu-collapse-more">
      <ul class="nav flex-column ms-3">
        <li class="nav-item"><a class="nav-link" href="/docs">Docs</a></li>
      </ul>
    </div>
  </li>
</ul>
```

## Breadcrumb

Renders only the path from the root to the active item as a Bootstrap 5 breadcrumb. The active
(last) crumb is a `<span>`, the rest are links. Also available via the helper as
`$this->Menu->renderBreadcrumbs()`.

![Bootstrap 5 breadcrumb rendered by cakephp-menu showing Articles / View](/renderers/breadcrumb.png)

```php
$menu = Menu::create();
$parent = $menu->addItem('Articles', '/articles');
$parent->getSubMenu()->addItem('View', '/articles/view', ['active' => true]);

echo (new BreadcrumbRenderer())->render($menu);
```

```html
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/articles">Articles</a></li>
    <li class="breadcrumb-item active"><span aria-current="page">View</span></li>
  </ol>
</nav>
```

## JSON

Serializes the resolved menu — handy for SPAs, a JS-driven menu, or debugging active state. Pass
`['pretty' => true]` for human-readable output. Every item carries the full property set (shown in
full for `Home` below); nested items are abbreviated here for brevity.

```php
$menu = Menu::create(['class' => 'nav']);
$menu->addItem('Home', '/');
$articles = $menu->addItem('Articles', '/articles', ['id' => 'articles']);
$articles->getSubMenu()->addItem('View', '/articles/view', ['active' => true]);

echo (new JsonRenderer())->render($menu, ['pretty' => true]);
```

```json
{
  "attributes": { "class": "nav" },
  "data": [],
  "items": [
    {
      "id": "menu-item-…",
      "key": null,
      "label": "Home",
      "escape": true,
      "link": "/",
      "linkAttributes": [],
      "external": false,
      "raw": null,
      "divider": false,
      "header": false,
      "visible": true,
      "active": false,
      "expanded": false,
      "before": "",
      "after": "",
      "icon": null,
      "badge": null,
      "badgeType": null,
      "attributes": [],
      "data": [],
      "matchRoutes": [],
      "ignoreQueryString": null,
      "fuzzy": false,
      "submenu": null
    },
    {
      "id": "articles",
      "label": "Articles",
      "link": "/articles",
      "active": false,
      "submenu": {
        "attributes": [],
        "data": [],
        "items": [
          { "id": "menu-item-…", "label": "View", "link": "/articles/view", "active": true, "submenu": null }
        ]
      }
    }
  ]
}
```

---

See the full option list for each renderer in the [Renderer Options reference](/reference/renderer-options).
