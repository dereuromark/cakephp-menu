<?php

declare(strict_types=1);

namespace Menu\Item;

interface StateResetInterface
{
    public function resetState(): static;

    public function setRuntimeVisibility(bool $isVisible): static;

    public function setRuntimeActive(bool $isActive): static;

    public function setRuntimeExpanded(bool $expanded = true): static;
}
