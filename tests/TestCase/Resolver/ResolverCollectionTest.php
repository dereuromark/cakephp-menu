<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Resolver\AuthorizationResolver;
use CakeMenu\Resolver\CallbackResolver;
use CakeMenu\Resolver\LoggedInResolver;
use CakeMenu\Resolver\PermissionResolver;
use CakeMenu\Resolver\ResolverCollection;
use CakeMenu\Resolver\ResolverContext;

class ResolverCollectionTest extends TestCase
{
    public function testVisibilityResolversOnlyHideInEitherOrder(): void
    {
        $item = (new Item('Private'))->setData('auth', 'loggedIn')->setData('permission', 'read');
        $allow = new AuthorizationResolver(static fn (): bool => true);
        $deny = new LoggedInResolver(false);
        $permission = new PermissionResolver(new class {
            public function can(): bool
            {
                return false;
            }
        });

        foreach ([[$deny, $allow], [$allow, $deny], [$permission, $allow], [$allow, $permission]] as $resolvers) {
            $item->resetState();
            (new ResolverCollection())->addMany($resolvers)->resolve($item, new ResolverContext());
            $this->assertFalse($item->isVisible());
        }

        $item->resetState()->setVisible(false);
        $allow->resolve($item, new ResolverContext());
        $this->assertFalse($item->isVisible());
    }

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
