---
description: Extend cakephp-menu with custom renderers and resolvers, and integrate it with your own authorization and data services.
---

# Extending

## Custom Renderers

Implement `CakeMenu\Renderer\RendererInterface` (or extend `StringTemplateRenderer`) and pass the class
name or an instance as the `renderer` option:

```php
use CakeMenu\Item\ItemInterface;
use CakeMenu\MenuInterface;
use CakeMenu\Renderer\RendererInterface;

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

A single item can also render itself by implementing `CakeMenu\Item\SelfRendererInterface::render()`,
which the built-in renderers call directly.

## Custom Resolvers

Implement `CakeMenu\Resolver\ResolverInterface` and add it via `additionalResolvers` or a
`ResolverCollection`. The required context provides the depth and parent:

```php
use CakeMenu\Item\ItemInterface;
use CakeMenu\Resolver\ResolverInterface;
use CakeMenu\Resolver\ResolverContext;

class FeatureFlagResolver implements ResolverInterface
{
    public function resolve(ItemInterface $item, ResolverContext $context): void
    {
        $feature = $item->getData('feature');
        if ($feature !== null && !Features::enabled((string)$feature)) {
            $item->setRuntimeVisible(false);
        }
    }
}
```

## Testing Menus

A menu is plain PHP, so its structure and resolved state are easy to assert without rendering:

```php
use Cake\Http\ServerRequest;
use CakeMenu\Menu;
use CakeMenu\Resolver\Psr7UrlResolver;

$menu = Menu::create();
$menu->addItem('Home', '/');
$menu->addItem('Articles', '/articles');

$menu->resolve(new Psr7UrlResolver(new ServerRequest(['url' => '/articles'])));

$this->assertSame('Articles', $menu->getActiveItem()?->getLabel());
$this->assertCount(2, $menu->collect());
```
