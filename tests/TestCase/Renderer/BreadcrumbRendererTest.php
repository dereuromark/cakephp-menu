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
        $this->assertStringContainsString('<li class="breadcrumb-item active"><span>View</span></li>', $result);
    }
}
