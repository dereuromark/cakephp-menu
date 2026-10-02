<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Resolver\LoggedInResolver;
use CakeMenu\Resolver\ResolverContext;

class LoggedInResolverTest extends TestCase
{
    public function testMarksLoggedOutOnlyItemsInvisible(): void
    {
        $item = (new Item('Login'))->setData('auth', 'loggedOut');

        (new LoggedInResolver(true))->resolve($item, new ResolverContext());

        $this->assertFalse($item->isVisible());
    }
}
