# Usage Guide

## Creating Menus

Use `Menu::create()` for the root menu and `addItem()` for the common case:

```php
use Menu\Menu;

$menu = Menu::create(['class' => 'nav nav-pills']);
$menu->addItem('Home', '/');
$menu->addItem('Docs', 'https://book.cakephp.org', [
    'attributes' => ['target' => '_blank', 'rel' => 'noopener'],
]);
```

### Nested Menus

Each item can own a submenu:

```php
$products = $menu->addItem('Products', '#');
$products->getSubMenu()->setAttributes(['class' => 'submenu']);
$products->add($menu->newItem('Books', ['controller' => 'Products', 'action' => 'books']));
$products->add($menu->newItem('Games', ['controller' => 'Products', 'action' => 'games']));
```

## Item Options

`Menu::addItem()` and `Menu::newItem()` accept these options:

- `id`: stable item id for lookup/removal
- `key`: logical key for your own matching/grouping
- `attributes`: `<li>` attributes
- `submenuAttributes`: nested `<ul>` attributes
- `before`: raw markup before the label/link
- `after`: raw markup after the label/link
- `data`: arbitrary metadata consumed by your app or resolvers
- `visible`: initial visibility
- `active`: initial active state
- `raw`: render raw HTML inside the item
- `divider`: render a divider item

## Item Lookup and Mutation

```php
$menu->get('account');
$menu->has('account');
$menu->remove('account');
$menu->sortBy('weight');
$menu->filter(fn ($item) => $item->isVisible());
```

## Resolvers

Resolvers apply cross-cutting state without mixing request/session logic into menu construction.

### URL Resolvers

```php
use Menu\Resolver\Psr7UrlResolver;
use Menu\Resolver\UrlArrayResolver;

$menu->resolve(new Psr7UrlResolver($request));
$menu->resolve(new UrlArrayResolver($request));
```

### Login Visibility Resolver

Mark items with metadata:

```php
$menu->addItem('Login', '/login', ['data' => ['auth' => 'loggedOut']]);
$menu->addItem('Profile', '/profile', ['data' => ['auth' => 'loggedIn']]);
```

Then resolve:

```php
use Menu\Resolver\LoggedInResolver;

$menu->resolve(new LoggedInResolver($identity !== null));
```

### Multiple Resolvers

```php
use Menu\Resolver\ResolverCollection;

$menu->resolve(
    (new ResolverCollection())
        ->add(new UrlArrayResolver($request))
        ->add(new LoggedInResolver($identity !== null))
);
```

## Helper Rendering

```php
echo $this->Menu->render($menu);
```

You can override templates per render call:

```php
echo $this->Menu->render($menu, [
    'templates' => [
        'menuWrapper' => '<nav><ul{{attributes}}>{{items}}</ul></nav>',
    ],
]);
```

## Notes

- Item labels are escaped by default.
- `before`, `after`, and `raw` are treated as trusted markup.
- String URLs and array URLs are both supported.
