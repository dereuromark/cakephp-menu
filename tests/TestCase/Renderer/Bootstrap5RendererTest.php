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
        $parent->getSubMenu()->addItem('Profile', '/profile');

        $result = (new Bootstrap5Renderer())->render($menu);

        $this->assertStringContainsString('<a class="nav-link" href="/">Home</a>', $result);
        $this->assertStringContainsString('class="dropdown"', $result);
        $this->assertStringContainsString('class="dropdown-menu"', $result);
        $this->assertStringContainsString('class="dropdown-item" href="/profile"', $result);
    }
}
