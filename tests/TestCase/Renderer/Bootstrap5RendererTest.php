<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\Bootstrap5Renderer;

class Bootstrap5RendererTest extends TestCase
{
    public function testRendersBootstrapStyleClasses(): void
    {
        $menu = Menu::create(['class' => 'navbar-nav']);
        $menu->addItem('Home', '/');
        $parent = $menu->addItem('Account', '#');
        $settings = $parent->getSubMenu()->addItem('Settings', '#');
        $settings->getSubMenu()->addItem('Profile', '/profile', ['active' => true]);

        $renderer = new Bootstrap5Renderer();
        $result = $renderer->render($menu);
        $secondResult = $renderer->render($menu);

        $this->assertStringContainsString('href="/"', $result);
        $this->assertStringContainsString('class="nav-link"', $result);
        $this->assertMatchesRegularExpression('/class="[^"]*dropdown[^"]*"/', $result);
        $this->assertStringContainsString('class="dropdown-menu"', $result);
        $this->assertStringContainsString('class="dropdown-item dropdown-toggle"', $result);
        $this->assertStringContainsString('aria-expanded="true"', $result);
        $this->assertStringContainsString('href="/profile"', $result);
        $this->assertStringContainsString('class="dropdown-item"', $result);
        $this->assertSame($result, $secondResult);
        $this->assertStringNotContainsString('Array', $secondResult);
    }
}
