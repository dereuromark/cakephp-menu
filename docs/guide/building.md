---
description: Build composable menu trees in cakephp-menu — nested items, string and array URLs, dividers, section headers, icons, badges, raw HTML, and import/export.
---

# Building Menus

## Creating Menus

Use `Menu::create()` for the root menu and `addItem()` for the common case:

```php
use Menu\Menu;

$menu = Menu::create(['class' => 'nav nav-pills']);
$menu->addItem('Home', '/');
$menu->addItem('Docs', 'https://book.cakephp.org', [
    'attributes' => ['target' => '_blank', 'rel' => 'noopener'],
]);

$menu->addItems([
    $menu->newItem('About', '/about'),
    $menu->newItem('Contact', '/contact'),
]);
```

A link can be a **string URL** or a **CakePHP route array** — use whichever fits:

::: code-group

```php [Array URL (recommended)]
$menu->addItem('Articles', ['controller' => 'Articles', 'action' => 'index']);
```

```php [String URL]
$menu->addItem('Articles', '/articles');
```

```php [External]
$menu->addItem('Docs', 'https://book.cakephp.org', [
    'external' => true,
    'attributes' => ['target' => '_blank', 'rel' => 'noopener'],
]);
```

:::

::: tip
Prefer array URLs: the Router resolves base paths, plugins, and prefixes for you, which also makes
[active matching](/guide/resolvers) reliable in subdirectory installs.
:::

### Nested Menus

Each item can own a submenu:

```php
$products = $menu->addItem('Products', '#');
$products->getSubMenu()->setAttributes(['class' => 'submenu']);
$products->add($menu->newItem('Books', ['controller' => 'Products', 'action' => 'books']));
$products->add($menu->newItem('Games', ['controller' => 'Products', 'action' => 'games']));
```

### Section Headers and Dividers

Group items under a non-link header, and separate groups with a divider:

```php
$menu->addHeader('Account');
$menu->addItem('Profile', '/profile');
$menu->addItem('Logout', '/logout');
$menu->addDivider();
$menu->addHeader('Admin');
$menu->addItem('Users', ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index']);
```

A header renders as a plain `<li>` with the `headerClass` (`menu-header`, or `nav-header` in the
sidebar renderer), not a link.

### Named Menus via Helper

```php
$menu = $this->Menu->create('main', [
    'menuAttributes' => ['class' => 'nav nav-pills'],
]);
$menu->addItem('Home', '/');

echo $this->Menu->render('main');
```

The helper also supports lifecycle operations for named menus:

```php
$main = $this->Menu->getOrCreate('main');

if ($this->Menu->has('main')) {
    $this->Menu->remove('main');
}

$this->Menu->reset();
```

You can also register menus lazily:

```php
$this->Menu->register('main', static function ($menu): void {
    $menu->addItem('Home', '/');
    $menu->addItem('Articles', '/articles');
});
```

`register()` is idempotent by default and returns the existing named menu on repeated calls. Pass `['rebuild' => true]` if you want to replace the existing named menu and rebuild its definition from scratch.

## Item Options

`Menu::addItem()` and `Menu::newItem()` take an options array — `id`, `key`, `icon`, `badge`,
`data`, `matchRoutes`, `fuzzy`, `divider`, `header`, and more. Each option also has a fluent setter
on `ItemInterface`. See the full list with defaults and equivalents in
**[Item Options](/reference/item-options)**.

```php
$menu->addItem('Inbox', ['controller' => 'Messages', 'action' => 'index'])
    ->setIcon('fa fa-inbox')
    ->setBadge($unread, 'bg-danger');
```

::: warning Trusted markup
`icon`, `badge`, `before`, `after`, and `raw` are emitted without escaping — escape or cast dynamic
values yourself. The `label` is escaped unless you pass `escape => false`.
:::

Two more rendering-related options:

```php
// Render the item but not its submenu (treated as a leaf):
$menu->addItem('Reports', '/reports', ['displayChildren' => false]);

// Attributes on the rendered link/label element (classes merge with existing ones):
$menu->addItem('Help', '/help', [
    'labelAttributes' => ['title' => 'Get help', 'class' => 'text-muted'],
]);
```

## Import / Export

Menu trees can be created from arrays and exported back:

```php
$menu = Menu::fromArray([
    'attributes' => ['class' => 'nav'],
    'items' => [
        [
            'id' => 'articles',
            'label' => 'Articles',
            'link' => '/articles',
            'submenu' => [
                'items' => [
                    ['label' => 'View', 'link' => '/articles/view'],
                ],
            ],
        ],
    ],
]);

$data = $menu->toArray();
```

## Freeze Mode

If you want to lock the structural definition after building it:

```php
$menu->freeze();
```

Frozen menus still allow runtime state updates from resolvers such as `active`, `visible`, and `expanded`, but block structural/content changes.

## Item Lookup and Mutation

Look items up by id or by key (explicit, or the slug of the label):

```php
$menu->get('account');           // by id
$menu->has('account');
$menu->remove('account');

$menu->getByKey('profile');      // by key (explicit or label slug)
$menu->hasKey('profile');
$menu->removeByKey('profile');   // removes the first match

$menu->sortBy('weight');
$menu->filter(fn ($item) => $item->isVisible());
$menu->find(fn ($item) => $item->getLabel() !== null && str_contains($item->getLabel(), 'Admin'));
$menu->clearActive();
$menu->getActiveItem();
```

::: tip
Key lookups match the explicit key or, if none was set, the label slug. When labels may collide,
assign explicit keys (`['key' => 'profile']`) so operations target a specific item.
:::

## Tree Manipulation

Reorder, insert, move, merge, and split a menu's direct items (by id or key):

```php
// Insert relative to a sibling:
$menu->insertBefore($menu->newItem('New', '/new'), 'articles');
$menu->insertAfter($menu->newItem('New', '/new'), 'home');

// Move an existing item:
$menu->moveToFirstPosition('account');
$menu->moveToLastPosition('home');
$menu->moveToPosition('articles', 2);

// Reorder by id/key (unlisted items keep their order, appended after):
$menu->reorder(['home', 'articles', 'account']);

// Merge another menu's items in (a deep copy; the source menu is left intact):
$menu->merge($otherMenu);

// Derive new menus (e.g. for columns) — items are copied, the original is untouched:
$firstTwo = $menu->slice(0, 2);
['primary' => $left, 'secondary' => $right] = $menu->split(2);
```

### Flattened Collection

`Menu::collect()` returns an `ItemCollection` containing every item in the tree (depth-first), so
you can iterate or query the whole menu without manual recursion:

```php
$items = $menu->collect();

foreach ($items as $item) {
    // ...
}

$items->findById('menu-item-...');
$items->findByKey('profile');
$items->findByParent($accountItem); // direct children of $accountItem
count($items);
```
