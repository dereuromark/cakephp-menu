# CakePHP Menu Plugin

[![CI](https://github.com/dereuromark/cakephp-menu/actions/workflows/ci.yml/badge.svg)](https://github.com/dereuromark/cakephp-menu/actions/workflows/ci.yml)
[![License](https://poser.pugx.org/dereuromark/cakephp-menu/license.svg)](https://packagist.org/packages/dereuromark/cakephp-menu)

Composable menu builder and renderer for CakePHP applications.

This plugin provides a small menu tree API with:

- nested menu items and submenus
- string and Cake-style array URLs
- renderer abstraction with a Cake `StringTemplate` implementation
- request/user resolvers for active and visible items
- Cake view helper integration

## Requirements

- PHP 8.2+
- CakePHP 5.3+

See the [version matrix](docs/version-matrix.md).

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

## Resolvers

Resolvers let you decorate menu items after construction:

- `Psr7UrlResolver`: marks string URL items active based on the current request URI
- `UrlArrayResolver`: marks Cake array URL items active from request params
- `LoggedInResolver`: hides items marked with `auth = loggedIn|loggedOut`
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

## Rendering

The default renderer is `Menu\Renderer\StringTemplateRenderer`.

It outputs nested `<ul>` / `<li>` markup and supports:

- root menu attributes
- item attributes
- `before` / `after` inline markup
- dividers
- raw HTML items
- active item CSS class

You can override templates through helper options/config.

## Documentation

- [Usage Guide](docs/README.md)
- [Version Matrix](docs/version-matrix.md)
- [Wiki](https://github.com/dereuromark/cakephp-menu/wiki)

## Development

```bash
composer test
composer stan
composer cs-check
```
