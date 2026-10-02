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

### Resolver interface

`ContextAwareResolverInterface` is removed. Every resolver implements `ResolverInterface`
with a required context. Rename `resolveWithContext()` and remove any wrapper `resolve()`:

```php
public function resolveWithContext(ItemInterface $item, ResolverContext $context): void // [!code --]
public function resolve(ItemInterface $item, ResolverContext $context): void // [!code ++]
```

Direct calls must pass a context; `Menu::resolve()` creates it for you.
`ResolverCollectionInterface` now extends `ResolverInterface`, so replace
`ResolverInterface|ResolverCollectionInterface` types with `ResolverInterface`.

### Runtime state

`StateResetInterface` and `RuntimeStateTrait` are removed. Custom items implement
`resetState()`, `setRuntimeVisible()`, `setRuntimeActive()`, and `setRuntimeExpanded()`
as part of `ItemInterface`. Rename `setRuntimeVisibility()` to `setRuntimeVisible()`.
Resolvers call these setters directly:

```php
$item->setActive(true); // [!code --]
$item->setRuntimeActive(true); // [!code ++]
$item->setExpanded(); // [!code --]
$item->setRuntimeExpanded(); // [!code ++]
```

Authoring setters change defaults only. Runtime setters override them until `resetState()`.

### Visibility and matching

Rename `setVisibility()` to `setVisible()`; `isVisible()` is unchanged.
Replace `setFuzzyMatch()`, `isFuzzyMatch()`, and `getFuzzyMatchSetting()` with
`setFuzzy(?bool)` and `getFuzzy(): ?bool`:

```php
$item->setVisibility(false)->setFuzzyMatch(); // [!code --]
$item->setVisible(false)->setFuzzy(true); // [!code ++]
```

`null` inherits the resolver setting. The `fuzzy` option key stays the same.
URL resolvers now use `ItemInterface` matching methods for custom items too.
`setParent()`, `setOwnerMenu()`, and `MenuInterface::setOwnerItem()` are internal tree plumbing.

### Item paths

Custom items must implement `getPath()` (root-to-self, including self), `getLevel()`
(zero for top-level items), and `getRoot()` (the top-level ancestor).
`MenuHelper::extractPath()` delegates to `getPath()` and no longer accepts options:

```php
$path = $this->Menu->extractPath($item, $options); // [!code --]
$path = $item->getPath(); // [!code ++]
```

### Serialization

`Item::toArray()` now writes authoring defaults for `visible`, `active`, and `expanded`.
Rebuilding a resolved menu with `Menu::fromArray()` no longer preserves request-specific
state. Apply resolvers again for the current request.

### Helper build options

Replace `menuAttributes` with `attributes`:

```php
$this->Menu->create('main', ['menuAttributes' => ['class' => 'nav']]); // [!code --]
$this->Menu->create('main', ['attributes' => ['class' => 'nav']]); // [!code ++]
```

`attributes`, `overwrite`, `cache`, and `rebuild` are build options. Renderers no longer
receive them as per-menu defaults. Other options still become render defaults.

### Breadcrumb rendering

`renderBreadcrumbs()` takes only the menu and options, and always uses `BreadcrumbRenderer`.
Its `renderer` option accepts a subclass name or instance. For Cake's helper markup:

```php
$this->Menu->renderBreadcrumbs('main', [], $attributes, $separator); // [!code --]
$this->Menu->populateBreadcrumbs('main'); // [!code ++]
$this->Breadcrumbs->render($attributes, $separator); // [!code ++]
```

### Callback and authorizer arguments

Registration closures always receive `($menu, $helper)`. Closures may omit unused parameters.
`PermissionResolver` always calls `can($identity, $permission, $item)`; adapt authorizers
that previously expected another argument order or a resolver context.

```php
public function can(string $permission): bool // [!code --]
public function can(mixed $identity, string $permission, ItemInterface $item): bool // [!code ++]
```

### Sort direction

Replace string directions and the removed `Menu::SORT_ASC` / `SORT_DESC` constants with
`CakeMenu\SortDirection`:

```php
$menu->sortBy('weight', Menu::SORT_DESC); // [!code --]
$menu->sortBy('weight', SortDirection::Desc); // [!code ++]
```

`SortDirection::Asc` remains the default. Custom menus must update their method signature.

### Authentication state

`LoggedInResolver` accepts `CakeMenu\Resolver\AuthState` in item data:

```php
$item->setData('auth', 'loggedIn'); // [!code --]
$item->setData('auth', AuthState::LoggedIn); // [!code ++]
```

The backed strings `'loggedIn'` and `'loggedOut'` still work in array configuration.
Unknown strings now throw `InvalidArgumentException` instead of being ignored.

### Closure signatures

Menu filter/find/sort callbacks, helper registration callbacks, `CallbackResolver`, and
`AuthorizationResolver` now require `Closure`. Convert function names and method arrays
with first-class callable syntax:

```php
$menu->filter([$service, 'isVisible']); // [!code --]
$menu->filter($service->isVisible(...)); // [!code ++]
```

Custom implementations must replace `callable` parameter types with `Closure`.

### Custom menu interfaces

Custom `MenuInterface` implementations must add `collect(): ItemCollection`,
`setItemClass(string $class): static`, and `getItemClass(): string`.
`Menu::setItemClass()` validates that the class implements `ItemInterface`.
New item submenus inherit the owning menu's item class instead of always using `Item`.
Set the class before building the tree.

### Regex request matching

Pass a `ServerRequestInterface` instead of a path string. Move the custom data key
into the options array:

```php
new RegexResolver($request->getUri()->getPath(), 'activePattern'); // [!code --]
new RegexResolver($request, ['dataKey' => 'activePattern', 'maxDepth' => 2]); // [!code ++]
```

The resolver matches the URI path. `maxDepth` uses `ResolverContext` depth (top level is 1),
like the other request resolvers; `null` leaves depth unlimited.
