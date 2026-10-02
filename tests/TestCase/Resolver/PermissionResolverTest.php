<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Resolver\PermissionResolver;

class PermissionResolverTest extends TestCase
{
    public function testVariadicAuthorizerReceivesOnlyFixedArguments(): void
    {
        $item = (new Item('Admin', '/admin'))->setData('permission', 'admin.access');
        $authorizer = new class {
            /**
             * @var array<mixed>
             */
            public array $arguments = [];

            public function can(mixed $user, string $action, mixed $resource, mixed ...$optionalArgs): bool
            {
                $this->arguments = func_get_args();

                return false;
            }
        };
        $resolver = new PermissionResolver($authorizer);
        $resolver->resolve($item);
        $resolver->resolve($item);

        $this->assertSame([null, 'admin.access', $item], $authorizer->arguments);
        $this->assertFalse($item->isVisible());
    }

    public function testUsesAuthorizerCanMethod(): void
    {
        $item = (new Item('Admin', '/admin'))->setData('permission', 'admin.access');
        $authorizer = new class {
            public function can(mixed $identity, string $permission): bool
            {
                return $permission !== 'admin.access';
            }
        };

        (new PermissionResolver($authorizer, ['id' => 1]))->resolve($item);

        $this->assertFalse($item->isVisible());
    }

    public function testSupportsTwoArgumentCanMethod(): void
    {
        $item = (new Item('Admin', '/admin'))->setData('permission', 'admin.access');
        $authorizer = new class {
            public function can(mixed $identity, string $permission): bool
            {
                return $permission !== 'admin.access';
            }
        };

        (new PermissionResolver($authorizer, ['id' => 1]))->resolve($item);

        $this->assertFalse($item->isVisible());
    }

    public function testSupportsFourArgumentCanMethod(): void
    {
        $item = (new Item('Admin', '/admin'))->setData('permission', 'admin.access');
        $authorizer = new class {
            public function can(mixed $identity, string $permission, Item $item, object $context): bool
            {
                return $identity === ['id' => 1]
                    && $permission === 'admin.access'
                    && $item->getLabel() === 'Admin'
                    && method_exists($context, 'getDepth');
            }
        };

        (new PermissionResolver($authorizer, ['id' => 1]))->resolve($item);

        $this->assertTrue($item->isVisible());
    }
}
