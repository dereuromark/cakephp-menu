<?php

declare(strict_types=1);

namespace CakeMenu\Test\TestCase\Renderer;

use Cake\TestSuite\TestCase;
use CakeMenu\Item\Item;
use CakeMenu\Item\SelfRendererInterface;
use CakeMenu\Menu;
use CakeMenu\Renderer\StringTemplateRenderer;
use InvalidArgumentException;

class StringTemplateRendererTest extends TestCase
{
    public function testActiveOnForLinksAndLabels(): void
    {
        foreach (['item', 'link', 'both'] as $activeOn) {
            foreach ([true, false] as $asLink) {
                $menu = Menu::create();
                $parent = $menu->addItem('Parent', '/parent');
                $parent->getSubMenu()->addItem('Child', '/child', ['active' => true]);
                $html = (new StringTemplateRenderer())->render($menu, [
                    'activeOn' => $activeOn,
                    'currentAsLink' => $asLink,
                ]);
                $this->assertMatchesRegularExpression(
                    '/<li class="' . ($activeOn === 'link' ? 'has-children' : 'active-ancestor has-children') . '">/',
                    $html,
                );
                if ($activeOn !== 'item') {
                    $this->assertStringContainsString('class="active-ancestor"', $html);
                }
                $this->assertStringContainsString(
                    '<li' . ($activeOn === 'link' ? '>' : ' class="active">'),
                    $html,
                );
                $this->assertMatchesRegularExpression(
                    '/<' . ($asLink ? 'a' : 'span') . '[^>]*' . ($activeOn === 'item' ? 'aria-current' : 'class="active"') . '[^>]*>Child</',
                    $html,
                );
            }
        }
    }

