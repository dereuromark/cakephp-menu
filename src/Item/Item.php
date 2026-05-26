<?php

declare(strict_types=1);

namespace Menu\Item;

use Cake\Utility\Text;
use Closure;
use LogicException;
use Menu\Link\Link;
use Menu\Link\LinkInterface;
use Menu\Menu;
use Menu\MenuInterface;

class Item implements ItemInterface, StateResetInterface
{
    protected string $id;

    protected string $key = '';

    protected bool $explicitKey = false;

    protected ?string $label = null;

    protected bool $escapeLabel = true;

    protected ?LinkInterface $link = null;

    protected ?string $raw = null;

    protected bool $divider = false;

    protected bool $header = false;

    protected bool $visible = true;

    protected bool $defaultVisible = true;

    protected bool $active = false;

    protected bool $defaultActive = false;

    protected string $before = '';

    protected string $after = '';

    protected ?string $icon = null;

    protected ?string $badge = null;

    protected ?string $badgeType = null;

    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    protected ?ItemInterface $parent = null;

    protected ?MenuInterface $subMenu = null;

    /**
     * @var list<array<string|int, mixed>|string>
     */
    protected array $matchRoutes = [];

    protected ?bool $ignoreQueryString = null;

    protected ?bool $fuzzyMatch = null;

    protected bool $expanded = false;

    protected bool $defaultExpanded = false;

    protected bool $displayChildren = true;

    /**
     * @var array<string, mixed>
     */
    protected array $labelAttributes = [];

    protected bool $frozen = false;

    /**
     * @phpstan-param \Menu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     */
    public function __construct(
        ?string $label = null,
        LinkInterface|string|array|null $link = null,
    ) {
        $this->id = 'menu-item-' . Text::uuid();
        if ($label !== null) {
            $this->setLabel($label);
        }
        if ($link !== null) {
            $this->setLink($link);
        }
    }

    /**
     * Deep-clone linked value objects and nested menus. The clone is detached from its source
     * parent — the caller (or `Menu::__clone`/`setOwnerItemDuringClone`) reparents it as needed.
     */
    public function __clone(): void
    {
        // Detach from any source-tree parent; nested submenus reparent their children to this clone
        // below. Callers that re-attach the clone (add(), setOwnerItem) will set parent again.
        $this->parent = null;
        // A clone is a mutable working copy — the source's `frozen` flag tracks that source object,
        // not this independent one. Callers can re-freeze the clone after any edits.
        $this->frozen = false;
        if ($this->link !== null) {
            $this->link = clone $this->link;
        }
        if ($this->subMenu !== null) {
            $this->subMenu = clone $this->subMenu;
            if ($this->subMenu instanceof Menu) {
                $rebindOwner = Closure::bind(
                    static function (Menu $menu, ItemInterface $owner): void {
                        $menu->setOwnerItemDuringClone($owner);
                    },
                    null,
                    Menu::class,
                );
                $rebindOwner($this->subMenu, $this);
            } else {
                foreach ($this->subMenu->getItems() as $item) {
                    if ($item instanceof self) {
                        $item->parent = $this;

                        continue;
                    }
                    // Custom ItemInterface implementation — go through the public interface.
                    $item->setParent($this);
                }
            }
        }
    }

