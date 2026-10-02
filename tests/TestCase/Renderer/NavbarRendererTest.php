<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use CakeMenu\Menu;
use CakeMenu\Renderer\NavbarRenderer;
use CakeMenu\Renderer\StringTemplateRenderer;

class NavbarRendererTest extends TestCase
{
    public function testInheritsParentOptionsAndRendersHeadersAndDividers(): void
    {
        $renderer = new NavbarRenderer();
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
        $renderer = new NavbarRenderer();

        $result = $renderer->render($menu, ['templates' => $templates]);
        $this->assertStringContainsString('<li><strong>Head</strong></li>', $result);
        $this->assertStringContainsString('<li><strong>Child</strong></li>', $result);
        $this->assertStringContainsString('<h6 class="dropdown-header">Child</h6>', $renderer->render($menu));
        $this->assertStringContainsString(
            '<li><strong>Child</strong></li>',
            (new NavbarRenderer(['templates' => $templates]))->render($menu),
        );
    }

    public function testBranchesAlwaysRenderDropdownToggles(): void
    {
        foreach ([false, true] as $active) {
            foreach ([null, '/account'] as $url) {
                $menu = Menu::create();
                $branch = $menu->addItem('Account', $url, ['active' => $active]);
                $branch->getSubMenu()->addItem('Profile', '/p');
                $renderer = new NavbarRenderer();
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

    public function testRendersFullNavbarChrome(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');
        $account = $menu->addItem('Account', '#');
        $account->getSubMenu()->addItem('Profile', '/profile');

        $result = (new NavbarRenderer())->render($menu, [
            'brand' => 'MyApp',
            'brandUrl' => '/',
            'collapseId' => 'navbarNav',
        ]);

        $this->assertStringContainsString('<nav class="navbar navbar-expand-lg bg-body-tertiary">', $result);
        $this->assertStringContainsString('<div class="container-fluid">', $result);
        $this->assertStringContainsString('<a class="navbar-brand" href="/">MyApp</a>', $result);
        $this->assertStringContainsString('<button class="navbar-toggler"', $result);
        // Toggler, target and collapse id all agree.
        $this->assertStringContainsString('data-bs-target="#navbarNav"', $result);
        $this->assertStringContainsString('aria-controls="navbarNav"', $result);
        $this->assertStringContainsString('<div class="collapse navbar-collapse" id="navbarNav">', $result);
        // The inner list is a navbar-nav with nav-link items and a dropdown branch.
        $this->assertStringContainsString('<ul class="navbar-nav">', $result);
        $this->assertStringContainsString('class="nav-link"', $result);
        $this->assertMatchesRegularExpression('/class="[^"]*dropdown[^"]*"/', $result);
    }

    public function testOmitsBrandWhenNotProvided(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');

        $result = (new NavbarRenderer())->render($menu);

        $this->assertStringNotContainsString('navbar-brand', $result);
    }

    public function testCustomExpandThemeAndCollapseId(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');

        $result = (new NavbarRenderer())->render($menu, [
            'expand' => 'md',
            'theme' => 'bg-dark',
            'collapseId' => 'mainNav',
            'containerClass' => 'container',
        ]);

        $this->assertStringContainsString('<nav class="navbar navbar-expand-md bg-dark">', $result);
        $this->assertStringContainsString('<div class="container">', $result);
        $this->assertStringContainsString('id="mainNav"', $result);
        $this->assertStringContainsString('data-bs-target="#mainNav"', $result);
    }

    public function testEscapesBrand(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');

        $result = (new NavbarRenderer())->render($menu, ['brand' => '<b>x</b>']);

        $this->assertStringNotContainsString('<b>x</b>', $result);
    }

    public function testGeneratesUniqueCollapseIdsForMultipleNavbars(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');
        $renderer = new NavbarRenderer();

        $first = $renderer->render($menu);
        $second = $renderer->render($menu);

        preg_match('/<div class="collapse navbar-collapse" id="(navbar-collapse-\d+)">/', $first, $m1);
        preg_match('/<div class="collapse navbar-collapse" id="(navbar-collapse-\d+)">/', $second, $m2);
        $id1 = $m1[1] ?? '';
        $id2 = $m2[1] ?? '';

        $this->assertNotSame('', $id1);
        $this->assertNotSame($id1, $id2);
        // Each toggler targets its own collapse region.
        $this->assertStringContainsString('data-bs-target="#' . $id1 . '"', $first);
        $this->assertStringContainsString('data-bs-target="#' . $id2 . '"', $second);
    }

    public function testAriaLabelLabelsTheNavLandmarkNotTheList(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');

        $result = (new NavbarRenderer())->render($menu, ['ariaLabel' => 'Primary', 'collapseId' => 'x']);

        $this->assertStringContainsString('<nav class="navbar navbar-expand-lg bg-body-tertiary" aria-label="Primary">', $result);
        // The inner list is not labelled (no aria-label on the <ul>).
        $this->assertStringContainsString('<ul class="navbar-nav">', $result);
    }
}
