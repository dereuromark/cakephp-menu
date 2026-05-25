<?php

declare(strict_types=1);

namespace Menu\Renderer;

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
}
