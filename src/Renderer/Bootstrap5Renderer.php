<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Menu\Item\ItemInterface;

class Bootstrap5Renderer extends StringTemplateRenderer
{
    /**
     * @phpstan-param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->_defaultConfig = array_replace_recursive($this->_defaultConfig, [
            'ancestorClass' => 'active',
            'branchClass' => 'dropdown',
            'headerClass' => null,
            'itemClass' => 'nav-item',
            'addAriaExpanded' => false,
            'nestedMenuClass' => 'dropdown-menu',
            'linkClass' => 'nav-link',
            'childLinkClass' => 'dropdown-item',
            'toggleClass' => 'dropdown-toggle',
            'toggleAttribute' => 'data-bs-toggle',
            'toggleValue' => 'dropdown',
            'templates' => [
                'divider' => '<li{{attributes}}><hr class="dropdown-divider"></li>',
                'dropdownHeader' => '<li{{attributes}}><h6 class="dropdown-header">{{title}}</h6></li>',
            ],
        ]);
        parent::__construct($config);
    }

    protected function getHeaderTemplate(int $level): string
    {
        if ($level === 1 || $this->templater()->get('header') !== $this->_defaultConfig['templates']['header']) {
            return 'header';
        }

        return 'dropdownHeader';
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderContent(ItemInterface $item, array $options, int $level = 1): string
    {
        if ($item->isRaw()) {
            return (string)$item->getRaw();
        }

        $title = $this->decorateTitle($item, $this->escapeLabel($item, $options), $options);
        $link = $item->getLink();
        $renderChildren = $this->rendersSubMenu($item, $options, $level);
        $renderAsLabel = $link === null || ($item->isActive() && !$this->getBooleanOption($options, 'currentAsLink', true));
        if (!$renderChildren && $renderAsLabel) {
            $attributes = $link?->getAttributes() ?? [];
            unset($attributes['href']);
            if ($item->isActive() && $this->getBooleanOption($options, 'addAriaCurrent', true)) {
                $attributes['aria-current'] = 'page';
            }
            $attributes = $this->mergeLabelAttributes($attributes, $item);
            $attributes = $this->applyMenuItemRole($attributes, $item, $options);

            return $this->templater()->format('label', [
                'attributes' => $this->renderAttributes($attributes),
                'title' => $title,
            ]);
        }

        $attributes = $link?->getAttributes() ?? [];
        $attributes['href'] = $renderAsLabel ? '#' : ($link->getUrl() ?? '#');
        // Render level decides the link class: a standalone submenu render (`render($submenu)` or
        // `renderItem($child)`) treats its items as top-level, so they get `linkClass` (`nav-link`)
        // rather than `childLinkClass` (`dropdown-item`), matching the surrounding `<li>` class.
        $baseLinkClass = $level === 1
            ? $this->getStringOption($options, 'linkClass')
            : $this->getStringOption($options, 'childLinkClass');
        $attributes = $this->appendClass($attributes, $baseLinkClass);
        // Only treat as a dropdown when the submenu will actually render. Without the
        // displaysChildren() guard, an item with setDisplayChildren(false) still got
        // dropdown-toggle + data-bs-toggle even though its submenu is suppressed.
        if ($renderChildren) {
            $attributes = $this->appendClass($attributes, $this->getStringOption($options, 'toggleClass'));
            $toggleAttribute = $this->getStringOption($options, 'toggleAttribute');
            if ($toggleAttribute !== '') {
                $attributes[$toggleAttribute] = $this->getStringOption($options, 'toggleValue');
            }
            $attributes['role'] = $attributes['role'] ?? 'button';
            $attributes['aria-expanded'] = $item->isExpanded() || $item->isActive() || $this->hasActiveDescendant($item)
                ? 'true'
                : 'false';
        }
        if ($item->isActive() && $this->getBooleanOption($options, 'addAriaCurrent', true)) {
            $attributes['aria-current'] = 'page';
        }
        $attributes = $this->mergeLabelAttributes($attributes, $item);
        $attributes = $this->applyMenuItemRole($attributes, $item, $options);

        return $this->templater()->format('link', [
            'attributes' => $this->renderAttributes($attributes),
            'title' => $title,
        ]);
    }
}
