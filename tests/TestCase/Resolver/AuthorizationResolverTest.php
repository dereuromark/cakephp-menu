<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Item\ItemInterface;
use Menu\Resolver\AuthorizationResolver;
use Menu\Resolver\ResolverContext;

class AuthorizationResolverTest extends TestCase
{
    public function testHidesUnauthorizedItems(): void
    {
        $item = (new Item('Admin', '/admin'))->setData('role', 'admin');

        $resolver = new AuthorizationResolver(static function (ItemInterface $item, ResolverContext $context): ?bool {
            if ($item->getData('role') === 'admin') {
                return false;
            }

            return null;
        });
        $resolver->resolve($item);

        $this->assertFalse($item->isVisible());
    }
}
