<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Menu;

use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\StringTemplateRenderer;

class NestedUlListIntegrationTest extends TestCase
{
    public function testNestedMenuRendering(): void
    {
        $menu = Menu::create(['id' => 'menu1', 'class' => 'dropdown-menu']);
        $menu->addItem('Dashboard', '/users/dashboard');

        $subMenu = $menu->addItem('Sub Menu', '#', ['attributes' => ['class' => 'dropdown-submenu']]);
        $subMenu->getSubMenu()->setAttributes(['class' => 'dropdown-submenu']);
        $subMenu->add((new Menu())->newItem('Child 1', '/users/edit/1'));
        $subMenu->add((new Menu())->newItem('Child 2', '/users/edit/2'));

        $menu->addDivider();
        $menu->addItem('Logout', '/logout', ['before' => '<i class="fa fa-sign-out"></i>']);

        $renderer = new StringTemplateRenderer();
        $result = $renderer->render($menu);

        $this->assertStringContainsString('<ul id="menu1" class="dropdown-menu">', $result);
        $this->assertStringContainsString('<a href="/users/dashboard">Dashboard</a>', $result);
        $this->assertStringContainsString('class="dropdown-submenu has-children"', $result);
        $this->assertStringContainsString('<ul class="dropdown-submenu submenu">', $result);
        $this->assertStringContainsString('<li class="divider"></li>', $result);
        $this->assertStringContainsString('<i class="fa fa-sign-out"></i><a href="/logout">Logout</a>', $result);
    }
}
