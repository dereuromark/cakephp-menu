<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\StringTemplateRenderer;

class RendererTranslateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear('_cake_translations_');
        I18n::clear();
        I18n::setLocale('en_US');
    }

    protected function tearDown(): void
    {
        Cache::clear('_cake_translations_');
        I18n::clear();

        parent::tearDown();
    }

    public function testTranslateDefaultsOff(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('>Home<', $result);
    }

    public function testTranslateTranslatesLabel(): void
    {
        I18n::setTranslator('default', function (): Package {
            $package = new Package('default');
            $package->setMessages(['Home' => 'Translated Home']);

            return $package;
        }, 'en_US');

        $menu = Menu::create();
        $menu->addItem('Home', '/');

        $result = (new StringTemplateRenderer())->render($menu, ['translate' => true]);

        $this->assertStringContainsString('>Translated Home<', $result);
    }

    public function testTranslateAppliesToHeaderItems(): void
    {
        I18n::setTranslator('default', function (): Package {
            $package = new Package('default');
            $package->setMessages(['Account' => 'Translated Account']);

            return $package;
        }, 'en_US');

        $menu = Menu::create();
        $menu->addHeader('Account');

        $result = (new StringTemplateRenderer())->render($menu, ['translate' => true]);

        $this->assertStringContainsString('>Translated Account<', $result);
    }
}
