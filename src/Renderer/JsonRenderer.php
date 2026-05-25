<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Menu\Item\ItemInterface;
use Menu\MenuInterface;

class JsonRenderer implements RendererInterface
{
    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface $menu, array $options = []): string
    {
        $flags = JSON_THROW_ON_ERROR;
        if (!empty($options['pretty'])) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($menu->toArray(), $flags);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function renderItem(ItemInterface $item, array $options = []): string
    {
        $flags = JSON_THROW_ON_ERROR;
        if (!empty($options['pretty'])) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($item->toArray(), $flags);
    }
}
