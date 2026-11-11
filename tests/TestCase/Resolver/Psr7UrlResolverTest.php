<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Item\ItemInterface;
use CakeMenu\Resolver\Psr7UrlResolver;
use CakeMenu\Resolver\ResolverContext;

class Psr7UrlResolverTest extends TestCase
{
    public function testCustomItemMatchingSettings(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->method('getMatchRoutes')->willReturn(['/users?sort=asc']);
        $item->method('getIgnoreQueryString')->willReturn(false);
        $item->expects($this->never())->method('setRuntimeActive');
        $request = new ServerRequest(['url' => '/users?sort=desc']);
        $request = $request->withUri($request->getUri()->withPath('/users')->withQuery('sort=desc'));
        (new Psr7UrlResolver($request))->resolve($item, new ResolverContext());
    }

    public function testResolvesCurrentStringUrl(): void
    {
        $item = new Item('User Listing', '/users?sort=desc');
        $request = new ServerRequest(['url' => '/users?sort=desc']);
        $request = $request->withUri($request->getUri()->withPath('/users')->withQuery('sort=desc'));

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testIgnoresQueryStringByDefault(): void
    {
        $item = new Item('User Listing', '/users');
        $request = new ServerRequest(['url' => '/users?sort=desc']);
        $request = $request->withUri($request->getUri()->withPath('/users')->withQuery('sort=desc'));

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testMatchesAdditionalStringRoutes(): void
    {
        $item = (new Item('Dashboard', '/dashboard'))
            ->addMatchRoute('/users');
        $request = new ServerRequest(['url' => '/users']);
        $request = $request->withUri($request->getUri()->withPath('/users'));

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testQuerySensitiveMatchWithAbsoluteRequestUri(): void
    {
        // Real requests carry scheme + host in the URI string.
        $item = new Item('User Listing', '/users?sort=desc');
        $request = new ServerRequest(['url' => '/users?sort=desc']);

        $resolver = new Psr7UrlResolver($request, ['ignoreQueryString' => false]);
        $resolver->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testQuerySensitiveMatchRejectsDifferentQuery(): void
    {
        $item = new Item('User Listing', '/users?sort=desc');
        $request = new ServerRequest(['url' => '/users?sort=asc']);

        $resolver = new Psr7UrlResolver($request, ['ignoreQueryString' => false]);
        $resolver->resolve($item, new ResolverContext());

        $this->assertFalse($item->isActive());
    }

    public function testQuerySensitiveMatchIsOrderInsensitive(): void
    {
        $item = new Item('Filtered', '/users?sort=desc&page=2');
        $request = new ServerRequest(['url' => '/users?page=2&sort=desc']);

        $resolver = new Psr7UrlResolver($request, ['ignoreQueryString' => false]);
        $resolver->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testDoesNotMatchAbsoluteUrlOnDifferentHost(): void
    {
        // An absolute URL to another host must not light up on a same-path local request.
        $item = new Item('External Users', 'https://other.example/users');
        $request = new ServerRequest(['url' => '/users']);

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item, new ResolverContext());

        $this->assertFalse($item->isActive());
    }

    public function testQuerySensitiveMatchDistinguishesRepeatedKeys(): void
    {
        $item = new Item('Tags', '/articles?tag=a&tag=b');
        $request = new ServerRequest(['url' => '/articles?tag=b']);

        $resolver = new Psr7UrlResolver($request, ['ignoreQueryString' => false]);
        $resolver->resolve($item, new ResolverContext());

        $this->assertFalse($item->isActive());
    }

    public function testMatchesAbsoluteUrlWithExplicitDefaultPort(): void
    {
        // The request URI reports the default port as null; an explicit :80 must still match.
        $item = new Item('Users', 'http://localhost:80/users');
        $request = new ServerRequest(['url' => '/users']);

        $resolver = new Psr7UrlResolver($request);
        $resolver->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testMatchesWhenRequestPathStillContainsBase(): void
    {
        $request = (new ServerRequest())
            ->withUri((new ServerRequest())->getUri()->withPath('/subdir/users'))
            ->withAttribute('base', '/subdir');

        $item = new Item('Users', '/users');
        (new Psr7UrlResolver($request))->resolve($item, new ResolverContext());
        $other = new Item('Other', '/other');
        (new Psr7UrlResolver($request))->resolve($other, new ResolverContext());

        $this->assertTrue($item->isActive());
        $this->assertFalse($other->isActive());
    }

    public function testMatchesAppRelativeStringUrlUnderBasePath(): void
    {
        $request = (new ServerRequest(['url' => '/users']))->withAttribute('base', '/subdir');
        $this->assertSame('/subdir', $request->getAttribute('base'));
        $this->assertSame('/users', $request->getUri()->getPath());
        Router::setRequest($request);
        try {
            $item = new Item('Users', '/users');
            $this->assertSame('/subdir/users', $item->getLink()?->getUrl());
            (new Psr7UrlResolver($request))->resolve($item, new ResolverContext());
            $this->assertTrue($item->isActive());

            $withBase = new Item('Users with base', '/subdir/users');
            (new Psr7UrlResolver($request))->resolve($withBase, new ResolverContext());
            $this->assertTrue($withBase->isActive());

            $absolute = new Item('Absolute', 'http://localhost/subdir/users');
            (new Psr7UrlResolver($request))->resolve($absolute, new ResolverContext());
            $this->assertTrue($absolute->isActive());
        } finally {
            Router::reload();
        }
    }

    public function testSkipsLinksWithoutPathAndOtherSchemes(): void
    {
        foreach (['/', '/users'] as $path) {
            $request = new ServerRequest(['url' => $path]);
            foreach (['', '#', '#section', '?page=2', 'javascript:void(0)', 'mailto:x@example.com', 'tel:123'] as $route) {
                $item = new Item('Link', $route);
                (new Psr7UrlResolver($request))->resolve($item, new ResolverContext());
                $this->assertFalse($item->isActive(), $path . ': ' . $route);
            }
        }
    }

    public function testNormalizesStringUrlPath(): void
    {
        $request = new ServerRequest(['url' => '/caf%C3%A9/']);
        $item = new Item('Cafe', '/café');

        (new Psr7UrlResolver($request))->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testEncodedSlashDoesNotMatchPathSeparator(): void
    {
        $request = new ServerRequest(['url' => '/files/a/b']);
        $item = new Item('File', '/files/a%2Fb');

        (new Psr7UrlResolver($request))->resolve($item, new ResolverContext());

        $this->assertFalse($item->isActive());
    }
}
