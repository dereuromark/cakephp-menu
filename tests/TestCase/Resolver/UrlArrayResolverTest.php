<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Resolver\UrlArrayResolver;

class UrlArrayResolverTest extends TestCase
{
    public function testMatchesControllerActionAndPass(): void
    {
        $item = new Item('View Article', [
            'controller' => 'Articles',
            'action' => 'view',
            42,
        ]);

        $request = new ServerRequest();
        $request = $request->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'view',
            'pass' => [42],
        ]);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }
}
