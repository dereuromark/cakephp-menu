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

Features:

- nested menu trees with string and Cake-style array URLs
- active-state matching with alternate routes, named routes, and fuzzy matching
- helper-managed named menus and breadcrumb integration
- extensible resolvers and renderers for app-specific rules

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

## Documentation

Full documentation lives at **<https://dereuromark.github.io/cakephp-menu/>**.

- [Getting Started](https://dereuromark.github.io/cakephp-menu/guide/)
- [Building Menus](https://dereuromark.github.io/cakephp-menu/guide/building)
- [Resolvers & Active State](https://dereuromark.github.io/cakephp-menu/guide/resolvers)
- [Rendering](https://dereuromark.github.io/cakephp-menu/guide/rendering)
- [Recipes](https://dereuromark.github.io/cakephp-menu/guide/recipes)
- [Extending](https://dereuromark.github.io/cakephp-menu/guide/extending)

## Demo

https://sandbox.dereuromark.de/menu-sandbox
