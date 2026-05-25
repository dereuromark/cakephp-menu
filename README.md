# CakePHP Menu Plugin

[![CI](https://github.com/dereuromark/cakephp-menu/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-menu/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-menu/master.svg)](https://codecov.io/gh/dereuromark/cakephp-menu)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-menu/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-menu)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-menu/license.svg)](https://packagist.org/packages/dereuromark/cakephp-menu)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-menu/d/total.svg)](https://packagist.org/packages/dereuromark/cakephp-menu)
[![Coding Standards](https://img.shields.io/badge/cs-PhpCollective-purple.svg?style=flat-square)](https://github.com/php-collective/code-sniffer)

Composable menu builder and renderer for CakePHP applications.

This branch is for **CakePHP 5.3+**. See the [version map](https://github.com/dereuromark/cakephp-menu/wiki#cakephp-version-map) for details.

This plugin provides a small menu tree API with:

- nested menu items and submenus
- string and Cake-style array URLs
- alternate match routes, named routes, and fuzzy route matching
- renderer abstraction with a Cake `StringTemplate` implementation
- breadcrumb rendering and Cake `BreadcrumbsHelper` integration
- request-aware active item resolution
- callback and authorization resolvers for app-specific rules
- named-menu Cake view helper integration

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

```php
use Menu\Menu;

$menu = Menu::create(['class' => 'nav']);
$menu->addItem('Dashboard', ['controller' => 'Dashboard', 'action' => 'index']);

$account = $menu->addItem('Account', '#', [
    'attributes' => ['class' => 'nav-item'],
]);
$account->getSubMenu()->setAttributes(['class' => 'submenu']);
$account->add($menu->newItem('Profile', ['controller' => 'Users', 'action' => 'profile']));
$account->add($menu->newItem('Logout', ['controller' => 'Users', 'action' => 'logout']));

echo $this->Menu->render($menu);
```

## Helper Workflow

For view-driven menus you can let the helper manage named menus and active-state resolution:

```php
$menu = $this->Menu->create('main', [
    'menuAttributes' => ['class' => 'nav'],
]);
$menu->addItem('Home', '/');
$menu->addItem('Articles', ['controller' => 'Articles', 'action' => 'index'], [
    'matchRoutes' => [
        ['controller' => 'Articles', 'action' => 'view'],
    ],
    'fuzzy' => true,
]);

echo $this->Menu->render('main');
```

The helper also manages named menu lifecycle and breadcrumb generation:

```php
$menu = $this->Menu->getOrCreate('main');

if (!$this->Menu->has('account')) {
    $account = $menu->addItem('Account', '/account');
    $account->getSubMenu()->addItem('Profile', '/account/profile');
}

echo $this->Menu->renderBreadcrumbs('main');
```

## Resolvers

Resolvers let you decorate menu items after construction:

- `Psr7UrlResolver`: marks string URL items active based on the current request URI
- `UrlArrayResolver`: marks Cake array URL items active from request params, including fuzzy and named-route matching
- `LoggedInResolver`: hides items marked with `auth = loggedIn|loggedOut`
- `AuthorizationResolver`: hides or shows items via an app-provided authorization callback
- `CallbackResolver`: generic resolver hook for section matching, metadata-driven visibility, or other custom rules
- `ResolverCollection`: applies multiple resolvers in order

Example:

```php
use Menu\Resolver\LoggedInResolver;
use Menu\Resolver\ResolverCollection;
use Menu\Resolver\UrlArrayResolver;

$menu->addItem('Login', ['controller' => 'Users', 'action' => 'login'], [
    'data' => ['auth' => 'loggedOut'],
]);

$menu->resolve(
    (new ResolverCollection())
        ->add(new UrlArrayResolver($this->request))
        ->add(new LoggedInResolver($this->Authentication->getIdentity() !== null))
);
```

Authorization example:

```php
use Menu\Item\ItemInterface;
use Menu\Resolver\AuthorizationResolver;
use Menu\Resolver\ResolverContext;

$menu->resolve(new AuthorizationResolver(
    static function (ItemInterface $item, ResolverContext $context): ?bool {
        if ($item->getData('permission') === null) {
            return null;
        }

        return $authorization->can($identity, (string)$item->getData('permission'));
    }
));
```

## Rendering

The default renderer is `Menu\Renderer\StringTemplateRenderer`.

It outputs nested `<ul>` / `<li>` markup and supports:

- root menu attributes
- item attributes
- `before` / `after` inline markup
- dividers
- raw HTML items
- active and active-ancestor CSS classes
- first/last/branch/leaf/menu-level classes
- `aria-current` and `aria-expanded` output
- rendering the active item as text instead of a link

The plugin also includes `Menu\Renderer\BreadcrumbRenderer` for rendering the active menu path as breadcrumb markup.

You can override templates through helper options/config and limit automatic resolution depth via `resolveDepth`.

## Breadcrumbs

You can feed CakePHP's built-in `BreadcrumbsHelper` directly from the active menu path:

```php
$crumbs = $this->Menu->getBreadcrumbs('main');
$this->Menu->populateBreadcrumbs('main');

echo $this->Breadcrumbs->render();
```

Or render breadcrumbs in one step:

```php
echo $this->Menu->renderBreadcrumbs('main');
echo $this->Menu->renderBreadcrumbs('main', [
    'renderer' => \Menu\Renderer\BreadcrumbRenderer::class,
]);
```

## Documentation

- [Usage Guide](docs/README.md)

## Development

```bash
composer test
composer stan
composer cs-check
```
