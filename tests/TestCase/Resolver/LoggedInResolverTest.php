<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Resolver\LoggedInResolver;

class LoggedInResolverTest extends TestCase
{
    public function testMarksLoggedOutOnlyItemsInvisible(): void
    {
        $item = (new Item('Login'))->setData('auth', 'loggedOut');

        (new LoggedInResolver(true))->resolve($item);

        $this->assertFalse($item->isVisible());
    }
}
