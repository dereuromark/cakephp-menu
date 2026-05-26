# Getting Started

`dereuromark/cakephp-menu` is a composable menu builder and renderer for CakePHP applications. It has
**no runtime dependencies** beyond CakePHP itself, and cleanly separates three concerns: *building* a
menu tree, *resolving* per-request state (active item, visibility) onto it, and *rendering* it to HTML
(or JSON).

This is for **CakePHP 5.3+** and **PHP 8.2+**.

::: tip See it in action
Prefer to explore first? The **[live sandbox](https://sandbox.dereuromark.de/menu-sandbox)** lets you
build a menu and preview every renderer. New to the plugin? Read [Concepts](/guide/concepts) for the
mental model behind it.
:::

## Installation

```bash
composer require dereuromark/cakephp-menu
```

Load the plugin:

```bash
bin/cake plugin load Menu
```

Load the helper in your `AppView`:

```php
use Cake\View\View;

class AppView extends View
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadHelper('Menu.Menu');
    }
}
```

## Quick Start

Build a menu and render it from a template:

```php
use Menu\Menu;

$menu = Menu::create(['class' => 'nav']);
$menu->addItem('Dashboard', ['controller' => 'Dashboard', 'action' => 'index']);

$account = $menu->addItem('Account', '#');
$account->getSubMenu()->addItem('Profile', ['controller' => 'Users', 'action' => 'profile']);
$account->getSubMenu()->addItem('Logout', ['controller' => 'Users', 'action' => 'logout']);

echo $this->Menu->render($menu);
```

The helper resolves the active item automatically and restores the menu's original state after each
render.

## Where to next

- [Concepts](/guide/concepts) — the build → resolve → render pipeline and the core types.
- [Building Menus](/guide/building) — items, nesting, headers, icons/badges, import/export, database-backed menus.
- [Resolvers & Active State](/guide/resolvers) — how the active item is matched, and the available resolvers.
- [Rendering](/guide/rendering) — the helper, renderer options, and the bundled renderers.
- [Renderer Gallery](/guide/gallery) — every bundled renderer with real output.
- [Recipes](/guide/recipes) — role-based menus (TinyAuth), caching, breadcrumbs, and more.
- [Extending](/guide/extending) — custom renderers and resolvers, plus testing tips.
- [FAQ & Troubleshooting](/guide/faq) — common active-state and URL gotchas.
- [Reference](/reference/renderer-options) — every renderer/item/helper option and an API cheat sheet.
