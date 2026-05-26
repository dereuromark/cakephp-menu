---
description: Practical cakephp-menu recipes — admin sidebars, account dropdowns, breadcrumbs, role-based menus with TinyAuth, caching, and config- or database-backed menus.
---

# Recipes

## Admin Sidebar

```php
$this->Menu->register('admin', static function ($menu): void {
    $menu->addItem('Dashboard', ['prefix' => 'Admin', 'controller' => 'Dashboard', 'action' => 'index'], [
        'data' => ['section' => ['prefix' => 'Admin', 'controller' => 'Dashboard']],
    ]);
    $menu->addItem('Articles', ['prefix' => 'Admin', 'controller' => 'Articles', 'action' => 'index'], [
        'data' => ['section' => ['prefix' => 'Admin', 'controller' => 'Articles']],
    ]);
});

echo $this->Menu->render('admin', [
    'resolver' => (new \Menu\Resolver\ResolverCollection())
        ->add(new \Menu\Resolver\SectionResolver($this->request)),
]);
```

## Account Dropdown

```php
$account = $menu->addItem('Account', '#');
$account->getSubMenu()->addItem('Profile', '/profile');
$account->getSubMenu()->addItem('Logout', '/logout');

echo $this->Menu->render($menu, [
    'renderer' => \Menu\Renderer\Bootstrap5Renderer::class,
]);
```

## Breadcrumbs From Navigation

```php
$this->Menu->register('main', static function ($menu): void {
    $articles = $menu->addItem('Articles', '/articles');
    $articles->getSubMenu()->addItem('View', '/articles/view/42');
});

echo $this->Menu->render('main');
echo $this->Menu->renderBreadcrumbs('main');
```

## Role-Based Menus (TinyAuth)