    public function setId(string $id): static
    {
        $this->assertMutable();
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setKey(string $key): static
    {
        $this->assertMutable();
        $this->key = $key;
        $this->explicitKey = true;

        return $this;
    }

    public function getKey(): string
    {
        if ($this->key !== '') {
            return $this->key;
        }
        if ($this->label !== null) {
            return strtolower((string)Text::slug($this->label));
        }

        return '';
    }

    public function hasExplicitKey(): bool
    {
        return $this->explicitKey;
    }

    public function setLabel(string $label, bool $escape = true): static
    {
        $this->assertMutable();
        $this->label = $label;
        $this->escapeLabel = $escape;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function shouldEscapeLabel(): bool
    {
        return $this->escapeLabel;
    }

    /**
     * @phpstan-param \Menu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     */
    public function setLink(LinkInterface|string|array|null $link): static
    {
        $this->assertMutable();
        if (!$link instanceof LinkInterface) {
            $link = Link::create($link);
        }

        $this->link = $link;

        return $this;
    }

    public function getLink(): ?LinkInterface
    {
        return $this->link;
    }

    public function setRaw(string $content): static
    {
        $this->assertMutable();
        $this->raw = $content;

        return $this;
    }

    public function getRaw(): ?string
    {
        return $this->raw;
    }

    public function isRaw(): bool
    {
        return $this->raw !== null;
    }

    public function setDivider(bool $divider = true): static
    {
        $this->assertMutable();
        $this->divider = $divider;

        return $this;
    }

    public function isDivider(): bool
    {
        return $this->divider;
    }

    public function setHeader(bool $header = true): static
    {
        $this->assertMutable();
        $this->header = $header;

        return $this;
    }

    public function isHeader(): bool
    {
        return $this->header;
    }

    public function setVisibility(bool $isVisible): static
    {
        $this->visible = $isVisible;
        $this->defaultVisible = $isVisible;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setActive(bool $isActive): static
    {
        $this->active = $isActive;
        $this->defaultActive = $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function add(ItemInterface $item): static
    {
        $this->assertMutable();
        $subMenu = $this->getSubMenu();
        if ($subMenu instanceof Menu) {
            $subMenu->add($item);

            return $this;
        }

        $item->setParent($this);
        $subMenu->add($item);

        return $this;
    }

    public function setSubMenu(MenuInterface $menu): static
    {
        $this->assertMutable();
        if ($menu instanceof Menu) {
            $menu->setOwnerItem($this);
        }
        $this->subMenu = $menu;

        return $this;
    }

    public function getSubMenu(): MenuInterface
    {
        if ($this->subMenu === null) {
            $this->subMenu = (new Menu())->setOwnerItem($this);
        }

        return $this->subMenu;
    }

    public function hasSubMenu(): bool
    {
        return $this->subMenu !== null && $this->subMenu->getItems() !== [];
    }

    public function setParent(ItemInterface $item): static
    {
        $this->assertMutable();
        $this->parent = $item;

        return $this;
    }

    public function getParent(): ?ItemInterface
    {
        return $this->parent;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getParentId(): ?string
    {
        return $this->parent?->getId();
    }

    public function setBefore(string $before): static
    {
        $this->assertMutable();
        $this->before = $before;

        return $this;
    }

    public function getBefore(): string
    {
        return $this->before;
    }

    public function setAfter(string $after): static
    {
        $this->assertMutable();
        $this->after = $after;

        return $this;
    }

    public function getAfter(): string
    {
        return $this->after;
    }

    public function setIcon(?string $icon): static
    {
        $this->assertMutable();
        $this->icon = $icon;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setBadge(string|int|null $badge, ?string $type = null): static
    {
        $this->assertMutable();
        $this->badge = $badge === null ? null : (string)$badge;
        $this->badgeType = $type;

        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getBadgeType(): ?string
    {
        return $this->badgeType;
    }

    public function setAttribute(string $name, mixed $value): static
    {
        $this->assertMutable();
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes, bool $merge = false): static
    {
        $this->assertMutable();
        $this->attributes = $merge ? $attributes + $this->attributes : $attributes;

        return $this;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setData(string $name, mixed $value): static
    {
        $this->assertMutable();
        $this->data[$name] = $value;

        return $this;
    }

    public function getData(?string $name = null): mixed
    {
        if ($name === null) {
            return $this->data;
        }

        return $this->data[$name] ?? null;
    }

    /**
     * @phpstan-param list<array<string|int, mixed>|string> $routes
     */
    public function setMatchRoutes(array $routes): static
    {
        $this->assertMutable();
        $this->matchRoutes = [];
        foreach ($routes as $route) {
            $this->addMatchRoute($route);
        }

        return $this;
    }

    /**
     * @phpstan-param array<string|int, mixed>|string $route
     */
    public function addMatchRoute(string|array $route): static
    {
        $this->assertMutable();
        $this->matchRoutes[] = $route;

        return $this;
    }

    /**
     * @phpstan-return list<array<string|int, mixed>|string>
     */
    public function getMatchRoutes(): array
    {
        return $this->matchRoutes;
    }

    public function setIgnoreQueryString(?bool $ignoreQueryString): static
    {
        $this->assertMutable();
        $this->ignoreQueryString = $ignoreQueryString;

        return $this;
    }

    public function getIgnoreQueryString(): ?bool
    {
        return $this->ignoreQueryString;
    }

    public function setFuzzyMatch(bool $fuzzyMatch = true): static
    {
        $this->assertMutable();
        $this->fuzzyMatch = $fuzzyMatch;

        return $this;
    }

    public function isFuzzyMatch(): bool
    {
        return $this->fuzzyMatch ?? false;
    }

    public function getFuzzyMatchSetting(): ?bool
    {
        return $this->fuzzyMatch;
    }

    public function setExpanded(bool $expanded = true): static
    {
        $this->expanded = $expanded;
        $this->defaultExpanded = $expanded;

        return $this;
    }

    public function isExpanded(): bool
    {
        return $this->expanded;
    }

    public function setDisplayChildren(bool $displayChildren = true): static
    {
        $this->assertMutable();
        $this->displayChildren = $displayChildren;

        return $this;
    }

    public function displaysChildren(): bool
    {
        return $this->displayChildren;
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setLabelAttributes(array $attributes, bool $merge = false): static
    {
        $this->assertMutable();
        $this->labelAttributes = $merge ? $attributes + $this->labelAttributes : $attributes;

        return $this;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getLabelAttributes(): array
    {
        return $this->labelAttributes;
    }

    public function freeze(): static
    {
        $this->frozen = true;
        if ($this->subMenu instanceof MenuInterface) {
            $this->subMenu->freeze();
        }

        return $this;
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key !== '' ? $this->key : null,
            'label' => $this->label,
            'escape' => $this->escapeLabel,
            'link' => $this->link?->getRawUrl(),
            'linkAttributes' => $this->link?->getAttributes() ?? [],
            'external' => $this->link?->isExternal() ?? false,
            'raw' => $this->raw,
            'divider' => $this->divider,
            'header' => $this->header,
            'visible' => $this->visible,
            'active' => $this->active,
            'expanded' => $this->expanded,
            'displayChildren' => $this->displayChildren,
            'before' => $this->before,
            'after' => $this->after,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'badgeType' => $this->badgeType,
            'attributes' => $this->attributes,
            'labelAttributes' => $this->labelAttributes,
            'data' => $this->data,
            'matchRoutes' => $this->matchRoutes,
            'ignoreQueryString' => $this->ignoreQueryString,
            'fuzzy' => $this->fuzzyMatch,
            'submenu' => $this->subMenu?->toArray(),
        ];
    }

    public function resetState(): static
    {
        $this->visible = $this->defaultVisible;
        $this->active = $this->defaultActive;
        $this->expanded = $this->defaultExpanded;

        return $this;
    }

    public function setRuntimeVisibility(bool $isVisible): static
    {
        $this->visible = $isVisible;

        return $this;
    }

    public function setRuntimeActive(bool $isActive): static
    {
        $this->active = $isActive;

        return $this;
    }

    public function setRuntimeExpanded(bool $expanded = true): static
    {
        $this->expanded = $expanded;

        return $this;
    }

    protected function assertMutable(): void
    {
        if ($this->frozen) {
            throw new LogicException('Cannot mutate a frozen menu item.');
        }
    }
}
