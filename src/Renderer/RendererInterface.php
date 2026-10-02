<?php

declare(strict_types=1);

namespace CakeMenu\Renderer;

use CakeMenu\Item\ItemInterface;
use CakeMenu\MenuInterface;

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
