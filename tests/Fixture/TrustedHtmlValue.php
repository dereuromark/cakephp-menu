<?php

declare(strict_types=1);

namespace CakeMenu\Test\Fixture;

use Cake\View\HtmlStringable;

require_once __DIR__ . '/html_stringable_shim.php';

final class TrustedHtmlValue implements HtmlStringable
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
