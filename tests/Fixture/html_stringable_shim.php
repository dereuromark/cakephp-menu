<?php

declare(strict_types=1);

// Stand-in for CakePHP 5.next's interface, so the trusted-HTML tests also run on CakePHP 5.3.

namespace Cake\View;

use Stringable;

if (!interface_exists(HtmlStringable::class)) {
    interface HtmlStringable extends Stringable
    {
    }
}
