<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Resolver\Psr7UrlResolver;

class Psr7UrlResolverTest extends TestCase
{
    public function testResolvesCurrentStringUrl(): void
    {
        $item = new Item('User Listing', '/users?sort=desc');
        $request = new ServerRequest(['url' => '/users?sort=desc']);
        $request = $request->withUri($request->getUri()->withPath('/users')->withQuery('sort=desc'));

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testIgnoresQueryStringByDefault(): void
    {
        $item = new Item('User Listing', '/users');
        $request = new ServerRequest(['url' => '/users?sort=desc']);
        $request = $request->withUri($request->getUri()->withPath('/users')->withQuery('sort=desc'));

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testMatchesAdditionalStringRoutes(): void
    {
        $item = (new Item('Dashboard', '/dashboard'))
            ->addMatchRoute('/users');
        $request = new ServerRequest(['url' => '/users']);
        $request = $request->withUri($request->getUri()->withPath('/users'));

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }
}
