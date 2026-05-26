<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Resolver\RegexResolver;

class RegexResolverTest extends TestCase
{
    public function testMatchesPattern(): void
    {
        $item = (new Item('Articles', '/articles'))->setData('match', '#^/articles#');

        (new RegexResolver('/articles/view/42'))->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testDoesNotMatch(): void
    {
        $item = (new Item('Users', '/users'))->setData('match', '#^/users#');

        (new RegexResolver('/articles'))->resolve($item);

        $this->assertFalse($item->isActive());
    }

    public function testMatchesAnyOfMultiplePatterns(): void
    {
        $item = (new Item('Content'))->setData('match', ['#^/articles#', '#^/pages#']);

        (new RegexResolver('/pages/about'))->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testNoDataLeavesItemUntouched(): void
    {
        $item = new Item('Home', '/');

        (new RegexResolver('/'))->resolve($item);

        $this->assertFalse($item->isActive());
    }

    public function testCustomDataKey(): void
    {
        $item = (new Item('X'))->setData('activePattern', '#^/x#');

        (new RegexResolver('/x/y', 'activePattern'))->resolve($item);

        $this->assertTrue($item->isActive());
    }

    public function testInvalidPatternIsIgnored(): void
    {
        $item = (new Item('X'))->setData('match', 'not-a-valid-regex(');

        (new RegexResolver('/x'))->resolve($item);

        $this->assertFalse($item->isActive());
    }
}
