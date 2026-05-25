<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Cake\Core\InstanceConfigTrait;
use Cake\View\StringTemplateTrait;
use Menu\Item\ItemInterface;
use Menu\Item\SelfRendererInterface;
use Menu\MenuInterface;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function sprintf;
use function trim;

class StringTemplateRenderer implements RendererInterface
{
    use InstanceConfigTrait;
    use StringTemplateTrait;

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'activeClass' => 'active',
        'ancestorClass' => 'active-ancestor',
        'dividerClass' => 'divider',
        'branchClass' => 'has-children',
        'leafClass' => null,
        'submenuClass' => 'has-children',
        'nestedMenuClass' => 'submenu',
        'menuLevelClass' => null,
        'firstClass' => null,
        'lastClass' => null,
        'depth' => null,
        'currentAsLink' => true,
        'templates' => [
            'menuWrapper' => '<ul{{attributes}}>{{items}}</ul>',
            'item' => '<li{{attributes}}>{{content}}</li>',
            'link' => '<a{{attributes}}>{{title}}</a>',
            'label' => '<span{{attributes}}>{{title}}</span>',
            'divider' => '<li{{attributes}}></li>',
        ],
    ];

    /**
     * @phpstan-param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface $menu, array $options = []): string
    {
        if (isset($options['templates']) && is_array($options['templates'])) {
            $this->setConfig('templates', array_merge($this->getConfig('templates'), $options['templates']));
        }

        return $this->renderMenu($menu, $options, 1);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function renderItem(ItemInterface $item, array $options = []): string
    {
        return $this->renderMenuItem($item, $options, 0, 1, 1);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderMenu(MenuInterface $menu, array $options, int $level): string
    {
        $depth = $this->getIntegerOption($options, 'depth');
        if ($depth !== null && $level > $depth) {
            return '';
        }

        $visibleItems = array_values(array_filter(
            $menu->getItems(),
            static fn (ItemInterface $item): bool => $item->isVisible(),
        ));
        $count = count($visibleItems);

        $items = [];
        foreach ($visibleItems as $index => $item) {
            $html = $this->renderMenuItem($item, $options, $index, $count, $level);
            if ($html !== '') {
                $items[] = $html;
            }
        }

        $attributes = $menu->getAttributes();
        if ($level > 1) {
            $nestedMenuClass = $this->getStringOption($options, 'nestedMenuClass');
            if ($nestedMenuClass !== '') {
                $attributes = $this->appendClass($attributes, $nestedMenuClass);
            }
        }

        $menuLevelClass = $this->getStringOption($options, 'menuLevelClass');
        if ($menuLevelClass !== '') {
            $attributes = $this->appendClass($attributes, $menuLevelClass . $level);
        }

        return $this->templater()->format('menuWrapper', [
            'attributes' => $this->renderAttributes($attributes),
            'items' => implode('', $items),
        ]);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderMenuItem(
        ItemInterface $item,
        array $options,
        int $index,
        int $count,
        int $level,
    ): string {
        if (!$item->isVisible()) {
            return '';
        }

        if ($item instanceof SelfRendererInterface) {
            return $item->render();
        }

        if ($item->isDivider()) {
            $attributes = $this->applyPositionClasses($item->getAttributes(), $options, $index, $count);
            $attributes = $this->appendConfiguredClass($attributes, $options, 'dividerClass');

            return $this->templater()->format('divider', [
                'attributes' => $this->renderAttributes($attributes),
            ]);
        }

        $attributes = $item->getAttributes();
        $hasSubMenu = $item->hasSubMenu();
        if ($item->isActive()) {
            $attributes = $this->appendConfiguredClass($attributes, $options, 'activeClass');
        } elseif ($this->hasActiveDescendant($item)) {
            $attributes = $this->appendConfiguredClass($attributes, $options, 'ancestorClass');
        }
        if ($hasSubMenu) {
            $attributes = $this->appendConfiguredClass($attributes, $options, 'branchClass');
            $attributes = $this->appendConfiguredClass($attributes, $options, 'submenuClass');
        } else {
            $attributes = $this->appendConfiguredClass($attributes, $options, 'leafClass');
        }
        $attributes = $this->applyPositionClasses($attributes, $options, $index, $count);

        $content = $item->getBefore() . $this->renderContent($item, $options) . $item->getAfter();
        if ($hasSubMenu) {
            $content .= $this->renderMenu($item->getSubMenu(), $options, $level + 1);
        }

        return $this->templater()->format('item', [
            'attributes' => $this->renderAttributes($attributes),
            'content' => $content,
        ]);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderContent(ItemInterface $item, array $options): string
    {
        if ($item->isRaw()) {
            return (string)$item->getRaw();
        }

        $label = $this->escapeLabel($item);
        $link = $item->getLink();
        if ($link === null || ($item->isActive() && !$this->getBooleanOption($options, 'currentAsLink', true))) {
            $attributes = $link?->getAttributes() ?? [];
            unset($attributes['href']);

            return $this->templater()->format('label', [
                'attributes' => $this->renderAttributes($attributes),
                'title' => $label,
            ]);
        }

        $attributes = $link->getAttributes();
        $attributes['href'] = $link->getUrl() ?? '#';

        return $this->templater()->format('link', [
            'attributes' => $this->renderAttributes($attributes),
            'title' => $label,
        ]);
    }

    protected function escapeLabel(ItemInterface $item): string
    {
        $label = $item->getLabel() ?? '';

        if (!$item->shouldEscapeLabel()) {
            return $label;
        }

        return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    }

    protected function hasActiveDescendant(ItemInterface $item): bool
    {
        if (!$item->hasSubMenu()) {
            return false;
        }

        foreach ($item->getSubMenu()->getItems() as $child) {
            if ($child->isActive() || $this->hasActiveDescendant($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    protected function renderAttributes(array $attributes): string
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $result[] = sprintf(' %s="%s"', $name, $name);

                continue;
            }

            if (is_array($value)) {
                $value = implode(' ', array_filter(array_map('strval', $value)));
            }

            $result[] = sprintf(
                ' %s="%s"',
                $name,
                htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'),
            );
        }

        return implode('', $result);
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     * @phpstan-param array<string, mixed> $options
     *
     * @phpstan-return array<string, mixed>
     */
    protected function applyPositionClasses(array $attributes, array $options, int $index, int $count): array
    {
        if ($count <= 0) {
            return $attributes;
        }

        if ($index === 0) {
            $attributes = $this->appendConfiguredClass($attributes, $options, 'firstClass');
        }
        if ($index === $count - 1) {
            $attributes = $this->appendConfiguredClass($attributes, $options, 'lastClass');
        }

        return $attributes;
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     * @phpstan-param array<string, mixed> $options
     *
     * @phpstan-return array<string, mixed>
     */
    protected function appendConfiguredClass(array $attributes, array $options, string $configKey): array
    {
        $class = $this->getStringOption($options, $configKey);
        if ($class === '') {
            return $attributes;
        }

        return $this->appendClass($attributes, $class);
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     *
     * @phpstan-return array<string, mixed>
     */
    protected function appendClass(array $attributes, string $class): array
    {
        if ($class === '') {
            return $attributes;
        }

        $existing = $attributes['class'] ?? [];
        if (!is_array($existing)) {
            $existing = trim((string)$existing) === '' ? [] : [trim((string)$existing)];
        }
        if (!in_array($class, $existing, true)) {
            $existing[] = $class;
        }

        $attributes['class'] = array_unique($existing);

        return $attributes;
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function getStringOption(array $options, string $key): string
    {
        $value = $options[$key] ?? $this->getConfig($key);

        return is_string($value) ? $value : '';
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function getIntegerOption(array $options, string $key): ?int
    {
        $value = $options[$key] ?? $this->getConfig($key);

        return is_int($value) ? $value : null;
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function getBooleanOption(array $options, string $key, bool $default): bool
    {
        $value = $options[$key] ?? $this->getConfig($key);

        return is_bool($value) ? $value : $default;
    }
}
