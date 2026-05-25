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
        'submenuClass' => 'dropdown',
        'nestedMenuClass' => 'dropdown-menu',
        'menuLevelClass' => null,
        'firstClass' => null,
        'lastClass' => null,
        'currentAsLink' => true,
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

            return $this->templater()->format('label', [
                'attributes' => $this->renderAttributes($attributes),
                'title' => $label,
            ]);
        }

        $attributes = $link->getAttributes();
        $attributes['href'] = $link->getUrl() ?? '#';
        $baseLinkClass = $item->hasParent() ? 'dropdown-item' : 'nav-link';
        if ($item->hasSubMenu()) {
            $attributes['class'] = array_filter([$baseLinkClass, 'dropdown-toggle', $attributes['class'] ?? null]);
            $attributes['data-bs-toggle'] = 'dropdown';
            $attributes['role'] = $attributes['role'] ?? 'button';
            $attributes['aria-expanded'] = $item->isExpanded() || $item->isActive() || $this->hasActiveDescendant($item)
                ? 'true'
                : 'false';
        } else {
            $attributes['class'] = array_filter([$baseLinkClass, $attributes['class'] ?? null]);
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
