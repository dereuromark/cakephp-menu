# Upgrading

## 0.1 to 0.2

0.2 renames the PHP namespace and the plugin from `Menu` to `CakeMenu`. A bare `Menu` namespace
collides easily with application code and other packages, so the plugin now owns a distinct one.
The class names themselves (`Menu`, `MenuHelper`, `Item`, the resolvers and renderers) are unchanged.

### Namespace

Replace the `Menu\` prefix with `CakeMenu\` in every `use` statement and fully qualified name:

```php
use Menu\Menu; // [!code --]
use Menu\Resolver\UrlArrayResolver; // [!code --]
use CakeMenu\Menu; // [!code ++]
use CakeMenu\Resolver\UrlArrayResolver; // [!code ++]
```

A project-wide search and replace of `Menu\` to `CakeMenu\` covers most apps. Check custom
resolvers and renderers that implement the plugin's interfaces, and docblock types such as
`\Menu\Item\ItemInterface`.

### Plugin and helper

The plugin name is now `CakeMenu`, and the plugin class is `CakeMenu\CakeMenuPlugin`:

```php
// src/Application.php
$this->addPlugin('Menu'); // [!code --]
$this->addPlugin('CakeMenu'); // [!code ++]
```

```php
// src/View/AppView.php
$this->loadHelper('Menu.Menu'); // [!code --]
$this->loadHelper('CakeMenu.Menu'); // [!code ++]
```

The helper keeps its `Menu` alias, so `$this->Menu->render('main')` in templates stays as it is.
If you load plugins through `config/plugins.php`, rename the `'Menu'` key there.

### Configuration key

Menus declared in configuration move from `Menu.menus` to `CakeMenu.menus`:

```php
return [
    'Menu' => [ // [!code --]
    'CakeMenu' => [ // [!code ++]
        'menus' => [
            'main' => [/* ... */],
        ],
    ],
];
```

The old `Menu.menus` key is still read when `CakeMenu.menus` is not set, and triggers a
deprecation warning. Support for it will be removed in a later release.

Spec files created earlier with `bin/cake menu generate` use the old key. Rename it there too.