[TinyAuth](https://github.com/dereuromark/cakephp-tinyauth) exposes
`$this->AuthUser->hasAccess($url)`, which returns whether the current user may access a CakePHP URL.
Combined with `additionalResolvers` (which keeps the default active-state matching) and
`hideEmptyBranches`, items the user cannot reach are hidden automatically. The same recipe works
whether TinyAuth's ACL is INI- or DB-backed (e.g. via
[tinyauth-backend](https://github.com/dereuromark/cakephp-tinyauth-backend)), because `hasAccess()`
abstracts the adapter.

```php
use Menu\Item\ItemInterface;
use Menu\Resolver\AuthorizationResolver;

// In a template/view where the TinyAuth.AuthUser helper is loaded.
$menu = $this->Menu->create('admin');
$menu->addItem('Articles', ['prefix' => 'Admin', 'controller' => 'Articles', 'action' => 'index']);
$menu->addItem('Users', ['prefix' => 'Admin', 'controller' => 'Users', 'action' => 'index']);

echo $this->Menu->render('admin', [
    'hideEmptyBranches' => true,
    'additionalResolvers' => [
        new AuthorizationResolver(function (ItemInterface $item): ?bool {
            $url = $item->getLink()?->getRawUrl();

            return is_array($url) ? $this->AuthUser->hasAccess($url) : null;
        }),
    ],
]);
```

Prefer explicit role tags? Tag items with `data` and check `hasRoles()` instead:

```php
$menu->addItem('Settings', ['prefix' => 'Admin', 'controller' => 'Settings', 'action' => 'index'], [
    'data' => ['roles' => ['admin']],
]);

new AuthorizationResolver(function (ItemInterface $item): ?bool {
    $roles = $item->getData('roles');

    return $roles === null ? null : $this->AuthUser->hasRoles((array)$roles);
});
```

Returning `null` from the callback leaves the item untouched, so items without a URL/role tag stay
visible.

## Caching Role-Based Menus

Access checks are cheap, but for a large ACL menu you can resolve visibility once per role-set and
cache the filtered tree. Active-state is request-specific, so cache the *structure*, not the HTML:

```php
use Cake\Cache\Cache;
use Menu\Item\ItemInterface;
use Menu\Menu;
use Menu\Resolver\AuthorizationResolver;

$cacheKey = 'menu_main_' . implode('-', $this->AuthUser->roles());
$tree = Cache::read($cacheKey);
if ($tree === null) {
    $menu = $this->Menu->create('main');
    // ...addItem() calls...

    $menu->resolve(new AuthorizationResolver(function (ItemInterface $item): ?bool {
        $url = $item->getLink()?->getRawUrl();

        return is_array($url) ? $this->AuthUser->hasAccess($url) : null;
    }));
    $menu->filter(static fn (ItemInterface $item): bool => $item->isVisible());

    $tree = $menu->toArray();
    Cache::write($cacheKey, $tree);
}

// Rendering re-resolves active-state for the current request.
echo $this->Menu->render(Menu::fromArray($tree));
```

## TinyAuth Backend Navigation

`tinyauth-backend` exposes `$this->TinyAuth->getNavigationItems()` — its feature-gated admin
sections (Dashboard, Roles, Resources, ...) as `['name', 'label', 'route']` arrays (already filtered
to the enabled features). Turn it into a menu:

```php
$menu = $this->Menu->create('admin');
foreach ($this->TinyAuth->getNavigationItems() as $item) {
    $menu->addItem(
        __($item['label']),
        ['plugin' => 'TinyAuthBackend', 'prefix' => 'Admin'] + $item['route'],
        ['key' => $item['name']],
    );
}

echo $this->Menu->render('admin');
```

## Icons and Badges

Icons and badges are first-class: `setIcon()` / `setBadge()` (on `ItemInterface`) or the
`icon`/`badge`/`badgeType` options are escaped for you and rendered around the label.

```php
$menu->addItem('Inbox', ['controller' => 'Messages', 'action' => 'index'])
    ->setIcon('fa fa-inbox')
    ->setBadge($unread, 'bg-danger');

// Same via options:
$menu->addItem('Profile', '/profile', ['icon' => 'fa fa-user', 'badge' => 'new']);
```

The markup is overridable per render with the `iconTemplate` / `badgeTemplate` options
(`{{icon}}`, and `{{class}}`/`{{text}}` placeholders). For anything more custom, `before`, `after`,
and `raw` are still emitted as trusted markup — cast or escape dynamic values you put there yourself
(e.g. `(int)$count`).

## Defining a Menu in Config

`Menu::fromArray()` accepts the same shape `toArray()` produces, so a menu can live in a config file:

```php
// config/menu.php
return [
    'attributes' => ['class' => 'nav'],
    'items' => [
        ['label' => 'Home', 'link' => '/'],
        ['label' => 'Articles', 'link' => ['controller' => 'Articles', 'action' => 'index']],
    ],
];
```

```php
use Menu\Menu;

$menu = Menu::fromArray(require CONFIG . 'menu.php');
echo $this->Menu->render($menu);
```

## Database-Backed Menus

`Menu::fromFlat()` builds a tree from flat, parent-referenced rows (a `menu_items` table, an
editable CMS nav, …). The mapper turns each row into a spec (`key`, `parent`, `label`, `link`, and
any `newItem()` `options`); rows may be in any order, and unknown parents fall back to root:

```php
use Menu\Menu;

$rows = $this->fetchTable('MenuItems')->find()->orderBy(['weight' => 'ASC'])->all();

$menu = Menu::fromFlat($rows, fn ($row): array => [
    'key' => (string)$row->id,
    'parent' => $row->parent_id !== null ? (string)$row->parent_id : null,
    'label' => $row->title,
    'link' => $row->url,
]);

echo $this->Menu->render($menu);
```

## Building Menus Once in AppView

`register()` is idempotent, so define named menus once in `AppView::initialize()` and render them
from any template:

```php
// src/View/AppView.php
public function initialize(): void
{
    parent::initialize();
    $this->loadHelper('Menu.Menu');

    $this->Menu->register('main', function ($menu): void {
        $menu->addItem('Home', '/');
        $menu->addItem('Articles', ['controller' => 'Articles', 'action' => 'index']);
    });
}
```

```php
// any template
echo $this->Menu->render('main');
```

