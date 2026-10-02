<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use CakeMenu\Link\Link;
use CakeMenu\Menu;
use CakeMenu\Renderer\Bootstrap5Renderer;
use CakeMenu\Renderer\StringTemplateRenderer;

class Bootstrap5RendererTest extends TestCase
{
    public function testDepthCutoffRendersBranchesWithoutDropdownToggle(): void
    {
        $menu = Menu::create();
        $menu->addItem('Account')->getSubMenu()->addItem('Profile', '/p');
        $menu->addItem('Docs', '/docs')->getSubMenu()->addItem('API', '/api');

        $result = (new Bootstrap5Renderer())->render($menu, ['depth' => 1]);

        $this->assertSame(
            '<ul><li class="nav-item"><span>Account</span></li><li class="nav-item"><a href="/docs" class="nav-link">Docs</a></li></ul>',
            $result,
        );
    }

    public function testHideEmptyBranchesKeepsCutoffChildren(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '/parent');
        $child = $parent->getSubMenu()->addItem('Child', '/child');
        $child->getSubMenu()->addItem('Hidden', '/hidden')->setVisibility(false);

        $result = (new Bootstrap5Renderer())->render($menu, ['depth' => 2, 'hideEmptyBranches' => true]);

        $this->assertStringContainsString('<a href="/child" class="dropdown-item">Child</a>', $result);
    }

    public function testInheritsParentOptionsAndRendersHeadersAndDividers(): void
    {
        $renderer = new Bootstrap5Renderer();
        $parentConfig = (new StringTemplateRenderer())->getConfig();
        foreach ($parentConfig as $key => $value) {
            $this->assertArrayHasKey($key, $renderer->getConfig());
        }
        foreach ($parentConfig['templates'] as $key => $value) {
            $this->assertArrayHasKey($key, $renderer->getConfig('templates'));
        }

        $menu = Menu::create();
        $menu->addHeader('Head');
        $menu->addDivider();
        $branch = $menu->addItem('Account', '#');
        $branch->getSubMenu()->addHeader('Dropdown head');
        $branch->getSubMenu()->addDivider();
        $branch->getSubMenu()->addItem('Profile', '/p');

        $result = $renderer->render($menu);
        $this->assertStringContainsString('<li>Head</li>', $result);
        $this->assertStringContainsString('<li><h6 class="dropdown-header">Dropdown head</h6></li>', $result);
        $this->assertSame(2, substr_count($result, '<li class="divider"><hr class="dropdown-divider"></li>'));

        $result = $renderer->render($menu, [
            'headerClass' => 'heading',
            'dividerClass' => 'separator',
            'leafClass' => 'leaf',
            'firstClass' => 'first',
            'lastClass' => 'last',
            'roles' => true,
        ]);
        $this->assertStringContainsString('<li class="first heading" role="presentation">Head</li>', $result);
        $this->assertStringContainsString('<li class="first heading" role="presentation"><h6 class="dropdown-header">Dropdown head</h6></li>', $result);
        $this->assertSame(2, substr_count($result, '<li class="separator" role="separator"><hr class="dropdown-divider"></li>'));
        $this->assertStringContainsString('<li class="leaf last" role="none">', $result);
    }

    public function testHeaderTemplateOverridesApplyAtEveryLevel(): void
    {
        $menu = Menu::create();
        $menu->addHeader('Head');
        $menu->addItem('Account', '#')->getSubMenu()->addHeader('Child');
        $templates = ['header' => '<li{{attributes}}><strong>{{title}}</strong></li>'];
        $renderer = new Bootstrap5Renderer();

        $result = $renderer->render($menu, ['templates' => $templates]);
        $this->assertStringContainsString('<li><strong>Head</strong></li>', $result);
        $this->assertStringContainsString('<li><strong>Child</strong></li>', $result);
        $this->assertStringContainsString('<h6 class="dropdown-header">Child</h6>', $renderer->render($menu));
        $this->assertStringContainsString(
            '<li><strong>Child</strong></li>',
            (new Bootstrap5Renderer(['templates' => $templates]))->render($menu),
        );
    }

    public function testBranchesAlwaysRenderDropdownToggles(): void
    {
        foreach ([false, true] as $active) {
            foreach ([null, '/account'] as $url) {
                $menu = Menu::create();
                $branch = $menu->addItem('Account', $url, ['active' => $active]);
                $branch->getSubMenu()->addItem('Profile', '/p');
                $renderer = new Bootstrap5Renderer();
                $result = $renderer->render($menu, ['currentAsLink' => false]);
                $expected = '<a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-expanded="'
                    . ($active ? 'true' : 'false') . '"'
                    . ($active ? ' aria-current="page"' : '') . '>Account</a>';
                if ($url !== null && !$active) {
                    $expected = str_replace('href="#"', 'href="/account"', $expected);
                }
                $this->assertStringContainsString($expected, $result);
                $this->assertStringContainsString('<ul class="dropdown-menu">', $result);
                if ($active) {
                    $this->assertStringContainsString('<li class="active dropdown nav-item">', $result);
                }

                $branch->setDisplayChildren(false);
                if ($url === null || $active) {
                    $this->assertStringContainsString('<span', $renderer->render($menu, ['currentAsLink' => false]));
                }
                $this->assertStringNotContainsString('dropdown-toggle', $renderer->render($menu));
            }
        }
    }

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

    public function testFrameworkClassesAndToggleAttributeAreConfigurable(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');
        $parent = $menu->addItem('Account', '#');
        $parent->getSubMenu()->addItem('Profile', '/profile');

        $result = (new Bootstrap5Renderer())->render($menu, [
            'linkClass' => 'menu-link',
            'childLinkClass' => 'menu-sublink',
            'toggleClass' => 'menu-toggle',
            'toggleAttribute' => 'data-toggle',
        ]);

        $this->assertStringContainsString('class="menu-link"', $result);
        $this->assertStringContainsString('menu-toggle', $result);
        $this->assertStringContainsString('menu-sublink', $result);
        $this->assertStringContainsString('data-toggle="dropdown"', $result);
        // Bootstrap defaults are fully replaced, not appended.
        $this->assertStringNotContainsString('data-bs-toggle', $result);
        $this->assertStringNotContainsString('nav-link', $result);
        $this->assertStringNotContainsString('dropdown-item', $result);
    }

    public function testRendersArrayLinkClassWithoutCorruption(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', Link::create('/', ['class' => ['text-primary', 'fw-bold']]));

        $result = (new Bootstrap5Renderer())->render($menu);

        $this->assertStringNotContainsString('Array', $result);
        $this->assertStringContainsString('nav-link', $result);
        $this->assertStringContainsString('text-primary', $result);
        $this->assertStringContainsString('fw-bold', $result);
    }
}
