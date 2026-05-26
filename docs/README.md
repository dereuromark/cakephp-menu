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

`Menu::addItem()` and `Menu::newItem()` accept these options:

- `id`: stable item id for lookup/removal
- `key`: logical key for your own matching/grouping
- `attributes`: `<li>` attributes
- `submenuAttributes`: nested `<ul>` attributes
- `before`: raw markup before the label/link
- `after`: raw markup after the label/link
- `icon`: icon class rendered before the label (e.g. `fa fa-inbox`)
- `badge`: badge text/count rendered after the label
- `badgeType`: extra CSS class for the badge (e.g. `bg-danger`)
- `data`: arbitrary metadata consumed by your app or resolvers
- `visible`: initial visibility
- `active`: initial active state
- `matchRoutes`: alternate string or Cake array routes used for active matching
- `matchRoutes` also accepts named route arrays such as `['_name' => 'articles:view']`
- `ignoreQueryString`: per-item override for string URL query matching
- `fuzzy`: enables subset matching for Cake array routes
- `raw`: render raw HTML inside the item
- `divider`: render a divider item
- `expanded`: runtime state for open branches

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

```php
$menu->get('account');
$menu->has('account');
$menu->remove('account');
$menu->sortBy('weight');
$menu->filter(fn ($item) => $item->isVisible());
$menu->clearActive();
$menu->getActiveItem();
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

## Resolvers

Resolvers apply cross-cutting state without mixing request/session logic into menu construction.

### URL Resolvers

```php
use Menu\Resolver\Psr7UrlResolver;
use Menu\Resolver\UrlArrayResolver;

$menu->resolve(new Psr7UrlResolver($request));
$menu->resolve(new UrlArrayResolver($request));
```

`UrlArrayResolver` supports fuzzy matching, so a route like:

```php
['controller' => 'Articles', 'action' => 'view']
```

can match requests with additional passed parameters such as `/articles/view/42` when the item uses `fuzzy => true`.

It also supports named routes:

```php
$menu->addItem('View', ['_name' => 'articles:view']);
```

### Section Resolver

`SectionResolver` activates items from request parameter subsets:

```php
use Menu\Resolver\SectionResolver;

$menu->addItem('Admin Articles', '/admin/articles', [
    'data' => [
        'section' => [
            'prefix' => 'Admin',
            'controller' => 'Articles',
        ],
    ],
]);

$menu->resolve(new SectionResolver($request));
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

### Authorization and Callback Resolvers

```php
use Menu\Item\ItemInterface;
use Menu\Resolver\AuthorizationResolver;
use Menu\Resolver\CallbackResolver;
use Menu\Resolver\ResolverContext;

$menu->resolve(new AuthorizationResolver(
    static function (ItemInterface $item, ResolverContext $context): ?bool {
        if ($item->getData('permission') === null) {
            return null;
        }

        return $authorization->can($identity, (string)$item->getData('permission'));
    }
));

$menu->resolve(new CallbackResolver(
    static function (ItemInterface $item, ResolverContext $context): void {
        if ($context->getDepth() > 1) {
            $item->setExpanded();
        }
    }
));
```

### Permission Resolver

For Authorization-style `can()` services there is also a convenience resolver:

```php
use Menu\Resolver\PermissionResolver;

$menu->addItem('Admin', '/admin', [
    'data' => ['permission' => 'admin.access'],
]);

$menu->resolve(new PermissionResolver($authorization, $identity));
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

### Adding Resolvers to the Defaults

Passing a `resolver` replaces the built-in URL resolvers (so you lose automatic active-state
matching). To **keep** the defaults and add your own (for example a visibility resolver), use
`additionalResolvers` instead — they run after the URL resolvers:

```php
use Menu\Item\ItemInterface;
use Menu\Resolver\AuthorizationResolver;

