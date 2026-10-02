<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Resolver\CallbackResolver;
use CakeMenu\Resolver\LoggedInResolver;
use CakeMenu\Resolver\ResolverCollection;
use CakeMenu\Resolver\ResolverContext;

class ResolverCollectionTest extends TestCase
{
    public function testPassesSameContextToNestedCollections(): void
    {
        $item = new Item('Child');
        $parent = new Item('Parent');
        $context = new ResolverContext(2, $parent);
        $seen = [];
        $callback = new CallbackResolver(static function ($resolvedItem, $resolvedContext) use (&$seen): void {
            $seen[] = [$resolvedItem, $resolvedContext];
        });
        $nested = (new ResolverCollection())->add($callback);
        $collection = (new ResolverCollection())->addMany([$callback, $nested]);
        $collection->resolve($item, $context);

        $this->assertSame([[$item, $context], [$item, $context]], $seen);
    }

    public function testAppliesResolversInOrder(): void
    {
        $item = (new Item('Profile'))->setData('auth', 'loggedIn');
        $collection = (new ResolverCollection())->add(new LoggedInResolver(true));

        $collection->resolve($item, new ResolverContext());

        $this->assertTrue($item->isVisible());
        $this->assertCount(1, $collection->all());
    }
}
