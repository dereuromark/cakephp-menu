<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\StringTemplateRenderer;

class RendererFeaturesTest extends TestCase
{
    public function testDisplayChildrenFalseSuppressesSubmenu(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '/parent');
        $parent->getSubMenu()->addItem('Child', '/child');
        $parent->setDisplayChildren(false);

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertSame(
            '<ul><li><a href="/parent">Parent</a></li></ul>',
            $result,
        );
    }

    public function testDisplayChildrenTrueRendersSubmenu(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '/parent');
        $parent->getSubMenu()->addItem('Child', '/child');

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertSame(
            '<ul><li class="has-children" aria-expanded="false"><a href="/parent">Parent</a>'
            . '<ul class="submenu"><li><a href="/child">Child</a></li></ul></li></ul>',
            $result,
        );
    }

    public function testLabelAttributesOnLink(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home')->setLabelAttributes(['title' => 'Go home', 'class' => 'lbl']);

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertSame(
            '<ul><li><a href="/home" title="Go home" class="lbl">Home</a></li></ul>',
            $result,
        );
    }

    public function testLabelAttributesOnActiveLabel(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home', ['active' => true])->setLabelAttributes(['title' => 'Here']);

        $result = (new StringTemplateRenderer(['currentAsLink' => false]))->render($menu);

        $this->assertSame(
            '<ul><li class="active"><span aria-current="page" title="Here">Home</span></li></ul>',
            $result,
        );
    }

    public function testAriaRolesOffByDefault(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home');

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringNotContainsString('role=', $result);
    }

    public function testAriaRolesOnNestedMenu(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '/parent');
        $parent->getSubMenu()->addItem('Child', '/child');

        $result = (new StringTemplateRenderer())->render($menu, ['roles' => true]);

        $this->assertSame(
            '<ul role="menubar">'
            . '<li class="has-children" aria-expanded="false" role="none">'
            . '<a href="/parent" role="menuitem" aria-haspopup="true">Parent</a>'
            . '<ul class="submenu" role="menu">'
            . '<li role="none"><a href="/child" role="menuitem">Child</a></li>'
            . '</ul></li></ul>',
            $result,
        );
    }

    public function testAriaRolesDividerAndHeader(): void
    {
        $menu = Menu::create();
        $menu->addHeader('Section');
        $menu->addDivider();

        $result = (new StringTemplateRenderer())->render($menu, ['roles' => true]);

        $this->assertStringContainsString('<li class="menu-header" role="presentation">Section</li>', $result);
        $this->assertStringContainsString('<li class="divider" role="separator"></li>', $result);
    }
}