echo $this->Menu->render('main', [
    'additionalResolvers' => [
        new AuthorizationResolver(static function (ItemInterface $item): ?bool {
            return $item->getData('adminOnly') ? $isAdmin : null;
        }),
    ],
]);
```

### Depth-Limited Resolution

When rendering through the helper, you can limit how deep automatic URL resolution should scan:

```php
echo $this->Menu->render('main', [
    'resolveDepth' => 1,
]);
```

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
it accepts:

| Option | Default | Purpose |
| --- | --- | --- |
| `idPrefix` | `menu-collapse-` | Prefix for the generated collapse ids. Use a distinct value per sidebar on a page to avoid id collisions. |
| `navClass` | `nav flex-column` | Class on the root `<ul>`. |
| `nestedNavClass` | `nav flex-column ms-3` | Class on the nested `<ul>` inside each collapse. |
| `itemClass` | `nav-item` | Class added to each `<li>` (on top of the item's own attributes). |
| `linkClass` | `nav-link` | Class on leaf links. |
| `toggleClass` | `nav-link d-flex justify-content-between align-items-center` | Class on toggle-only branch links. |
| `toggleButtonClass` | `btn btn-link nav-link border-0 p-0 ms-2` | Class on the separate toggle button used for navigable branches. |
| `collapseClass` | `collapse` | Class on the branch wrapper element. |
| `expandedClass` | `show` | Class added to the wrapper when the branch is open. |
| `toggleAttribute` | `data-bs-toggle` | Toggle attribute on the branch control (value `toggleValue`). Set to `''` to omit (e.g. when wiring your own JS). |
| `toggleValue` | `collapse` | Value for `toggleAttribute`. |
| `targetAttribute` | `data-bs-target` | Attribute pointing the navigable-branch toggle button at its wrapper id. |
| `caret` | `true` | Append a small open/closed indicator (`<span class="menu-caret">`) to branch toggles; set `false` to omit. |
| `caretOpen` / `caretClosed` | `▾` / `▸` | Open/closed caret markup (trusted; pass an icon element like a FontAwesome `<i>` if you prefer). |

The framework-specific keys default to Bootstrap 5; override them (e.g. `toggleAttribute => 'data-toggle'`, `expandedClass => 'is-open'`) to target Bootstrap 4 or another setup without subclassing.

You can override templates per render call:

```php
echo $this->Menu->render($menu, [
    'templates' => [
        'menuWrapper' => '<nav><ul{{attributes}}>{{items}}</ul></nav>',
    ],
]);
```

## Renderer Options

These options are accepted by `StringTemplateRenderer` (the default) and `Bootstrap5Renderer`.
The **Default** column below is for `StringTemplateRenderer`; pass options per render call or as
constructor config when instantiating a renderer directly.

`Bootstrap5Renderer` overrides some of these defaults: `ancestorClass` → `active`,
`branchClass` / `submenuClass` → `dropdown`, `nestedMenuClass` → `dropdown-menu`, and
`addAriaExpanded` → `false` (it emits `aria-expanded` on the toggle link instead).

`Bootstrap5Renderer` also exposes its link-level Bootstrap bits as config (defaults shown), so
BS4/other setups work without subclassing: `linkClass` (`nav-link`, top-level links),
`childLinkClass` (`dropdown-item`, nested links), `toggleClass` (`dropdown-toggle`, branch links),
`toggleAttribute` (`data-bs-toggle`) and `toggleValue` (`dropdown`).

| Option | Default | Description |
| --- | --- | --- |
| `activeClass` | `active` | Class added to the active item's `<li>`. |
| `ancestorClass` | `active-ancestor` | Class for items on the active trail. |
| `branchClass` | `has-children` | Class for items that have a submenu. |
| `submenuClass` | `null` | Extra class for branch items, in addition to `branchClass`. |
| `leafClass` | `null` | Class for items without a submenu. |
| `dividerClass` | `divider` | Class for divider items. |
| `nestedMenuClass` | `submenu` | Class added to nested `<ul>` elements. |
| `rootClass` | `null` | Class appended to the root (level 1) `<ul>` only. |
| `menuLevelClass` | `null` | Prefix for a per-level class (e.g. `level-` produces `level-1`, `level-2`). |
| `firstClass` / `lastClass` | `null` | Class for the first / last item in a list. |
| `depth` | `null` | Maximum number of levels to render (`null` = unlimited). |
| `hideEmptyBranches` | `false` | Skip branches with no visible, renderable children. |
| `currentAsLink` | `true` | Render the active item as a link; `false` renders a plain label. |
| `addAriaCurrent` | `true` | Emit `aria-current="page"` on the active item. |
| `addAriaExpanded` | `true` | Emit `aria-expanded` on branch items. |
| `ariaLabel` | `null` | `aria-label` for the root `<ul>`. |
| `templates` | see above | Override the `menuWrapper`, `item`, `link`, `label`, and `divider` strings. |

## Notes

- Item labels are escaped by default.
- `before`, `after`, and `raw` are treated as trusted markup.
- String URLs and array URLs are both supported.
- Active matching is automatic in the helper by default and uses both array and string resolvers.
- Each helper render/request-state lookup applies resolvers temporarily and restores the original `active`, `visible`, and `expanded` item state afterward.
- Custom item classes should extend `Menu\Item\Item` or implement `Menu\Item\StateResetInterface` if you want `Menu::resetState()` to restore their original runtime defaults.
- The default renderer emits `aria-current="page"` for the active item (whether rendered as a link or as a label) and `aria-expanded` for branch items.

## Recipes

### Admin Sidebar

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

### Account Dropdown

```php
$account = $menu->addItem('Account', '#');
$account->getSubMenu()->addItem('Profile', '/profile');
$account->getSubMenu()->addItem('Logout', '/logout');

