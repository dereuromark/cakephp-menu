<?php

declare(strict_types=1);

namespace Menu\Resolver;

use Menu\Item\ItemInterface;
use function is_string;
use function preg_match;
use function restore_error_handler;
use function set_error_handler;

/**
 * Marks items active when the current request path matches a regular expression stored in the item's
 * data (default key `match`). The value may be a single pattern or a list of patterns; the item is
 * activated when any pattern matches. Invalid patterns are ignored rather than raising a warning.
 *
 * Useful for activating a menu entry across a whole URL section that a route-array match cannot
 * express, e.g. `'#^/admin/(users|roles)#'`.
 */
class RegexResolver implements ResolverInterface
{
    use RuntimeStateTrait;

    public function __construct(
        protected string $path,
        protected string $dataKey = 'match',
    ) {
    }

    public function resolve(ItemInterface $item): void
    {
        $patterns = $item->getData($this->dataKey);
        if ($patterns === null) {
            return;
        }

        foreach ((array)$patterns as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }
            if ($this->matches($pattern)) {
                $this->applyActive($item);

                return;
            }
        }
    }

    /**
     * Tests a pattern against the request path, swallowing the warning an invalid pattern emits so a
     * bad entry never breaks rendering.
     */
    protected function matches(string $pattern): bool
    {
        set_error_handler(static fn (): bool => true);
        try {
            return preg_match($pattern, $this->path) === 1;
        } finally {
            restore_error_handler();
        }
    }
}
