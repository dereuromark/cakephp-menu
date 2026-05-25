<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;
use Menu\Item\StateResetInterface;

/**
 * Shared helpers for resolvers to set item state, preferring the runtime setters (which a later
 * resetState() restores) when the item supports them.
 */
trait RuntimeStateTrait
{
    protected function applyActive(ItemInterface $item, bool $active = true): void
    {
        if ($item instanceof StateResetInterface) {
            $item->setRuntimeActive($active);

            return;
        }

        $item->setActive($active);
    }

    protected function applyVisibility(ItemInterface $item, bool $visible): void
    {
        if ($item instanceof StateResetInterface) {
            $item->setRuntimeVisibility($visible);

            return;
        }

        $item->setVisibility($visible);
    }

    protected function applyExpanded(ItemInterface $item, bool $expanded = true): void
    {
        if ($item instanceof StateResetInterface) {
            $item->setRuntimeExpanded($expanded);

            return;
        }

        $item->setExpanded($expanded);
    }
}
