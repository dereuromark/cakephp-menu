<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Menu\Item\ItemInterface;
use Menu\Item\SelfRendererInterface;
use Menu\MenuInterface;
use function array_filter;
use function implode;
use function preg_replace;
use function sprintf;
use function trim;

/**
 * Renders a menu as a collapsible Bootstrap 5 sidebar: a vertical `nav` whose branches are
 * Bootstrap `collapse` regions toggled by their parent.
 *
 * The branch containing the active item is expanded (`collapse show`, `aria-expanded="true"`),
 * every other branch starts collapsed, and the active leaf gets the active class plus
 * `aria-current="page"`. Each branch is wired to its `collapse` element through a unique id
 * (derived from the item id, prefixed with `idPrefix`); render two sidebars on one page with
 * different `idPrefix` values to avoid id collisions.
 *
 * A branch that also has a real URL stays navigable: it renders the link plus a separate
 * collapse toggle button. A branch with only a placeholder link (`#`/none) renders a single
 * toggle. Item and submenu attributes from the menu definition are preserved.
 *
 * ```php
 * echo $this->Menu->render('sidebar', ['renderer' => \Menu\Renderer\Bootstrap5SidebarRenderer::class]);
 * ```
 */
class Bootstrap5SidebarRenderer extends StringTemplateRenderer
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'activeClass' => 'active',
        'hideEmptyBranches' => false,
        'currentAsLink' => true,
        'addAriaCurrent' => true,
        // Wires each branch toggle to its collapse element; make it unique per sidebar on a page.
        'idPrefix' => 'menu-collapse-',
        'navClass' => 'nav flex-column',
        'nestedNavClass' => 'nav flex-column ms-3',
        'itemClass' => 'nav-item',
        'linkClass' => 'nav-link',
        'toggleClass' => 'nav-link d-flex justify-content-between align-items-center',
        // Toggle button used when a branch also has a real URL (link + separate toggle).
        'toggleButtonClass' => 'btn btn-link nav-link border-0 p-0 ms-2',
        // Framework-specific bits, defaulting to Bootstrap 5; override for BS4/other.
        'collapseClass' => 'collapse',
        'expandedClass' => 'show',
        'toggleAttribute' => 'data-bs-toggle',
        'toggleValue' => 'collapse',
        'targetAttribute' => 'data-bs-target',
        // Append a small open/closed indicator to branch toggles; set false to omit.
        // caretOpen/caretClosed are rendered as trusted markup (use an icon tag if you like).
        'caret' => true,
        'caretOpen' => '▾',
        'caretClosed' => '▸',
    ];

    /**
     * Fallback counter for branches whose item id does not yield a usable slug.
     *
     * @var int
     */
    protected int $autoId = 0;

    /**
     * Collapse ids already used in the current render, to guarantee uniqueness.
     *
     * @var array<string, true>
     */
    protected array $usedIds = [];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderMenu(MenuInterface $menu, array $options, int $level): string
    {
        if ($level === 1) {
            $this->autoId = 0;
            $this->usedIds = [];
        }

        $items = [];
        foreach ($menu->getItems() as $item) {
            if (!$item->isVisible()) {
                continue;
            }
            $html = $this->renderMenuItem($item, $options, 0, 0, $level);
            if ($html !== '') {
                $items[] = $html;
            }
        }

        $class = $level === 1
            ? $this->getStringOption($options, 'navClass')
            : $this->getStringOption($options, 'nestedNavClass');
        $attributes = $this->appendClass($menu->getAttributes(), $class);

        return sprintf('<ul%s>%s</ul>', $this->renderAttributes($attributes), implode('', $items));
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderMenuItem(
        ItemInterface $item,
        array $options,
        int $index,
        int $count,
        int $level,
    ): string {
        if (!$item->isVisible()) {
            return '';
        }
        if ($item instanceof SelfRendererInterface) {
            return $item->render();
        }
        if ($item->isDivider()) {
            return $this->li($item, $options, '<hr class="dropdown-divider">');
        }
        if ($item->isHeader()) {
            $attributes = $this->appendClass($item->getAttributes(), $this->getStringOption($options, 'headerClass') ?: 'nav-header');
            $title = $item->getBefore() . $this->escapeLabel($item, $options) . $item->getAfter();

            return sprintf('<li%s>%s</li>', $this->renderAttributes($attributes), $title);
        }

        $hasSubMenu = $item->hasSubMenu();
        if (
            $hasSubMenu
            && $this->getBooleanOption($options, 'hideEmptyBranches', false)
            && !$this->hasRenderableChild($item, $options)
        ) {
            return '';
        }
        if ($item->isRaw()) {
            return $this->li($item, $options, (string)$item->getRaw());
        }

        $label = $item->getBefore()
            . $this->decorateTitle($item, $this->escapeLabel($item, $options), $options)
            . $item->getAfter();

        $content = $hasSubMenu
            ? $this->renderBranch($item, $options, $label, $level)
            : $this->renderLeaf($item, $options, $label);

        return $this->li($item, $options, $content);
    }

    /**
     * Wraps content in an `<li>` that keeps the item's own attributes plus the item class.
     *
     * @phpstan-param array<string, mixed> $options
     */
    protected function li(ItemInterface $item, array $options, string $content): string
    {
        $attributes = $this->appendClass($item->getAttributes(), $this->getStringOption($options, 'itemClass'));

        return sprintf('<li%s>%s</li>', $this->renderAttributes($attributes), $content);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderLeaf(ItemInterface $item, array $options, string $label): string
    {
        $active = $item->isActive();
        $link = $item->getLink();
        $asLabel = $link === null || ($active && !$this->getBooleanOption($options, 'currentAsLink', true));

        // Keep the link's own attributes (title, data-*, custom classes) and merge our classes in.
        $attributes = $link?->getAttributes() ?? [];
        $attributes = $this->appendClass($attributes, $this->getStringOption($options, 'linkClass'));
        if ($active) {
            $attributes = $this->appendClass($attributes, $this->getStringOption($options, 'activeClass'));
        }
        if ($active && $this->getBooleanOption($options, 'addAriaCurrent', true)) {
            $attributes['aria-current'] = 'page';
        }

        if ($asLabel) {
            unset($attributes['href']);
            $attributes = $this->mergeLabelAttributes($attributes, $item);
            $attributes = $this->applyMenuItemRole($attributes, $item, $options);

            return sprintf('<span%s>%s</span>', $this->renderAttributes($attributes), $label);
        }

        $attributes['href'] = $link->getUrl() ?? '#';
        $attributes = $this->mergeLabelAttributes($attributes, $item);
        $attributes = $this->applyMenuItemRole($attributes, $item, $options);

        return sprintf('<a%s>%s</a>', $this->renderAttributes($attributes), $label);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderBranch(ItemInterface $item, array $options, string $label, int $level): string
    {
        $expanded = $item->isExpanded() || $item->isActive() || $this->hasActiveDescendant($item);
        $id = $this->collapseId($item, $options);
        $link = $item->getLink();
        $url = $link?->getUrl();
        $hasRealUrl = $url !== null && $url !== '#';

        $head = $hasRealUrl
            ? $this->renderNavigableBranch($item, $options, $label, $id, $url, $expanded)
            : $this->renderToggle($item, $options, $label, $id, $expanded);

        $collapse = sprintf(
            '<div%s>%s</div>',
            $this->renderAttributes([
                'class' => array_filter([
                    $this->getStringOption($options, 'collapseClass'),
                    $expanded ? $this->getStringOption($options, 'expandedClass') : null,
                ]),
                'id' => $id,
            ]),
            $this->renderMenu($item->getSubMenu(), $options, $level + 1),
        );

        return $head . $collapse;
    }

    /**
     * A branch that only groups children: the label itself is the collapse toggle.
     *
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderToggle(ItemInterface $item, array $options, string $label, string $id, bool $expanded): string
    {
        $classes = [$this->getStringOption($options, 'toggleClass')];
        if ($expanded) {
            $classes[] = $this->getStringOption($options, 'activeClass');
        }

        $attributes = ['class' => $classes];
        $toggleAttribute = $this->getStringOption($options, 'toggleAttribute');
        if ($toggleAttribute !== '') {
            $attributes[$toggleAttribute] = $this->getStringOption($options, 'toggleValue');
        }
        $attributes += [
            'role' => 'button',
            'href' => '#' . $id,
            'aria-controls' => $id,
            'aria-expanded' => $expanded ? 'true' : 'false',
        ];
        $attributes = $this->mergeLabelAttributes($attributes, $item);
        $attributes = $this->applyMenuItemRole($attributes, $item, $options);

        return sprintf(
            '<a%s>%s%s</a>',
            $this->renderAttributes($attributes),
            $label,
            $this->getBooleanOption($options, 'caret', true) ? $this->caret($options, $expanded) : '',
        );
    }

    /**
     * A branch that also navigates: a real link plus a separate collapse toggle button, so the
     * destination stays reachable and link attributes (target, rel, title, ...) are preserved.
     *
     * @phpstan-param array<string, mixed> $options
     */
    protected function renderNavigableBranch(
        ItemInterface $item,
        array $options,
        string $label,
        string $id,
        string $url,
        bool $expanded,
    ): string {
        /** @var \Menu\Link\LinkInterface $link */
        $link = $item->getLink();
        // Keep the link's own attributes and merge our classes in (do not overwrite).
        $linkAttributes = $this->appendClass($link->getAttributes(), $this->getStringOption($options, 'linkClass'));
        if ($item->isActive()) {
            $linkAttributes = $this->appendClass($linkAttributes, $this->getStringOption($options, 'activeClass'));
        }
        $linkAttributes['href'] = $url;
        if ($item->isActive() && $this->getBooleanOption($options, 'addAriaCurrent', true)) {
            $linkAttributes['aria-current'] = 'page';
        }
        // labelAttributes describe the rendered link/label; merge onto the navigable anchor only,
        // not the separate toggle button (which is renderer chrome, not the item's link).
        $linkAttributes = $this->mergeLabelAttributes($linkAttributes, $item);
        $linkAttributes = $this->applyMenuItemRole($linkAttributes, $item, $options);

        $anchor = sprintf('<a%s>%s</a>', $this->renderAttributes($linkAttributes), $label);

        $buttonAttributes = [
            'class' => array_filter([$this->getStringOption($options, 'toggleButtonClass'), $expanded ? $this->getStringOption($options, 'activeClass') : null]),
            'type' => 'button',
        ];
        $toggleAttribute = $this->getStringOption($options, 'toggleAttribute');
        if ($toggleAttribute !== '') {
            $buttonAttributes[$toggleAttribute] = $this->getStringOption($options, 'toggleValue');
        }
        $targetAttribute = $this->getStringOption($options, 'targetAttribute');
        if ($targetAttribute !== '') {
            $buttonAttributes[$targetAttribute] = '#' . $id;
        }
        $buttonAttributes += [
            'aria-controls' => $id,
            'aria-expanded' => $expanded ? 'true' : 'false',
            'aria-label' => trim('Toggle ' . (string)$item->getLabel()),
        ];

        $button = sprintf('<button%s>%s</button>', $this->renderAttributes($buttonAttributes), $this->caret($options, $expanded));

        return sprintf('<div class="d-flex justify-content-between align-items-center">%s%s</div>', $anchor, $button);
    }

    /**
     * The caret glyph is renderer config (not menu data) and is emitted as trusted markup,
     * so an icon element (e.g. a FontAwesome `<i>`) can be used.
     *
     * @phpstan-param array<string, mixed> $options
     */
    protected function caret(array $options, bool $expanded): string
    {
        $glyph = $expanded
            ? $this->getStringOption($options, 'caretOpen')
            : $this->getStringOption($options, 'caretClosed');

        return sprintf('<span class="menu-caret ms-2" aria-hidden="true">%s</span>', $glyph);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function collapseId(ItemInterface $item, array $options): string
    {
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', $item->getId());
        $slug = trim((string)$slug, '-');
        $base = $this->getStringOption($options, 'idPrefix') . ($slug !== '' ? $slug : (string)(++$this->autoId));

        // Distinct item ids can slugify to the same value; keep collapse ids unique per render.
        $id = $base;
        $suffix = 1;
        while (isset($this->usedIds[$id])) {
            $suffix++;
            $id = $base . '-' . $suffix;
        }
        $this->usedIds[$id] = true;

        return $id;
    }
}
