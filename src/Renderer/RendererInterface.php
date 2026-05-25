<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Menu\Item\ItemInterface;
use Menu\MenuInterface;

interface RendererInterface
{
    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface $menu, array $options = []): string;

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function renderItem(ItemInterface $item, array $options = []): string;
}
