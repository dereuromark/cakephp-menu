<?php

declare(strict_types=1);

namespace Menu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use Menu\Menu;
use Menu\Renderer\Bootstrap5Renderer;
use Menu\Renderer\Bootstrap5SidebarRenderer;
use Menu\Renderer\BreadcrumbRenderer;
use Menu\Renderer\NavbarRenderer;
use Menu\Renderer\StringTemplateRenderer;
use Throwable;

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

    // ---------------------------------------------------------------------
    // labelAttributes / roles parity across renderers. Previously these were
    // honored only by StringTemplateRenderer; the Bootstrap5-family renderers
    // override the link/label rendering path and silently dropped them.
    // ---------------------------------------------------------------------

    public function testLabelAttributesOnBootstrap5Link(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home')->setLabelAttributes(['title' => 'Go home', 'class' => 'text-primary']);

        $result = (new Bootstrap5Renderer())->render($menu);

        $this->assertStringContainsString('href="/home"', $result);
        $this->assertStringContainsString('title="Go home"', $result);
        // The renderer's nav-link class merges with the labelAttributes class.
        $this->assertMatchesRegularExpression('/class="[^"]*\bnav-link\b[^"]*\btext-primary\b[^"]*"/', $result);
    }

    public function testLabelAttributesOnBootstrap5ActiveLabel(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home', ['active' => true])->setLabelAttributes(['title' => 'Here']);

        $result = (new Bootstrap5Renderer(['currentAsLink' => false]))->render($menu);

        $this->assertStringContainsString('aria-current="page"', $result);
        // The active item renders as <span>, and labelAttributes land on it.
        $this->assertMatchesRegularExpression('/<span[^>]*title="Here"[^>]*>Home<\/span>/', $result);
    }

    public function testRolesAppliedByBootstrap5Renderer(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home');

        $result = (new Bootstrap5Renderer())->render($menu, ['roles' => true]);

        $this->assertStringContainsString('role="menubar"', $result);
        $this->assertStringContainsString('role="menuitem"', $result);
    }

    public function testLabelAttributesOnBootstrap5SidebarLeaf(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home')->setLabelAttributes(['title' => 'Go home', 'class' => 'text-primary']);

        $result = (new Bootstrap5SidebarRenderer())->render($menu);

        $this->assertStringContainsString('title="Go home"', $result);
        $this->assertMatchesRegularExpression('/class="[^"]*\bnav-link\b[^"]*\btext-primary\b/', $result);
    }

    public function testLabelAttributesOnBootstrap5SidebarPlaceholderToggle(): void
    {
        $menu = Menu::create();
        $branch = $menu->addItem('Features', '#', ['id' => 'features']);
        $branch->setLabelAttributes(['title' => 'Open features', 'class' => 'fw-bold']);
        $branch->getSubMenu()->addItem('Resolvers', '/resolvers');

        $result = (new Bootstrap5SidebarRenderer())->render($menu);

        // labelAttributes land on the toggle <a>, not on the <li> or the collapse <div>. The test
        // menu has only one toggle anchor, so finding both attributes on an <a> is sufficient
        // (the renderer emits data-bs-toggle before title in attribute order).
        $this->assertMatchesRegularExpression('/<a\b[^>]*\bdata-bs-toggle="collapse"[^>]*>/', $result);
        $this->assertMatchesRegularExpression('/<a\b[^>]*\btitle="Open features"[^>]*>/', $result);
        $this->assertStringContainsString('fw-bold', $result);
    }

    public function testLabelAttributesOnBootstrap5SidebarNavigableBranch(): void
    {
        $menu = Menu::create();
        $branch = $menu->addItem('Products', '/products', ['id' => 'products']);
        $branch->setLabelAttributes(['title' => 'Browse products']);
        $branch->getSubMenu()->addItem('Books', '/books');

        $result = (new Bootstrap5SidebarRenderer())->render($menu);

        // labelAttributes apply to the navigable anchor, not the separate collapse toggle button.
        $this->assertMatchesRegularExpression('/<a[^>]*href="\/products"[^>]*title="Browse products"/', $result);
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*title="Browse products"/', $result);
    }

    public function testLabelAttributesOnNavbarRenderer(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home')->setLabelAttributes(['title' => 'Go home', 'class' => 'text-primary']);

        $result = (new NavbarRenderer())->render($menu);

        // NavbarRenderer extends Bootstrap5Renderer, so the fix flows through inheritance.
        $this->assertStringContainsString('title="Go home"', $result);
        $this->assertMatchesRegularExpression('/class="[^"]*\bnav-link\b[^"]*\btext-primary\b/', $result);
    }

    // ---------------------------------------------------------------------
    // Bootstrap navbar `<li class="nav-item">` markup parity + level-aware
    // class selection for standalone submenu renders.
    // ---------------------------------------------------------------------

    public function testBootstrap5RendererAddsNavItemToTopLevelLi(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home');
        $account = $menu->addItem('Account', '#');
        $account->getSubMenu()->addItem('Profile', '/profile');

        $result = (new Bootstrap5Renderer())->render($menu);

        // Top-level <li>s carry `nav-item` (the canonical Bootstrap navbar pattern).
        $this->assertMatchesRegularExpression('/<li class="nav-item"><a[^>]*href="\/home"/', $result);
        // Branch <li>s combine `dropdown` (branchClass) with `nav-item`.
        $this->assertMatchesRegularExpression('/<li class="[^"]*\bdropdown\b[^"]*\bnav-item\b[^"]*"/', $result);
        // Submenu <li> (the `Profile` child) carries no `nav-item` — Bootstrap dropdown items
        // use a plain <li> with `<a class="dropdown-item">`.
        $this->assertMatchesRegularExpression('/<li><a[^>]*href="\/profile"[^>]*\bdropdown-item\b/', $result);
    }

    public function testNavbarRendererInheritsNavItem(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home');

        $result = (new NavbarRenderer())->render($menu);

        $this->assertMatchesRegularExpression('/<li class="nav-item"><a[^>]*href="\/home"/', $result);
    }

    public function testStandaloneSubmenuRenderUsesTopLevelClasses(): void
    {
        // A child rendered standalone should receive the top-level <li> class (`nav-item`) AND
        // the top-level link class (`nav-link`), not their child counterparts. Render level —
        // not the item's parent pointer — decides what "top" means.
        $menu = Menu::create();
        $parent = $menu->addItem('Account', '/account');
        $parent->getSubMenu()->addItem('Profile', '/profile');

        $result = (new Bootstrap5Renderer())->render($parent->getSubMenu());

        $this->assertMatchesRegularExpression('/<li class="nav-item"><a[^>]*href="\/profile"/', $result);
        $this->assertStringContainsString('class="nav-link"', $result);
        $this->assertStringNotContainsString('dropdown-item', $result);
    }

    public function testStringTemplateRendererItemClassOptIn(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home');
        $branch = $menu->addItem('Account', '#');
        $branch->getSubMenu()->addItem('Profile', '/profile');

        // Off by default: no extra <li> class on plain StringTemplateRenderer.
        $defaultResult = (new StringTemplateRenderer())->render($menu);
        $this->assertStringContainsString('<li><a href="/home">Home</a></li>', $defaultResult);

        // Opt-in via config: top <li> gets `itemClass`, submenu <li> gets `childItemClass`.
        $tunedResult = (new StringTemplateRenderer([
            'itemClass' => 'top',
            'childItemClass' => 'sub',
        ]))->render($menu);

        $this->assertMatchesRegularExpression('/<li class="top"><a[^>]*href="\/home"/', $tunedResult);
        $this->assertMatchesRegularExpression('/<li class="sub"><a[^>]*href="\/profile"/', $tunedResult);
    }

    // ---------------------------------------------------------------------
    // Per-call `templates` option must not leak into the renderer instance.
    // Previously render() called setConfig('templates', merged), which
    // permanently mutated the renderer; a subsequent render without the
    // option reused the mutated state.
    // ---------------------------------------------------------------------

    public function testPerCallTemplatesDoNotLeakInStringTemplateRenderer(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home');

        $renderer = new StringTemplateRenderer();
        $withOverride = $renderer->render($menu, [
            'templates' => ['menuWrapper' => '<nav><ul{{attributes}}>{{items}}</ul></nav>'],
        ]);
        $withoutOverride = $renderer->render($menu);

        $this->assertStringContainsString('<nav><ul>', $withOverride);
        // Subsequent render without the override must produce the original markup.
        $this->assertSame('<ul><li><a href="/home">Home</a></li></ul>', $withoutOverride);
    }

    public function testPerCallTemplatesDoNotLeakInBreadcrumbRenderer(): void
    {
        $menu = Menu::create();
        $menu->addItem('Home', '/home', ['active' => true]);

        $renderer = new BreadcrumbRenderer();
        $withOverride = $renderer->render($menu, [
            'templates' => ['menuWrapper' => '<ol>{{items}}</ol>'],
        ]);
        $withoutOverride = $renderer->render($menu);

        $this->assertStringContainsString('<ol>', $withOverride);
        // Default template restored after the overridden render.
        $this->assertStringContainsString('<nav aria-label="breadcrumb"><ol', $withoutOverride);
        $this->assertStringNotContainsString('<ol><li', $withoutOverride);
    }

    public function testTemplatesRestoredWhenRenderThrows(): void
    {
        // push/pop must restore templates even if rendering fails. Use an invalid template token
        // so format() throws, then confirm a subsequent render reverts to the default.
        $renderer = new StringTemplateRenderer();
        $menu = Menu::create();
        $menu->addItem('Home', '/home');

        try {
            $renderer->render($menu, [
                'templates' => ['menuWrapper' => '<broken {{ no_close'],
            ]);
        } catch (Throwable) {
            // Expected: malformed template.
        }

        $clean = $renderer->render($menu);
        $this->assertSame('<ul><li><a href="/home">Home</a></li></ul>', $clean);
    }
}
