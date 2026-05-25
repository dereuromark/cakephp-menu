<?php

declare(strict_types=1);

namespace Menu\View\Helper;

use Cake\View\Helper;
use InvalidArgumentException;
use Menu\Item\ItemInterface;
use Menu\Menu;
use Menu\MenuInterface;
use Menu\Renderer\RendererInterface;
use Menu\Renderer\StringTemplateRenderer;
use Menu\Resolver\Psr7UrlResolver;
use Menu\Resolver\ResolverCollection;
use Menu\Resolver\ResolverCollectionInterface;
use Menu\Resolver\ResolverInterface;
use Menu\Resolver\UrlArrayResolver;

/**
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class MenuHelper extends Helper
{
    /**
     * @var array<string, \Menu\MenuInterface>
     */
    protected array $menus = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $menuConfigs = [];

    protected ?string $lastMenuName = null;

    protected array $_defaultConfig = [
        'renderer' => StringTemplateRenderer::class,
        'resolve' => true,
        'ignoreQueryString' => true,
        'fuzzy' => true,
        'currentAsLink' => true,
    ];

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    public function create(string $name, array $options = []): MenuInterface
    {
        if ($name === '') {
            throw new InvalidArgumentException('Menu name must not be empty.');
        }

        $attributes = [];
        if (isset($options['menuAttributes']) && is_array($options['menuAttributes'])) {
            $attributes = $options['menuAttributes'];
        } elseif (isset($options['attributes']) && is_array($options['attributes'])) {
            $attributes = $options['attributes'];
        }

        $menu = Menu::create($attributes);
        $this->menus[$name] = $menu;
        $this->menuConfigs[$name] = $options;
        $this->lastMenuName = $name;

        return $menu;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function get(string $name): MenuInterface
    {
        if (!isset($this->menus[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown menu `%s`.', $name));
        }

        return $this->menus[$name];
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface|string|null $menu = null, array $options = []): string
    {
        [$menu, $resolvedOptions] = $this->resolveMenuAndOptions($menu, $options);
        $this->applyResolvers($menu, $resolvedOptions);

        return $this->getRenderer($resolvedOptions)->render($menu, $resolvedOptions);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function getCurrentItem(MenuInterface|string|null $menu = null, array $options = []): ?ItemInterface
    {
        [$menu, $resolvedOptions] = $this->resolveMenuAndOptions($menu, $options);
        $this->applyResolvers($menu, $resolvedOptions);

        return $menu->getActiveItem();
    }

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @return list<\Menu\Item\ItemInterface>
     */
    public function extractPath(ItemInterface $item, array $options = []): array
    {
        $path = [$item];
        while (($item = $item->getParent()) !== null) {
            $path[] = $item;
        }

        return array_reverse($path);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     *
     * @return array{0: \Menu\MenuInterface, 1: array<string, mixed>}
     */
    protected function resolveMenuAndOptions(MenuInterface|string|null $menu, array $options): array
    {
        $menuName = null;
        if (is_string($menu)) {
            $menuName = $menu;
            $menu = $this->get($menu);
        } elseif ($menu === null) {
            if ($this->lastMenuName === null) {
                throw new InvalidArgumentException('No menu available.');
            }
            $menuName = $this->lastMenuName;
            $menu = $this->get($menuName);
        }

        $createOptions = $menuName !== null ? ($this->menuConfigs[$menuName] ?? []) : [];
        /** @var array<string, mixed> $resolvedOptions */
        $resolvedOptions = array_replace($this->getConfig(), $createOptions, $options);

        return [$menu, $resolvedOptions];
    }

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    protected function applyResolvers(MenuInterface $menu, array $options): void
    {
        if (($options['resolve'] ?? true) !== true) {
            return;
        }

        $resolver = $options['resolver'] ?? $this->createDefaultResolver($options);
        if (!$resolver instanceof ResolverInterface && !$resolver instanceof ResolverCollectionInterface) {
            throw new InvalidArgumentException('Resolver must implement ResolverInterface or ResolverCollectionInterface.');
        }

        $menu->clearActive();
        $menu->resolve($resolver);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function createDefaultResolver(array $options): ResolverCollectionInterface
    {
        return (new ResolverCollection())
            ->add(new UrlArrayResolver(
                $this->getView()->getRequest(),
                ['fuzzy' => $options['fuzzy'] ?? true],
            ))
            ->add(new Psr7UrlResolver(
                $this->getView()->getRequest(),
                ['ignoreQueryString' => $options['ignoreQueryString'] ?? true],
            ));
    }

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException
     */
    protected function getRenderer(array $options): RendererInterface
    {
        $renderer = $options['renderer'] ?? $this->getConfig('renderer');
        if ($renderer instanceof RendererInterface) {
            return $renderer;
        }

        if (!is_string($renderer)) {
            throw new InvalidArgumentException('Renderer must be a class name or RendererInterface instance.');
        }

        /** @var class-string<\Menu\Renderer\RendererInterface> $renderer */
        return new $renderer($options);
    }
}
