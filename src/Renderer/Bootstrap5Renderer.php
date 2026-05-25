<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Menu\Item\ItemInterface;

class Bootstrap5Renderer extends StringTemplateRenderer
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'activeClass' => 'active',
        'ancestorClass' => 'active',
        'branchClass' => 'dropdown',
        'submenuClass' => null,
        'addAriaExpanded' => false,
        'nestedMenuClass' => 'dropdown-menu',
        'menuLevelClass' => null,
        'firstClass' => null,
        'lastClass' => null,
        'currentAsLink' => true,
        // Framework-specific bits, defaulting to Bootstrap 5; override for BS4/other.
        'linkClass' => 'nav-link',
        'childLinkClass' => 'dropdown-item',
        'toggleClass' => 'dropdown-toggle',
        'toggleAttribute' => 'data-bs-toggle',
        'toggleValue' => 'dropdown',
        'templates' => [
            'menuWrapper' => '<ul{{attributes}}>{{items}}</ul>',
            'item' => '<li{{attributes}}>{{content}}</li>',
            'link' => '<a{{attributes}}>{{title}}</a>',
            'label' => '<span{{attributes}}>{{title}}</span>',
            'divider' => '<li{{attributes}}><hr class="dropdown-divider"></li>',
        ],
    ];

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
            if ($item->isActive() && $this->getBooleanOption($options, 'addAriaCurrent', true)) {
                $attributes['aria-current'] = 'page';
            }

            return $this->templater()->format('label', [
                'attributes' => $this->renderAttributes($attributes),
                'title' => $label,
            ]);
        }

        $attributes = $link->getAttributes();
        $attributes['href'] = $link->getUrl() ?? '#';
        $baseLinkClass = $item->hasParent()
            ? $this->getStringOption($options, 'childLinkClass')
            : $this->getStringOption($options, 'linkClass');
        $attributes = $this->appendClass($attributes, $baseLinkClass);
        if ($item->hasSubMenu()) {
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

        return $this->templater()->format('link', [
            'attributes' => $this->renderAttributes($attributes),
            'title' => $label,
        ]);
    }
}
