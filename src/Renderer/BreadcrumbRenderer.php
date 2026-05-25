<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Menu\Item\ItemInterface;
use Menu\MenuInterface;
use function htmlspecialchars;
use function implode;
use const ENT_QUOTES;

class BreadcrumbRenderer extends StringTemplateRenderer
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'activeClass' => 'active',
        'itemClass' => 'breadcrumb-item',
        'menuClass' => 'breadcrumb',
        'ariaLabel' => 'breadcrumb',
        'templates' => [
            'menuWrapper' => '<nav aria-label="{{ariaLabel}}"><ol{{attributes}}>{{items}}</ol></nav>',
            'item' => '<li{{attributes}}>{{content}}</li>',
            'link' => '<a{{attributes}}>{{title}}</a>',
            'label' => '<span{{attributes}}>{{title}}</span>',
        ],
    ];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface $menu, array $options = []): string
    {
        if (isset($options['templates']) && is_array($options['templates'])) {
            $this->setConfig('templates', $options['templates'] + $this->getConfig('templates'));
        }

        $path = $options['path'] ?? null;
        if (!is_array($path)) {
            $activeItem = $menu->getActiveItem();
            if ($activeItem === null) {
                return '';
            }

            $path = [];
            while ($activeItem !== null) {
                $path[] = $activeItem;
                $activeItem = $activeItem->getParent();
            }
            $path = array_reverse($path);
        }

        $items = [];
        $count = count($path);
        foreach ($path as $index => $item) {
            if (!$item instanceof ItemInterface) {
                continue;
            }

            $items[] = $this->renderBreadcrumbItem($item, $options, $index === $count - 1);
        }

        $attributes = $this->appendClass([], (string)($options['menuClass'] ?? $this->getConfig('menuClass')));

        $ariaLabel = (string)($options['ariaLabel'] ?? $this->getConfig('ariaLabel'));

        return $this->templater()->format('menuWrapper', [
            'ariaLabel' => htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8'),
            'attributes' => $this->renderAttributes($attributes),
            'items' => implode('', $items),
        ]);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderBreadcrumbItem(ItemInterface $item, array $options, bool $isCurrent): string
    {
        $attributes = $this->appendClass([], (string)($options['itemClass'] ?? $this->getConfig('itemClass')));
        if ($isCurrent) {
            $attributes = $this->appendClass($attributes, (string)($options['activeClass'] ?? $this->getConfig('activeClass')));
        }

        $content = $this->renderContent($item, ['currentAsLink' => !$isCurrent] + $options);

        return $this->templater()->format('item', [
            'attributes' => $this->renderAttributes($attributes),
            'content' => $content,
        ]);
    }
}
