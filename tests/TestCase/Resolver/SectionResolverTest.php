<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Resolver;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Resolver\SectionResolver;

class SectionResolverTest extends TestCase
{
    public function testMatchesControllerSectionAndExpandsItem(): void
    {
        $item = (new Item('Articles', '/articles'))->setData('section', [
            'controller' => 'Articles',
            'prefix' => 'Admin',
        ]);

        $request = (new ServerRequest())
            ->withAttribute('params', [
                'controller' => 'Articles',
                'prefix' => 'Admin',
                'action' => 'index',
            ]);

        (new SectionResolver($request))->resolve($item);

        $this->assertTrue($item->isActive());
        $this->assertTrue($item->isExpanded());
    }
}