    public function testInvalidActiveOnThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new StringTemplateRenderer())->renderItem(Menu::create()->newItem('Home', '/'), ['activeOn' => 'other']);
    }

    public function testActiveOnLinkMarksUnlinkedLabels(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent');
        $parent->getSubMenu()->addItem('Child', null, ['active' => true]);

        $html = (new StringTemplateRenderer())->render($menu, ['activeOn' => 'link']);

        $this->assertStringContainsString('<span class="active-ancestor" aria-expanded="true">Parent</span>', $html);
        $this->assertStringContainsString('<span aria-current="page" class="active">Child</span>', $html);
    }

    public function testAriaExpandedBelongsToLinkOrLabel(): void
    {
        foreach ([true, false] as $expanded) {
            foreach ([true, false] as $asLink) {
                $menu = Menu::create();
                $parent = $menu->addItem('Parent', $asLink ? '/parent' : null);
                $parent->setExpanded($expanded);
                $parent->getSubMenu()->addItem('Child', '/child');
                $html = (new StringTemplateRenderer())->render($menu);
                $this->assertStringContainsString(
                    '<' . ($asLink ? 'a href="/parent"' : 'span') . ' aria-expanded="' . ($expanded ? 'true' : 'false') . '">Parent',
                    $html,
                );
                $this->assertStringNotContainsString('<li class="has-children" aria-expanded=', $html);
                $this->assertStringNotContainsString('aria-expanded=', (new StringTemplateRenderer())->render($menu, ['addAriaExpanded' => false]));
            }
        }
    }

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

    public function testHideEmptyBranchesSkipsBranchWithoutVisibleChildren(): void
    {
        $menu = Menu::create();
        $group = $menu->addItem('Admin', '#');
        $group->getSubMenu()->addItem('Users', '/admin/users')->setVisible(false);
        $menu->addItem('Home', '/');

        $result = (new StringTemplateRenderer())->render($menu, ['hideEmptyBranches' => true]);

        $this->assertStringNotContainsString('Admin', $result);
        $this->assertStringContainsString('Home', $result);
    }

    public function testEmptyBranchesRenderByDefault(): void
    {
        $menu = Menu::create();
        $group = $menu->addItem('Admin', '#');
        $group->getSubMenu()->addItem('Users', '/admin/users')->setVisible(false);

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('Admin', $result);
    }

    public function testHideEmptyBranchesRecursesThroughNestedBranches(): void
    {
        $menu = Menu::create();
        $admin = $menu->addItem('Admin', '#');
        $settings = $admin->getSubMenu()->addItem('Settings', '#');
        $settings->getSubMenu()->addItem('Users', '/admin/users')->setVisible(false);
        $reports = $menu->addItem('Reports', '#');
        $reports->getSubMenu()->addItem('Daily', '/reports/daily');

        $result = (new StringTemplateRenderer())->render($menu, ['hideEmptyBranches' => true]);

        $this->assertStringNotContainsString('Admin', $result);
        $this->assertStringNotContainsString('Settings', $result);
        $this->assertStringContainsString('Reports', $result);
        $this->assertStringContainsString('Daily', $result);
    }

    public function testHideEmptyBranchesKeepsSelfRenderingChild(): void
    {
        $menu = Menu::create();
        $group = $menu->addItem('Admin', '#');
        $self = new class ('Self', '/self') extends Item implements SelfRendererInterface {
            public function render(): string
            {
                return '<li class="self-item">self</li>';
            }
        };
        // The self-rendering child has only hidden descendants, but it still renders itself.
        $self->getSubMenu()->addItem('Hidden', '/hidden')->setVisible(false);
        $group->add($self);

        $result = (new StringTemplateRenderer())->render($menu, ['hideEmptyBranches' => true]);

        $this->assertStringContainsString('self-item', $result);
        $this->assertStringContainsString('Admin', $result);
    }

    public function testRendersSectionHeader(): void
    {
        $menu = Menu::create();
        $menu->addHeader('Account');
        $menu->addItem('Profile', '/profile');

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('<li class="menu-header">Account</li>', $result);
        $this->assertStringContainsString('<a href="/profile">Profile</a>', $result);
    }

    public function testRendersIconAndBadge(): void
    {
        $menu = Menu::create();
        $menu->addItem('Inbox', '/inbox', ['icon' => 'fa fa-inbox', 'badge' => 5, 'badgeType' => 'bg-danger']);

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringContainsString('<i class="fa fa-inbox" aria-hidden="true"></i> ', $result);
        $this->assertStringContainsString('<span class="badge bg-danger">5</span>', $result);
    }

    public function testEscapesIconAndBadge(): void
    {
        $menu = Menu::create();
        $menu->addItem('X', '/x', ['icon' => '"><script>', 'badge' => '<b>']);

        $result = (new StringTemplateRenderer())->render($menu);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('<b>', $result);
    }

    public function testIconAndBadgeCanOptOutOfEscapingIndependently(): void
    {
        $menu = Menu::create();
        $item = new Item('Inbox', '/inbox');
        $menu->add($item);
        $item->setIcon('<i class="bi bi-inbox" aria-hidden="true"></i>', false)
            ->setBadge('<strong>3</strong>', 'bg-danger', false);

        $result = (new StringTemplateRenderer())->render($menu, [
            'iconTemplate' => '{{icon}} ',
        ]);

        $this->assertStringContainsString('<i class="bi bi-inbox" aria-hidden="true"></i> ', $result);
        $this->assertStringContainsString('<span class="badge bg-danger"><strong>3</strong></span>', $result);
    }

    public function testEmptyNestedListsAreOmittedAndPositionClassesUseRenderedItems(): void
    {
        $menu = Menu::create();
        $empty = $menu->addItem('Empty', '#');
        $empty->getSubMenu()->addItem('Hidden', '/hidden')->setVisible(false);
        $menu->addItem('Home', '/');
        $tail = $menu->addItem('Tail', '#');
        $tail->getSubMenu()->addItem('Hidden tail', '/hidden-tail')->setVisible(false);

        $renderer = new StringTemplateRenderer();
        $kept = $renderer->render($menu, ['roles' => true]);
        $hidden = $renderer->render($menu, [
            'roles' => true,
            'hideEmptyBranches' => true,
            'firstClass' => 'first',
            'lastClass' => 'last',
        ]);

        $this->assertStringContainsString('Empty', $kept);
        $this->assertStringContainsString('Tail', $kept);
        $this->assertStringNotContainsString('<ul class="submenu" role="menu"></ul>', $kept);
        $this->assertStringContainsString('href="#" role="menuitem">Empty</a>', $kept);
        $this->assertStringNotContainsString('Empty', $hidden);
        $this->assertStringNotContainsString('Tail', $hidden);
        $this->assertStringContainsString('<li class="first last" role="none">', $hidden);
    }

    public function testRootClassAppliesToTheRootListOnly(): void
    {
        $menu = Menu::create();
        $parent = $menu->addItem('Parent', '#');
        $parent->getSubMenu()->addItem('Child', '/child');

        $result = (new StringTemplateRenderer())->render($menu, ['rootClass' => 'navbar-nav']);

        $this->assertStringContainsString('<ul class="navbar-nav">', $result);
        // Nested lists keep their nestedMenuClass and are not given the root class.
        $this->assertStringContainsString('<ul class="submenu">', $result);
    }

    public function testRenderItemHonorsPerCallTemplateOverrides(): void
    {
        $item = Menu::create()->newItem('Home', '/');

        $result = (new StringTemplateRenderer())->renderItem($item, [
            'templates' => [
                'link' => '<a data-test="custom"{{attributes}}>{{title}}</a>',
            ],
        ]);

        $this->assertSame('<li><a data-test="custom" href="/">Home</a></li>', $result);
    }

    public function testRawItemKeepsStateOnListItem(): void
    {
        $menu = new Menu();
        $raw = $menu->addItem('Raw')->setRaw('<span>raw</span>')->setActive(true);
        $raw->getSubMenu()->addItem('Child', '/child');

        $html = (new StringTemplateRenderer(['activeOn' => 'link']))->render($menu);

        $this->assertMatchesRegularExpression('/<li[^>]*class="[^"]*\\bactive\\b[^"]*"[^>]*aria-expanded="true"|<li[^>]*aria-expanded="true"[^>]*class="[^"]*\\bactive\\b/', $html);
        $this->assertStringContainsString('<span>raw</span>', $html);
    }

    public function testTrustedIconAndBadgeOptionsSurviveArrayRoundTrip(): void
    {
        $menu = new Menu();
        $menu->addItem('Home', '/', [
            'icon' => '<i class="bi bi-house"></i>',
            'escapeIcon' => false,
            'badge' => '<b>3</b>',
            'escapeBadge' => false,
        ]);
        $menu->addItem('Plain', '/plain', ['icon' => '<i>x</i>', 'badge' => '<b>1</b>']);

        $html = (new StringTemplateRenderer())->render(Menu::fromArray($menu->toArray()));

        $this->assertStringContainsString('<i class="bi bi-house"></i>', $html);
        $this->assertStringContainsString('<b>3</b>', $html);
        $this->assertStringContainsString('&lt;i&gt;x&lt;/i&gt;', $html);
        $this->assertStringContainsString('&lt;b&gt;1&lt;/b&gt;', $html);
    }
}
