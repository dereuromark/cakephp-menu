<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\NavbarRenderer;

class NavbarRendererTest extends TestCase
{
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
