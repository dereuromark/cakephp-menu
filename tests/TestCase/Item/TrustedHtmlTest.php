<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Item;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Menu;
use CakeMenu\Renderer\StringTemplateRenderer;
use CakeMenu\Test\Fixture\TrustedHtmlValue;
use Stringable;

class TrustedHtmlTest extends TestCase
{
    public function testTrustedValuesRenderUnescapedAndSurviveCache(): void
    {
        $menu = new Menu();
        $menu->addItem(new TrustedHtmlValue('<b>Home</b>'), '/', [
            'icon' => new TrustedHtmlValue('<i class="bi bi-house"></i>'),
            'badge' => new TrustedHtmlValue('<span>3</span>'),
        ]);

        foreach ([$menu, Menu::fromArray($menu->toArray())] as $candidate) {
            $html = (new StringTemplateRenderer())->render($candidate);

            $this->assertStringContainsString('<b>Home</b>', $html);
            $this->assertStringContainsString('<i class="bi bi-house"></i>', $html);
            $this->assertStringContainsString('<span>3</span>', $html);
        }
    }

    public function testPlainStringableIsEscaped(): void
    {
        $plain = new class implements Stringable {
            public function __toString(): string
            {
                return '<b>Home</b>';
            }
        };
        $menu = new Menu();
        $menu->addItem($plain, '/', ['icon' => $plain]);

        $html = (new StringTemplateRenderer())->render($menu);

        $this->assertStringNotContainsString('<b>Home</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;Home&lt;/b&gt;', $html);
    }

    public function testExplicitEscapeOptionWinsOverTrustedValue(): void
    {
        $menu = new Menu();
        $menu->addItem(new TrustedHtmlValue('<b>Home</b>'), '/', ['escape' => true]);

        $html = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('&lt;b&gt;Home&lt;/b&gt;', $html);
    }

    public function testItemSettersAcceptTrustedValues(): void
    {
        $item = new Item(new TrustedHtmlValue('<b>Label</b>'));
        $item->setIcon(new TrustedHtmlValue('<i></i>'));
        $item->setBadge(new TrustedHtmlValue('<span>1</span>'));

        $this->assertFalse($item->shouldEscapeLabel());
        $this->assertFalse($item->shouldEscapeIcon());
        $this->assertFalse($item->shouldEscapeBadge());
        $this->assertSame('<b>Label</b>', $item->getLabel());
    }
}
