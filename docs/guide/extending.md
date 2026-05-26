---
description: Extend cakephp-menu with custom renderers and resolvers, and integrate it with your own authorization and data services.
---

# Extending

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
