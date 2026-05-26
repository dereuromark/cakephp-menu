---
description: Every configuration option for the bundled cakephp-menu renderers — String Template, Bootstrap 5, Sidebar, Navbar, Breadcrumb, and JSON — with defaults.
---

# Renderer Options

Each renderer is configured three ways — pick whichever fits:

```php
// 1. Constructor
echo (new Bootstrap5Renderer(['activeClass' => 'is-active']))->render($menu);

// 2. Fluent, after construction
$renderer = new Bootstrap5Renderer();
$renderer->setConfig('activeClass', 'is-active');

// 3. Through the helper — render() options are passed to the renderer
echo $this->Menu->render('main', [
    'renderer' => Bootstrap5Renderer::class,
    'activeClass' => 'is-active',
]);
```

All renderers except `JsonRenderer` extend `StringTemplateRenderer`, so its options apply unless a
subclass overrides them.

## StringTemplateRenderer

The dependency-free default.

| Option | Default | Description |
|--------|---------|-------------|
| `activeClass` | `'active'` | Class on active items. |
| `ancestorClass` | `'active-ancestor'` | Class on ancestors of the active item. |
| `dividerClass` | `'divider'` | Class on divider `<li>`s. |
| `headerClass` | `'menu-header'` | Class on header `<li>`s. |
| `branchClass` | `'has-children'` | Class on items that have a submenu. |
| `leafClass` | `null` | Optional class on items without a submenu. |
| `submenuClass` | `null` | Extra class on branch `<li>`s (in addition to `branchClass`). |
| `hideEmptyBranches` | `false` | Hide branches whose children are all hidden. |
| `nestedMenuClass` | `'submenu'` | Class on nested (level 2+) `<ul>`s. |
| `rootClass` | `null` | Optional class on the root (level 1) `<ul>` only. |
| `menuLevelClass` | `null` | Prefix for a per-level class (level number appended). |
| `firstClass` | `null` | Optional class on the first item of each level. |
| `lastClass` | `null` | Optional class on the last item of each level. |
| `depth` | `null` | Maximum nesting depth to render (`null` = unlimited). |
| `currentAsLink` | `true` | Render the active item as a link (`false` = `<span>` label). |
| `ariaLabel` | `null` | Optional `aria-label` on the root menu. |
| `addAriaCurrent` | `true` | Add `aria-current="page"` to active items. |
| `addAriaExpanded` | `true` | Add `aria-expanded` to branch toggles. |
| `roles` | `false` | Opt-in WAI-ARIA menu roles: `menubar`/`menu` on lists, `none` on items, `menuitem` (+ `aria-haspopup` on branches) on links, `separator`/`presentation` on dividers/headers. |

### Template keys

Override any of these via the `templates` option:

| Key | Default |
|-----|---------|
| `menuWrapper` | `<ul{{attributes}}>{{items}}</ul>` |
| `item` | `<li{{attributes}}>{{content}}</li>` |
| `link` | `<a{{attributes}}>{{title}}</a>` |
| `label` | `<span{{attributes}}>{{title}}</span>` |
| `divider` | `<li{{attributes}}></li>` |
| `header` | `<li{{attributes}}>{{title}}</li>` |

### Icon & badge templates

Per-render overrides for the icon/badge markup (see [Building Menus](/guide/building)):

| Option | Default |
|--------|---------|
| `iconTemplate` | `<i class="{{icon}}" aria-hidden="true"></i> ` |
| `badgeTemplate` | ` <span class="{{class}}">{{text}}</span>` |

## Bootstrap5Renderer

Extends `StringTemplateRenderer`. Overrides and additions:

| Option | Default | Description |
|--------|---------|-------------|
| `ancestorClass` | `'active'` | Ancestors use `active` (not `active-ancestor`). |
| `branchClass` | `'dropdown'` | Branch items become Bootstrap dropdowns. |
| `nestedMenuClass` | `'dropdown-menu'` | Nested `<ul>`s are dropdown menus. |
| `addAriaExpanded` | `false` | Bootstrap manages `aria-expanded` itself. |
| `linkClass` | `'nav-link'` | Class on root-level links. |
| `childLinkClass` | `'dropdown-item'` | Class on dropdown links. |
| `toggleClass` | `'dropdown-toggle'` | Class on dropdown toggles. |
| `toggleAttribute` | `'data-bs-toggle'` | Toggle data attribute. |
| `toggleValue` | `'dropdown'` | Toggle attribute value. |

