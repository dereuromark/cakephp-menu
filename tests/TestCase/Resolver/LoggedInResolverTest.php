<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Menu;
use CakeMenu\Resolver\AuthState;
use CakeMenu\Resolver\LoggedInResolver;
use CakeMenu\Resolver\ResolverContext;
use InvalidArgumentException;

class LoggedInResolverTest extends TestCase
{
    public function testMarksLoggedOutOnlyItemsInvisible(): void
    {
        $item = (new Item('Login'))->setData('auth', 'loggedOut');

        (new LoggedInResolver(true))->resolve($item, new ResolverContext());

        $this->assertFalse($item->isVisible());
    }

    public function testEnumAndStringStates(): void
    {
        foreach ([AuthState::LoggedIn, 'loggedIn'] as $state) {
            $menu = Menu::fromArray(['items' => [['label' => 'Profile', 'data' => ['auth' => $state]]]]);
            $menu->resolve(new LoggedInResolver(false));
            $this->assertFalse($menu->getItems()[0]->isVisible());
        }
        $item = (new Item('Login'))->setData('auth', AuthState::LoggedOut);
        (new LoggedInResolver(true))->resolve($item, new ResolverContext());
        $this->assertFalse($item->isVisible());
    }

    public function testUnknownStateThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new LoggedInResolver(true))->resolve((new Item('X'))->setData('auth', 'invalid'), new ResolverContext());
    }
}
