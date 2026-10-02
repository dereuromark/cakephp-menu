<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Item\ItemInterface;
use CakeMenu\Resolver\AuthorizationResolver;
use CakeMenu\Resolver\ResolverContext;

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
        $resolver->resolve($item, new ResolverContext());

        $this->assertFalse($item->isVisible());
    }
}
