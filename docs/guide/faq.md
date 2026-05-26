---
description: Troubleshooting cakephp-menu — active state not matching, subdirectory base paths, plugin/prefix URLs, label escaping, and multiple active items.
---

# FAQ & Troubleshooting

## The active item isn't highlighted

Active-state matching compares the item's URL to the current request. The usual causes:

- **The item URL doesn't match the route.** An array URL must include everything the request has —
  `plugin`, `prefix`, `controller`, `action`. A missing `prefix => 'Admin'` is the most common miss.
- **Extra passed/query params.** `/articles/view/42` won't match `['controller' => 'Articles',
  'action' => 'view']` unless the item opts into fuzzy matching.
- **A custom `resolver` replaced the defaults.** Passing `resolver` removes the built-in URL
  resolvers. Use `additionalResolvers` to keep them — see
  [Resolvers](/guide/resolvers#adding-resolvers-to-the-defaults).

Enable fuzzy matching so a parent route matches its deeper requests:

::: code-group

```php [Per item]
$menu->addItem('Articles', ['controller' => 'Articles', 'action' => 'index'], [
    'fuzzy' => true,
]);
```

```php [Per render]
echo $this->Menu->render('main', ['fuzzy' => true]);
```

:::

To inspect what resolved, dump the active item:

```php
debug($this->Menu->getCurrentItem('main')?->getLabel());
```

## Several items light up at once

A broad route or fuzzy matching can mark more than one item active. Keep only the deepest (most
specific) one active with `singleActive`:

```php
echo $this->Menu->render('main', ['singleActive' => true]);
```

## Links break in a subdirectory install

When the app lives under a subdirectory (e.g. `/myapp`), prefer **array URLs** so CakePHP's Router
prepends the base path and matches correctly:

::: code-group

```php [Recommended]
$menu->addItem('Home', ['controller' => 'Pages', 'action' => 'display', 'home']);
```

```php [Fragile]
$menu->addItem('Home', '/'); // hard-coded, ignores the base path
```

:::

## A FontAwesome icon shows as literal `<i>` text

The `label` is HTML-escaped by default. Don't put markup in the label — use the dedicated, trusted
slots instead:

```php
$menu->addItem('Inbox', '/inbox')->setIcon('fa fa-inbox'); // rendered as trusted markup
```

::: warning
`icon`, `badge`, `before`, `after`, and `raw` are emitted **without escaping**. Escape or cast any
dynamic value you put there yourself. See [Item Options](/reference/item-options#options).
:::

## A Bootstrap dropdown / collapse doesn't open

The renderers emit the correct Bootstrap markup, but the **interactivity is Bootstrap's JS**. Make
sure Bootstrap's JS bundle is loaded on the page:

```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

## Sidebar branches collapse the wrong section

The sidebar uses each branch's `id` to build its collapse target. If two branches share an id (or
none is set), their toggles collide. Give each branch a unique `id`:

```php
$menu->addItem('Features', '#', ['id' => 'features']);
$menu->addItem('More', '#', ['id' => 'more']);
```

## Does rendering mutate my menu?

No — when you render **through the helper**, it captures and restores each item's active/visible/
expanded state around the call, so a `register()`-ed menu can be rendered many times per request. If
you call a renderer **directly** and re-use the menu, call `$menu->resetState()` yourself between
resolutions.

## Breadcrumbs come out empty

`renderBreadcrumbs()` needs a resolved active item. If nothing matches the current request, there's
no path to render. Confirm the active item resolves first (see the first question), then render.
