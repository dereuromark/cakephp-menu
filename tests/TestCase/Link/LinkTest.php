<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Link;

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Menu\Link\Link;

class LinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Router::reload();
        Router::defaultRouteClass(DashedRoute::class);
        Router::createRouteBuilder('/')->fallbacks();
    }

    public function testBuildsInternalAndExternalUrls(): void
    {
        $internal = Link::create('/users/dashboard');
        $external = Link::create('https://cakephp.org', external: true);

        $this->assertSame('/users/dashboard', $internal->getUrl());
        $this->assertSame('https://cakephp.org', $external->getUrl());
        $this->assertTrue($external->isExternal());
    }

    public function testBuildsArrayUrl(): void
    {
        $link = Link::create([
            'controller' => 'Articles',
            'action' => 'view',
            42,
        ]);

        $this->assertSame('/articles/view/42', $link->getUrl());
    }
}
