<?php

declare(strict_types=1);

namespace CakeMenu;

use Stringable;
use function is_a;

/**
 * Recognizes CakePHP's trusted HTML values (`Cake\View\HtmlStringable`, CakePHP 5.next+)
 * without requiring a CakePHP version that ships the interface.
 *
 * @internal
 */
final class TrustedHtml
{
    /**
     * The class name is a string so the check works (and is simply false) on CakePHP versions
     * that do not have the interface yet.
     *
     * @var string
     */
    public const INTERFACE = 'Cake\View\HtmlStringable';

    public static function isTrusted(mixed $value): bool
    {
        return $value instanceof Stringable && is_a($value, self::INTERFACE);
    }
}
