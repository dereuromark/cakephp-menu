<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use Menu\Link\Link;
use Menu\Menu;
use Menu\Renderer\Bootstrap5SidebarRenderer;

class Bootstrap5SidebarRendererTest extends TestCase
{
    public function testRendersCollapsibleSidebarWithActiveBranchOpen(): void
    {
        $menu = Menu::create();
        $menu->addItem('Dashboard', '/dashboard');
        $features = $menu->addItem('Features', '#', ['id' => 'features']);
        $features->getSubMenu()->addItem('Resolvers', '/resolvers');
        $features->getSubMenu()->addItem('Renderers', '/renderers', ['active' => true]);
        $more = $menu->addItem('More', '#', ['id' => 'more']);
        $more->getSubMenu()->addItem('Docs', '/docs');

        $renderer = new Bootstrap5SidebarRenderer();
        $result = $renderer->render($menu);
        $secondResult = $renderer->render($menu);

        // Vertical nav and items.
        $this->assertStringContainsString('<ul class="nav flex-column">', $result);
        $this->assertStringContainsString('<li class="nav-item">', $result);
        $this->assertStringContainsString('href="/dashboard"', $result);
        $this->assertStringContainsString('<ul class="nav flex-column ms-3">', $result);

        // The branch holding the active item is wired and expanded.
        $this->assertStringContainsString('data-bs-toggle="collapse"', $result);
        $this->assertStringContainsString('href="#menu-collapse-features"', $result);
        $this->assertStringContainsString('aria-controls="menu-collapse-features"', $result);
        $this->assertStringContainsString('aria-expanded="true"', $result);
        $this->assertStringContainsString('<div class="collapse show" id="menu-collapse-features">', $result);

        // The active leaf carries the active class and aria-current.
        $this->assertStringContainsString('class="nav-link active"', $result);
        $this->assertStringContainsString('href="/renderers"', $result);
        $this->assertStringContainsString('aria-current="page"', $result);

        // The branch without an active descendant stays collapsed.
        $this->assertStringContainsString('aria-expanded="false"', $result);
        $this->assertStringContainsString('<div class="collapse" id="menu-collapse-more">', $result);

        // Deterministic output, no array leakage into attributes.
        $this->assertSame($result, $secondResult);
        $this->assertStringNotContainsString('Array', $result);
    }

    public function testIdPrefixOptionAvoidsCollisions(): void
    {
        $menu = Menu::create();
        $branch = $menu->addItem('Account', '#', ['id' => 'account']);
        $branch->getSubMenu()->addItem('Profile', '/profile');

        $result = (new Bootstrap5SidebarRenderer())->render($menu, ['idPrefix' => 'aside-']);

        $this->assertStringContainsString('href="#aside-account"', $result);
        $this->assertStringContainsString('id="aside-account"', $result);
        $this->assertStringNotContainsString('menu-collapse-', $result);
    }

    public function testPreservesItemAndSubmenuAttributes(): void
    {
        $menu = Menu::create();
        $menu->addItem('Dashboard', '/dashboard', ['attributes' => ['class' => 'custom', 'data-x' => '1']]);
        $features = $menu->addItem('Features', '#', [
            'id' => 'features',
            'submenuAttributes' => ['class' => 'sub-extra', 'data-sub' => 'y'],
        ]);
        $features->getSubMenu()->addItem('Resolvers', '/resolvers');

        $result = (new Bootstrap5SidebarRenderer())->render($menu);

        // Item attributes survive on the <li> (alongside the item class).
        $this->assertMatchesRegularExpression('/<li class="custom nav-item" data-x="1">/', $result);
        // Submenu attributes survive on the nested <ul>.
        $this->assertStringContainsString('sub-extra', $result);
        $this->assertStringContainsString('data-sub="y"', $result);
    }

    public function testBranchWithRealUrlStaysNavigable(): void
    {
        $menu = Menu::create();
        $products = $menu->addItem('Products', '/products', ['id' => 'products']);
        $products->getSubMenu()->addItem('Books', '/books');

        $result = (new Bootstrap5SidebarRenderer())->render($menu);

        // The branch keeps its real destination...
        $this->assertStringContainsString('href="/products"', $result);
        // ...and a separate collapse toggle drives the section (data-bs-target, not href="#...").
        $this->assertStringContainsString('<button', $result);
        $this->assertStringContainsString('data-bs-target="#menu-collapse-products"', $result);
        $this->assertStringContainsString('id="menu-collapse-products"', $result);
    }

    public function testPreservesLinkAttributesAndMergesClasses(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', Link::create('/home', ['class' => 'text-danger', 'title' => 'Go home']));
        $menu->addItem('Current', Link::create('/current', ['title' => 'Here']), ['active' => true]);

        $result = (new Bootstrap5SidebarRenderer())->render($menu, ['currentAsLink' => false]);

        // The link keeps its own class (merged with nav-link) and its title.
        $this->assertStringContainsString('text-danger', $result);
        $this->assertStringContainsString('nav-link', $result);
        $this->assertStringContainsString('title="Go home"', $result);
        // The active item rendered as a label keeps its attributes (title) and has no href.
        $this->assertStringContainsString('title="Here"', $result);
        $this->assertMatchesRegularExpression('/<span[^>]*class="nav-link active"[^>]*>Current<\/span>/', $result);
        $this->assertStringNotContainsString('Array', $result);
    }

    public function testFrameworkHooksAreConfigurable(): void
    {
        $menu = Menu::create();
        $branch = $menu->addItem('Group', '#', ['id' => 'group']);
        $branch->getSubMenu()->addItem('Child', '/child', ['active' => true]);

        $result = (new Bootstrap5SidebarRenderer())->render($menu, [
            'collapseClass' => 'accordion-collapse',
            'expandedClass' => 'is-open',
            'toggleAttribute' => 'data-toggle',
            'caretOpen' => 'v',
            'caretClosed' => '>',
        ]);

        // Active branch open, using the overridden collapse/expanded classes.
        $this->assertStringContainsString('class="accordion-collapse is-open"', $result);
        $this->assertStringContainsString('data-toggle="collapse"', $result);
        $this->assertStringContainsString('>v</span>', $result);
        // Bootstrap defaults replaced, not appended.
        $this->assertStringNotContainsString('data-bs-toggle', $result);
    }

    public function testHideEmptyBranchesDropsChildlessBranch(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/');
        $empty = $menu->addItem('Empty', '#', ['id' => 'empty']);
        $empty->getSubMenu()->addItem('Hidden', '/hidden', ['visible' => false]);

        $result = (new Bootstrap5SidebarRenderer())->render($menu, ['hideEmptyBranches' => true]);

        $this->assertStringContainsString('href="/"', $result);
        $this->assertStringNotContainsString('id="menu-collapse-empty"', $result);
    }
}
