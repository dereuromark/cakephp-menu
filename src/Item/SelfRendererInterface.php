<?php

declare(strict_types=1);

namespace Menu\Item;

interface SelfRendererInterface
{
    public function render(): string;
}
