<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\BreadcrumbRenderer;

class BreadcrumbRendererTest extends TestCase
{
    public function testRendersActivePath(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Articles', '/articles');
        $parent->getSubMenu()->addItem('View', '/articles/view', ['active' => true]);

        $result = (new BreadcrumbRenderer())->render($menu);

        $this->assertStringContainsString('<nav aria-label="breadcrumb">', $result);
        $this->assertStringContainsString('<ol class="breadcrumb">', $result);
        $this->assertStringContainsString('<a href="/articles">Articles</a>', $result);
        $this->assertStringContainsString('<li class="breadcrumb-item active"><span aria-current="page">View</span></li>', $result);
    }

    public function testEscapesAriaLabel(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/', ['active' => true]);

        $result = (new BreadcrumbRenderer())->render($menu, [
            'ariaLabel' => 'x" onload="alert(1)',
        ]);

        $this->assertStringNotContainsString('onload="alert(1)"', $result);
        $this->assertStringContainsString('aria-label="x&quot; onload=&quot;alert(1)"', $result);
    }

    public function testRendersItemIcon(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/', ['active' => true, 'icon' => 'fa fa-home']);

        $result = (new BreadcrumbRenderer())->render($menu);

        $this->assertStringContainsString('<i class="fa fa-home" aria-hidden="true"></i> ', $result);
    }

    public function testRenderItemHonorsPerCallTemplateOverrides(): void
    {
        $item = Menu::create()->newItem('Home', '/');

        $result = (new BreadcrumbRenderer())->renderItem($item, [
            'templates' => [
                'link' => '<a data-test="custom"{{attributes}}>{{title}}</a>',
            ],
        ]);

        $this->assertSame('<li class="breadcrumb-item"><a data-test="custom" href="/">Home</a></li>', $result);
    }
}
