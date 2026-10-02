<?php

declare(strict_types=1);

namespace CakeMenu\Item;

interface SelfRendererInterface
{
    public function render(): string;
}
