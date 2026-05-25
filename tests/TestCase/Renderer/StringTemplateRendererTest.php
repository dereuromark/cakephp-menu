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
        $this->assertStringContainsString('<li class="active"><a href="/second" aria-current="page">Second</a></li>', $result);
    }

    public function testRendersRawItemWithoutEscaping(): void
    {
        $menu = Menu::create();
        $menu->addRaw('<strong>Raw</strong>');

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('<li><strong>Raw</strong></li>', $result);
    }

    public function testRendersAncestorAndCurrentAsText(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Articles', '/articles');
        $parent->getSubMenu()->addItem('View', '/articles/view', ['active' => true]);

        $result = (new StringTemplateRenderer([
            'currentAsLink' => false,
        ]))->render($menu);

        $this->assertStringContainsString('class="active-ancestor has-children"', $result);
        $this->assertStringContainsString('<span aria-current="page">View</span>', $result);
        $this->assertStringNotContainsString('<a href="/articles/view">View</a>', $result);
    }

    public function testRendersPositionAndLevelClasses(): void
    {
        $menu = Menu::create(['class' => 'main']);
        $menu->addItem('First', '/first');
        $parent = $menu->addItem('Second', '/second');
        $parent->getSubMenu()->addItem('Child', '/second/child');

        $result = (new StringTemplateRenderer([
            'firstClass' => 'first',
            'lastClass' => 'last',
            'menuLevelClass' => 'level-',
            'nestedMenuClass' => 'submenu',
        ]))->render($menu);

        $this->assertStringContainsString('<ul class="main level-1">', $result);
        $this->assertStringContainsString('<li class="first">', $result);
        $this->assertStringContainsString('class="has-children last"', $result);
        $this->assertStringContainsString('<ul class="submenu level-2">', $result);
    }

    public function testAddsAriaCurrentAndExpandedState(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Articles', '/articles');
        $parent->setExpanded();
        $parent->getSubMenu()->addItem('View', '/articles/view', ['active' => true]);

        $result = (new StringTemplateRenderer([
            'ariaLabel' => 'Main navigation',
        ]))->render($menu);

        $this->assertStringContainsString('<ul aria-label="Main navigation">', $result);
        $this->assertStringContainsString('aria-expanded="true"', $result);
        $this->assertStringContainsString('aria-current="page"', $result);
    }

    public function testActiveItemRenderedAsSpanGetsAriaCurrent(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/', ['active' => true]);

        $result = (new StringTemplateRenderer())->render($menu, ['currentAsLink' => false]);

        $this->assertStringContainsString('<span aria-current="page">Home</span>', $result);
    }

    public function testActiveItemWithoutLinkGetsAriaCurrent(): void
    {
        $menu = Menu::create();
        $menu->addItem('Section', null, ['active' => true]);

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('aria-current="page"', $result);
    }
}