echo $this->Menu->render($menu, [
    'renderer' => \Menu\Renderer\Bootstrap5Renderer::class,
]);
```

### Breadcrumbs From Navigation

```php
$this->Menu->register('main', static function ($menu): void {
    $articles = $menu->addItem('Articles', '/articles');
    $articles->getSubMenu()->addItem('View', '/articles/view/42');
});

echo $this->Menu->render('main');
echo $this->Menu->renderBreadcrumbs('main');
```

### Role-Based Menus (TinyAuth)

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

### Caching Role-Based Menus

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

### TinyAuth Backend Navigation

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

### Icons and Badges

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

### Defining a Menu in Config

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

### Building Menus Once in AppView

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

## Custom Renderers

Implement `Menu\Renderer\RendererInterface` (or extend `StringTemplateRenderer`) and pass the class
name or an instance as the `renderer` option:

```php
use Menu\Item\ItemInterface;
use Menu\MenuInterface;
use Menu\Renderer\RendererInterface;

class NavRenderer implements RendererInterface
{
    public function render(MenuInterface $menu, array $options = []): string
    {
        // ...build markup from $menu->getItems()...
        return '';
    }

    public function renderItem(ItemInterface $item, array $options = []): string
    {
        // ...
        return '';
    }
}

echo $this->Menu->render('main', ['renderer' => NavRenderer::class]);
```

A single item can also render itself by implementing `Menu\Item\SelfRendererInterface::render()`,
which the built-in renderers call directly.

## Custom Resolvers

Implement `Menu\Resolver\ResolverInterface` (or `ContextAwareResolverInterface` for depth/parent
awareness) and add it via `additionalResolvers` or a `ResolverCollection`:

```php
use Menu\Item\ItemInterface;
use Menu\Resolver\ResolverInterface;

class FeatureFlagResolver implements ResolverInterface
{
    public function resolve(ItemInterface $item): void
    {
        $feature = $item->getData('feature');
        if ($feature !== null && !Features::enabled((string)$feature)) {
            $item->setVisibility(false);
        }
    }
}
```

## Testing Menus

A menu is plain PHP, so its structure and resolved state are easy to assert without rendering:

```php
use Cake\Http\ServerRequest;
use Menu\Menu;
use Menu\Resolver\Psr7UrlResolver;

$menu = Menu::create();
$menu->addItem('Home', '/');
$menu->addItem('Articles', '/articles');

$menu->resolve(new Psr7UrlResolver(new ServerRequest(['url' => '/articles'])));

$this->assertSame('Articles', $menu->getActiveItem()?->getLabel());
$this->assertCount(2, $menu->collect());
```