The `divider` template becomes `<li{{attributes}}><hr class="dropdown-divider"></li>`.

## Bootstrap5SidebarRenderer

Extends `StringTemplateRenderer`. A vertical, collapsible sidebar.

| Option | Default | Description |
|--------|---------|-------------|
| `idPrefix` | `'menu-collapse-'` | Prefix for collapse element ids (keep unique per sidebar on a page). |
| `navClass` | `'nav flex-column'` | Class on the root nav. |
| `nestedNavClass` | `'nav flex-column ms-3'` | Class on nested navs. |
| `itemClass` | `'nav-item'` | Class on list items. |
| `linkClass` | `'nav-link'` | Class on leaf links. |
| `toggleClass` | `'nav-link d-flex justify-content-between align-items-center'` | Class on collapse-toggle links. |
| `toggleButtonClass` | `'btn btn-link nav-link border-0 p-0 ms-2'` | Class on the toggle button when a branch also has a real URL. |
| `collapseClass` | `'collapse'` | Bootstrap collapse class. |
| `expandedClass` | `'show'` | Class added when a branch is expanded. |
| `toggleAttribute` | `'data-bs-toggle'` | Toggle data attribute. |
| `toggleValue` | `'collapse'` | Toggle attribute value. |
| `targetAttribute` | `'data-bs-target'` | Collapse target data attribute. |
| `caret` | `true` | Render an open/closed caret indicator. |
| `caretOpen` | `'▾'` | Markup for the expanded caret (trusted). |
| `caretClosed` | `'▸'` | Markup for the collapsed caret (trusted). |
| `activeClass` / `currentAsLink` / `addAriaCurrent` / `hideEmptyBranches` | (as parent) | Inherited from `StringTemplateRenderer`. |

## NavbarRenderer

Extends `Bootstrap5Renderer`. Wraps the menu in a full `<nav class="navbar">`.

| Option | Default | Description |
|--------|---------|-------------|
| `rootClass` | `'navbar-nav'` | Class on the root `<ul>`. |
| `expand` | `'lg'` | Bootstrap expand breakpoint (`sm`/`md`/`lg`/`xl`/`xxl`). |
| `theme` | `'bg-body-tertiary'` | Background/theme class on `<nav>`. |
| `navbarClass` | `null` | Custom `<nav>` class (replaces the auto-generated one). |
| `containerClass` | `'container-fluid'` | Class on the inner container `<div>`. |
| `brand` | `null` | Brand text/markup; renders a brand link when set. |
| `brandUrl` | `'/'` | Brand link href. |
| `togglerLabel` | `'Toggle navigation'` | `aria-label`/text on the responsive toggler. |
| `collapseId` | auto (`navbar-collapse-N`) | Unique id for the collapse section. Set explicitly when multiple navbars share a page. |
| `ariaLabel` | (inherited) | `aria-label` for the `<nav>`. |

## BreadcrumbRenderer

Extends `StringTemplateRenderer`. Renders the active path as a Bootstrap breadcrumb.

| Option | Default | Description |
|--------|---------|-------------|
| `activeClass` | `'active'` | Class on the current (last) crumb. |
| `itemClass` | `'breadcrumb-item'` | Class on every crumb. |
| `menuClass` | `'breadcrumb'` | Class on the `<ol>` wrapper. |
| `ariaLabel` | `'breadcrumb'` | `aria-label` on the `<nav>`. |
| `path` | (auto) | Explicit array of `ItemInterface` to render; defaults to the active item's ancestors. |

The wrapper template is `<nav aria-label="{{ariaLabel}}"><ol{{attributes}}>{{items}}</ol></nav>`.

## JsonRenderer

Implements `RendererInterface` directly (no `StringTemplateRenderer` options).

| Option | Default | Description |
|--------|---------|-------------|
| `pretty` | `false` | When truthy, encodes with `JSON_PRETTY_PRINT`. |

The JSON mirrors `Menu::toArray()`; see the [Renderer Gallery](/guide/gallery#json) for the shape.
