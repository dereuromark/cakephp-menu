<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\Http\ServerRequest;
use Cake\Routing\Route\Route;
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

    public function testMatchesNamedRoute(): void
    {
        $item = new Item('View Article', ['_name' => 'articles:view']);

        $request = (new ServerRequest())
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'view',
                '_route' => new Route('/articles/{id}', ['controller' => 'Articles', 'action' => 'view'], ['_name' => 'articles:view']),
            ]);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testMatchesExactArrayRouteWhenFuzzyDisabled(): void
    {
        $item = (new Item('View Article', [
            'controller' => 'Articles',
            'action' => 'view',
            42,
        ]))->setFuzzyMatch(false);

        $request = (new ServerRequest())->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'view',
            'plugin' => null,
            'pass' => [42],
        ]);

        $resolver = new UrlArrayResolver($request, ['fuzzy' => false]);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testDoesNotMatchExtraPassedArgsWhenFuzzyDisabled(): void
    {
        $item = (new Item('View Article', [
            'controller' => 'Articles',
            'action' => 'view',
        ]))->setFuzzyMatch(false);

        $request = (new ServerRequest())->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'view',
            'plugin' => null,
            'pass' => [42],
        ]);

        $resolver = new UrlArrayResolver($request, ['fuzzy' => false]);
        $resolver->resolve($item);

        $this->assertFalse($item->isActive());
    }

    public function testExplicitPerItemExactMatchOverridesGlobalFuzzySetting(): void
    {
        $item = (new Item('Articles', [
            'controller' => 'Articles',
        ]))->setFuzzyMatch(false);

        $request = (new ServerRequest())->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'view',
            'plugin' => null,
            'pass' => [],
        ]);

        $resolver = new UrlArrayResolver($request, ['fuzzy' => true]);
        $resolver->resolve($item);

        $this->assertFalse($item->isActive());
    }

    public function testMatchesPluginFalseAgainstNullRequestPlugin(): void
    {
        $item = new Item('Home', [
            'plugin' => false,
            'controller' => 'Pages',
            'action' => 'display',
        ]);

        $request = (new ServerRequest())->withAttribute('params', [
            'plugin' => null,
            'controller' => 'Pages',
            'action' => 'display',
            'pass' => ['home'],
        ]);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testPluginFalseDoesNotMatchPluginRequest(): void
    {
        $item = new Item('App Pages', [
            'plugin' => false,
            'controller' => 'Pages',
            'action' => 'display',
        ]);

        $request = (new ServerRequest())->withAttribute('params', [
            'plugin' => 'Blog',
            'controller' => 'Pages',
            'action' => 'display',
            'pass' => ['home'],
        ]);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertFalse($item->isActive());
    }

    public function testMatchesArrayMatchRouteOnStringLinkItem(): void
    {
        $item = (new Item('Dashboard', '/dashboard'))
            ->addMatchRoute(['controller' => 'Articles', 'action' => 'index']);

        $request = (new ServerRequest())->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testMatchesArrayMatchRouteOnLinklessItem(): void
    {
        $item = (new Item('Section'))
            ->addMatchRoute(['controller' => 'Articles', 'action' => 'index']);

        $request = (new ServerRequest())->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $resolver = new UrlArrayResolver($request);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testExactQueryConstraintIsNotMaskedByRoutingKey(): void
    {
        $item = (new Item('Edit query', [
            'controller' => 'Articles',
            'action' => 'index',
            '?' => ['action' => 'edit'],
        ]))->setFuzzyMatch(false);

        // Link targets Articles/index?action=edit; the request is Articles/index with no query,
        // so the `?action=edit` constraint must not be satisfied by the routing `action` key.
        $request = (new ServerRequest())->withAttribute('params', [
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            'pass' => [],
        ]);

        $resolver = new UrlArrayResolver($request, ['fuzzy' => false]);
        $resolver->resolve($item);

        $this->assertFalse($item->isActive());
    }

    public function testExactMatchHonorsQueryString(): void
    {
        $item = (new Item('Sorted', [
            'controller' => 'Articles',
            'action' => 'index',
            '?' => ['sort' => 'desc'],
        ]))->setFuzzyMatch(false);

        $request = (new ServerRequest())
            ->withAttribute('params', [
                'controller' => 'Articles',
                'action' => 'index',
                'plugin' => null,
                'pass' => [],
            ])
            ->withQueryParams(['sort' => 'desc']);

        $resolver = new UrlArrayResolver($request, ['fuzzy' => false]);
        $resolver->resolve($item);

        $this->assertTrue($item->isActive());
    }
}
