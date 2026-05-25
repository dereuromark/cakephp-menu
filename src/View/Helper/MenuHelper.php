<?php

declare(strict_types=1);

namespace Menu\View\Helper;

use Cake\View\Helper;
use Closure;
use InvalidArgumentException;
use Menu\Item\ItemInterface;
use Menu\Item\StateResetInterface;
use Menu\Menu;
use Menu\MenuInterface;
use Menu\Renderer\BreadcrumbRenderer;
use Menu\Renderer\RendererInterface;
use Menu\Renderer\StringTemplateRenderer;
use Menu\Resolver\Psr7UrlResolver;
use Menu\Resolver\ResolverCollection;
use Menu\Resolver\ResolverCollectionInterface;
use Menu\Resolver\ResolverInterface;
use Menu\Resolver\UrlArrayResolver;
use ReflectionFunction;

/**
 * @extends \Cake\View\Helper<\Cake\View\View>
 * @property \Cake\View\Helper\BreadcrumbsHelper $Breadcrumbs
 */
class MenuHelper extends Helper
{
    /**
     * @var list<string>
     */
    protected array $helpers = ['Breadcrumbs'];

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
        'resolveDepth' => null,
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
        if (isset($this->menus[$name]) && empty($options['overwrite'])) {
            throw new InvalidArgumentException(sprintf('Menu `%s` already exists.', $name));
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

    public function has(string $name): bool
    {
        return isset($this->menus[$name]);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function getOrCreate(string $name, array $options = []): MenuInterface
    {
        if ($this->has($name)) {
            return $this->get($name);
        }

        return $this->create($name, $options);
    }

    /**
     * @param string $name
     * @param callable(\Menu\MenuInterface): void|callable(\Menu\MenuInterface, self): void $callback
     * @param array<string, mixed> $options
     */
    public function register(string $name, callable $callback, array $options = []): MenuInterface
    {
        if ($this->has($name) && empty($options['rebuild'])) {
            return $this->get($name);
        }

        if (!empty($options['rebuild'])) {
            $options['overwrite'] = true;
            $menu = $this->create($name, $options);

            $this->invokeRegisterCallback($callback, $menu);

            return $menu;
        }

        $menu = $this->getOrCreate($name, $options);
        $this->invokeRegisterCallback($callback, $menu);

        return $menu;
    }

    public function remove(string $name): static
    {
        unset($this->menus[$name], $this->menuConfigs[$name]);
        if ($this->lastMenuName === $name) {
            $this->lastMenuName = null;
        }

        return $this;
    }

    public function reset(): static
    {
        $this->menus = [];
        $this->menuConfigs = [];
        $this->lastMenuName = null;

        return $this;
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
        $state = $this->captureItemState($menu);
        try {
            $this->applyResolvers($menu, $resolvedOptions);

            return $this->getRenderer($resolvedOptions)->render($menu, $resolvedOptions);
        } finally {
            $this->restoreItemState($menu, $state);
        }
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function getCurrentItem(MenuInterface|string|null $menu = null, array $options = []): ?ItemInterface
    {
        [$menu, $resolvedOptions] = $this->resolveMenuAndOptions($menu, $options);
        $state = $this->captureItemState($menu);
        try {
            $this->applyResolvers($menu, $resolvedOptions);

            return $menu->getActiveItem();
        } finally {
            $this->restoreItemState($menu, $state);
        }
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
     * @return list<array{title: string, url: array<string|int, mixed>|string|null, options: array<string, mixed>}>
     */
    public function getBreadcrumbs(MenuInterface|string|null $menu = null, array $options = []): array
    {
        [$menu, $resolvedOptions] = $this->resolveMenuAndOptions($menu, $options);
        $state = $this->captureItemState($menu);
        try {
            $this->applyResolvers($menu, $resolvedOptions);

            $currentItem = $menu->getActiveItem();
            if ($currentItem === null) {
                return [];
            }

            $path = $this->extractPath($currentItem);
            $linkCurrent = (bool)($resolvedOptions['linkCurrent'] ?? false);

            $crumbs = [];
            foreach ($path as $index => $item) {
                $isCurrent = $index === count($path) - 1;
                $link = $item->getLink();
                $optionsForCrumb = (array)($item->getData('breadcrumbOptions') ?? []);
                if ($isCurrent) {
                    $optionsForCrumb['innerAttrs']['aria-current'] = 'page';
                }

                $crumbs[] = [
                    'title' => (string)$item->getLabel(),
                    'url' => $link !== null && (!$isCurrent || $linkCurrent) ? $link->getRawUrl() : null,
                    'options' => $optionsForCrumb,
                ];
            }

            return $crumbs;
        } finally {
            $this->restoreItemState($menu, $state);
        }
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function populateBreadcrumbs(MenuInterface|string|null $menu = null, array $options = []): static
    {
        $reset = !array_key_exists('resetBreadcrumbs', $options) || (bool)$options['resetBreadcrumbs'];
        if ($reset) {
            $this->Breadcrumbs->reset();
        }

        $this->Breadcrumbs->addMany($this->getBreadcrumbs($menu, $options));

        return $this;
    }

    /**
     * @phpstan-param array<string, mixed> $options
     * @phpstan-param array<string, mixed> $attributes
     * @phpstan-param array<string, mixed> $separator
     */
    public function renderBreadcrumbs(
        MenuInterface|string|null $menu = null,
        array $options = [],
        array $attributes = [],
        array $separator = [],
    ): string {
        $renderer = $options['renderer'] ?? null;
        if ($renderer === BreadcrumbRenderer::class || $renderer instanceof BreadcrumbRenderer) {
            [$resolvedMenu, $resolvedOptions] = $this->resolveMenuAndOptions($menu, $options);
            $state = $this->captureItemState($resolvedMenu);
            try {
                $this->applyResolvers($resolvedMenu, $resolvedOptions);
                $activeItem = $resolvedMenu->getActiveItem();
                if ($activeItem === null) {
                    return '';
                }

                $resolvedOptions['path'] = $this->extractPath($activeItem);

                return $this->getRenderer(['renderer' => BreadcrumbRenderer::class] + $resolvedOptions)->render($resolvedMenu, $resolvedOptions);
            } finally {
                $this->restoreItemState($resolvedMenu, $state);
            }
        }

        $this->populateBreadcrumbs($menu, $options);

        return $this->Breadcrumbs->render($attributes, $separator);
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

        $menu->resetState();
        $menu->resolve($resolver);

        if (!empty($options['singleActive'])) {
            $this->enforceSingleActive($menu);
        }
    }

    /**
     * Keeps only the best (deepest) active item active, deactivating the rest.
     *
     * Resolvers can mark several items active for one request; this picks the most specific match
     * — the deepest in the tree, breaking ties by document order — so `getActiveItem()` and
     * breadcrumbs follow a single trail.
     */
    protected function enforceSingleActive(MenuInterface $menu): void
    {
        /** @var list<array{item: \Menu\Item\ItemInterface, depth: int, visible: bool}> $active */
        $active = [];
        $this->collectActiveItems($menu, 1, true, $active);
        if ($active === []) {
            return;
        }

        // Best match = the deepest active item that actually renders (visible, with visible
        // ancestors), breaking ties by document order.
        $best = null;
        $bestDepth = -1;
        foreach ($active as $entry) {
            if ($entry['visible'] && $entry['depth'] > $bestDepth) {
                $best = $entry['item'];
                $bestDepth = $entry['depth'];
            }
        }

        // Deactivate every other active item, including hidden ones that getActiveItem() would
        // otherwise still walk into.
        foreach ($active as $entry) {
            if ($entry['item'] === $best) {
                continue;
            }
            $item = $entry['item'];
            if ($item instanceof StateResetInterface) {
                $item->setRuntimeActive(false);
            } else {
                $item->setActive(false);
            }
        }
    }

    /**
     * Collects every active item in the tree, recording its depth and whether it (and all its
     * ancestors) are visible — i.e. whether it would actually render.
     *
     * @param \Menu\MenuInterface $menu
     * @param int $depth
     * @param bool $ancestorsVisible
     * @param list<array{item: \Menu\Item\ItemInterface, depth: int, visible: bool}> $active
     */
    protected function collectActiveItems(MenuInterface $menu, int $depth, bool $ancestorsVisible, array &$active): void
    {
        foreach ($menu->getItems() as $item) {
            $visible = $ancestorsVisible && $item->isVisible();
            if ($item->isActive()) {
                $active[] = ['item' => $item, 'depth' => $depth, 'visible' => $visible];
            }
            if ($item->hasSubMenu()) {
                $this->collectActiveItems($item->getSubMenu(), $depth + 1, $visible, $active);
            }
        }
    }

    protected function invokeRegisterCallback(callable $callback, MenuInterface $menu): void
    {
        $reflectionFunction = new ReflectionFunction(Closure::fromCallable($callback));
        if ($reflectionFunction->getNumberOfParameters() <= 1) {
            $callback($menu);

            return;
        }

        $callback($menu, $this);
    }

    /**
     * @return array<int, array{visible: bool, active: bool, expanded: bool}>
     */
    protected function captureItemState(MenuInterface $menu): array
    {
        $state = [];
        foreach ($menu->getItems() as $item) {
            $state[spl_object_id($item)] = [
                'visible' => $item->isVisible(),
                'active' => $item->isActive(),
                'expanded' => $item->isExpanded(),
            ];
            if ($item->hasSubMenu()) {
                $state += $this->captureItemState($item->getSubMenu());
            }
        }

        return $state;
    }

    /**
     * @param \Menu\MenuInterface $menu
     * @param array<int, array{visible: bool, active: bool, expanded: bool}> $state
     */
    protected function restoreItemState(MenuInterface $menu, array $state): void
    {
        foreach ($menu->getItems() as $item) {
            $itemState = $state[spl_object_id($item)] ?? null;
            if ($itemState !== null) {
                if ($item instanceof StateResetInterface) {
                    $item->setRuntimeVisibility($itemState['visible']);
                    $item->setRuntimeActive($itemState['active']);
                    $item->setRuntimeExpanded($itemState['expanded']);
                } else {
                    $item->setVisibility($itemState['visible']);
                    $item->setActive($itemState['active']);
                    $item->setExpanded($itemState['expanded']);
                }
            }
            if ($item->hasSubMenu()) {
                $this->restoreItemState($item->getSubMenu(), $state);
            }
        }
    }

    /**
     * @phpstan-param array<string, mixed> $options
     *
     * @throws \InvalidArgumentException When `additionalResolvers` contains a non-resolver entry.
     */
    protected function createDefaultResolver(array $options): ResolverCollectionInterface
    {
        $collection = (new ResolverCollection())
            ->add(new UrlArrayResolver(
                $this->getView()->getRequest(),
                [
                    'fuzzy' => $options['fuzzy'] ?? true,
                    'maxDepth' => $options['resolveDepth'] ?? null,
                ],
            ))
            ->add(new Psr7UrlResolver(
                $this->getView()->getRequest(),
                [
                    'ignoreQueryString' => $options['ignoreQueryString'] ?? true,
                    'maxDepth' => $options['resolveDepth'] ?? null,
                ],
            ));

        $additional = $options['additionalResolvers'] ?? [];
        if (is_array($additional)) {
            foreach ($additional as $resolver) {
                if (!$resolver instanceof ResolverInterface) {
                    throw new InvalidArgumentException(
                        'Each additionalResolvers entry must implement ' . ResolverInterface::class . '.',
                    );
                }
                $collection->add($resolver);
            }
        }

        return $collection;
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
