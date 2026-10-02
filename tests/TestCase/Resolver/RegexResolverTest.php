<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Resolver;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Resolver\RegexResolver;
use CakeMenu\Resolver\ResolverContext;

class RegexResolverTest extends TestCase
{
    public function testMatchesPattern(): void
    {
        $item = (new Item('Articles', '/articles'))->setData('match', '#^/articles#');

        (new RegexResolver((new ServerRequest())->withUri((new ServerRequest())->getUri()->withPath('/articles/view/42'))))->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testDoesNotMatch(): void
    {
        $item = (new Item('Users', '/users'))->setData('match', '#^/users#');

        (new RegexResolver((new ServerRequest())->withUri((new ServerRequest())->getUri()->withPath('/articles'))))->resolve($item, new ResolverContext());

        $this->assertFalse($item->isActive());
    }

    public function testMatchesAnyOfMultiplePatterns(): void
    {
        $item = (new Item('Content'))->setData('match', ['#^/articles#', '#^/pages#']);

        (new RegexResolver((new ServerRequest())->withUri((new ServerRequest())->getUri()->withPath('/pages/about'))))->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testNoDataLeavesItemUntouched(): void
    {
        $item = new Item('Home', '/');

        (new RegexResolver((new ServerRequest())->withUri((new ServerRequest())->getUri()->withPath('/'))))->resolve($item, new ResolverContext());

        $this->assertFalse($item->isActive());
    }

    public function testCustomDataKey(): void
    {
        $item = (new Item('X'))->setData('activePattern', '#^/x#');

        (new RegexResolver((new ServerRequest())->withUri((new ServerRequest())->getUri()->withPath('/x/y')), ['dataKey' => 'activePattern']))->resolve($item, new ResolverContext());

        $this->assertTrue($item->isActive());
    }

    public function testInvalidPatternIsIgnored(): void
    {
        $item = (new Item('X'))->setData('match', 'not-a-valid-regex(');

        (new RegexResolver((new ServerRequest())->withUri((new ServerRequest())->getUri()->withPath('/x'))))->resolve($item, new ResolverContext());

        $this->assertFalse($item->isActive());
    }

    public function testMaxDepth(): void
    {
        $request = (new ServerRequest())->withUri((new ServerRequest())->getUri()->withPath('/x'));
        $resolver = new RegexResolver($request, ['maxDepth' => 1]);
        $item = (new Item('X'))->setData('match', '#^/x$#');
        $resolver->resolve($item, new ResolverContext(2));
        $this->assertFalse($item->isActive());
        $resolver->resolve($item, new ResolverContext(1));
        $this->assertTrue($item->isActive());
    }
}
