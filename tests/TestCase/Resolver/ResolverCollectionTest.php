<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Resolver\LoggedInResolver;
use CakeMenu\Resolver\ResolverCollection;

class ResolverCollectionTest extends TestCase
{
    public function testAppliesResolversInOrder(): void
    {
        $item = (new Item('Profile'))->setData('auth', 'loggedIn');
        $collection = (new ResolverCollection())->add(new LoggedInResolver(true));

        $collection->resolve($item);

        $this->assertTrue($item->isVisible());
        $this->assertCount(1, $collection->all());
    }
}
