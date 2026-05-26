---
description: Apply active state and visibility in cakephp-menu with URL, section, login, permission, authorization, and callback resolvers — composed or layered on the defaults.
---

# Resolvers & Active State

Resolvers apply cross-cutting state without mixing request/session logic into menu construction.
Each resolver inspects every item and sets its active and/or visible state; chained together with a
`ResolverCollection`, they run in order, so later resolvers see what earlier ones decided.

```mermaid
flowchart LR
    M[Built menu] --> R1[UrlArrayResolver<br/>active state]
    R1 --> R2[LoggedInResolver<br/>visibility]
    R2 --> R3[PermissionResolver<br/>visibility]
    R3 --> Out[Resolved menu<br/>ready to render]
```

## URL Resolvers

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

## Section Resolver

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

## Login Visibility Resolver

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

## Authorization and Callback Resolvers

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

## Permission Resolver

For Authorization-style `can()` services there is also a convenience resolver:

```php
use Menu\Resolver\PermissionResolver;

$menu->addItem('Admin', '/admin', [
    'data' => ['permission' => 'admin.access'],
]);

$menu->resolve(new PermissionResolver($authorization, $identity));
```

## Multiple Resolvers

```php
use Menu\Resolver\ResolverCollection;

$menu->resolve(
    (new ResolverCollection())
        ->add(new UrlArrayResolver($request))
        ->add(new LoggedInResolver($identity !== null))
);
```

## Adding Resolvers to the Defaults

::: warning A custom `resolver` replaces the defaults
Passing a `resolver` option **replaces** the built-in URL resolvers, so you lose automatic
active-state matching. To **keep** the defaults and add your own (for example a visibility resolver),
use `additionalResolvers` instead — they run after the URL resolvers.
:::

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

## Depth-Limited Resolution

When rendering through the helper, you can limit how deep automatic URL resolution should scan:

```php
echo $this->Menu->render('main', [
    'resolveDepth' => 1,
]);
```

