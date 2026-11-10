<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Link;

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use CakeMenu\Link\Link;
use CakeMenu\Menu;
use LogicException;

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

    public function testFrozenItemAlsoFreezesItsLink(): void
    {
        $item = Menu::create()->addItem('Docs', '/docs');
        $item->freeze();
        $link = $item->getLink();
        $this->assertNotNull($link);

        foreach (
            [
                static fn () => $link->setUrl('/changed'),
                static fn () => $link->setAttribute('target', '_blank'),
                static fn () => $link->setAttributes(['rel' => 'noopener']),
            ] as $mutate
        ) {
            try {
                $mutate();
                $this->fail('Expected frozen link error.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('frozen menu link', $exception->getMessage());
            }
        }
    }
}
