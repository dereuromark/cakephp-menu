<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\StringTemplateRenderer;

class StringTemplateRendererTest extends TestCase
{
    public function testRendersEscapedLabelAndActiveState(): void
    {
        $menu = Menu::create();
        $menu->addItem('First<>', '/first', ['id' => 'first']);
        $menu->addItem('Second', '/second', ['id' => 'second', 'active' => true]);

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('First&lt;&gt;', $result);
        $this->assertStringContainsString('<li class="active"><a href="/second">Second</a></li>', $result);
    }

    public function testRendersRawItemWithoutEscaping(): void
    {
        $menu = Menu::create();
        $menu->addRaw('<strong>Raw</strong>');

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('<li><strong>Raw</strong></li>', $result);
    }
}
