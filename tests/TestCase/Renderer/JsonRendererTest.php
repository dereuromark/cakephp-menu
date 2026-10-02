<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Menu;
use CakeMenu\Renderer\JsonRenderer;
use JsonException;

class JsonRendererTest extends TestCase
{
    public function testRendersMenuAsJson(): void
    {
        $menu = Menu::create(['class' => 'nav']);
        $menu->addItem('Articles', '/articles', ['id' => 'articles']);

        $result = (new JsonRenderer())->render($menu);
        $data = json_decode($result, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('nav', $data['attributes']['class']);
        $this->assertSame('articles', $data['items'][0]['id']);
    }

    public function testThrowsOnEncodingError(): void
    {
        $menu = Menu::create();
        $menu->add((new Item("\xB1\x31", '/broken'))->setId('broken'));

        $this->expectException(JsonException::class);

        (new JsonRenderer())->render($menu);
    }
}
