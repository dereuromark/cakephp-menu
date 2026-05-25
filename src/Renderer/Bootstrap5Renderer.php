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
        $link = $item->getLink();
        if ($link !== null) {
            $attributes = $link->getAttributes();
            if ($item->hasSubMenu()) {
                $attributes['class'] = array_filter(['nav-link', 'dropdown-toggle', $attributes['class'] ?? null]);
                $attributes['data-bs-toggle'] = 'dropdown';
                $attributes['role'] = $attributes['role'] ?? 'button';
                $attributes['aria-expanded'] = $item->isExpanded() || $item->isActive() ? 'true' : 'false';
                $link->setAttributes($attributes);
            } else {
                $attributes['class'] = array_filter(['dropdown-item', $attributes['class'] ?? null]);
                $link->setAttributes($attributes);
            }
        }

        return parent::renderContent($item, $options);
    }
}
