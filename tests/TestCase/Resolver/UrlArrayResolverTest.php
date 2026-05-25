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

    public function testMatchesFuzzyControllerActionWithoutPass(): void
    {
        $item = (new Item('Articles', [
            'controller' => 'Articles',
            'action' => 'index',
        ]))
            ->addMatchRoute([
                'controller' => 'Articles',
                'action' => 'view',
            ])
            ->setFuzzyMatch();

        $request = (new ServerRequest())
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'view',
                'pass' => [42],
            ]);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testMatchesQueryStringInFuzzyRoute(): void
    {
        $item = (new Item('Filtered Articles', [
            'controller' => 'Articles',
            'action' => 'index',
        ]))
            ->addMatchRoute([
                'controller' => 'Articles',
                'action' => 'index',
                '?' => ['sort' => 'desc'],
            ])
            ->setFuzzyMatch();

        $request = (new ServerRequest())
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'index',
            ])
            ->withQueryParams(['sort' => 'desc', 'page' => '2']);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }
}
