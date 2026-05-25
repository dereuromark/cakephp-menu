<?php

declare(strict_types=1);

namespace Menu\Item;

use Cake\Utility\Text;
use Menu\Link\Link;
use Menu\Link\LinkInterface;
use Menu\Menu;
use Menu\MenuInterface;

class Item implements ItemInterface
{
    protected string $id;

    protected string $key = '';

    protected ?string $label = null;

    protected bool $escapeLabel = true;

    protected ?LinkInterface $link = null;

    protected ?string $raw = null;

    protected bool $divider = false;

    protected bool $visible = true;

    protected bool $active = false;

    protected string $before = '';

    protected string $after = '';

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

    protected bool $fuzzyMatch = false;

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

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setKey(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getKey(): string
    {
        if ($this->key === '' && $this->label !== null) {
            $this->key = strtolower((string)Text::slug($this->label));
        }

        return $this->key;
    }

    public function setLabel(string $label, bool $escape = true): static
    {
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
        $this->divider = $divider;

        return $this;
    }

    public function isDivider(): bool
    {
        return $this->divider;
    }

    public function setVisibility(bool $isVisible): static
    {
        $this->visible = $isVisible;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setActive(bool $isActive): static
    {
        $this->active = $isActive;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function add(ItemInterface $item): static
    {
        $item->setParent($this);
        $this->getSubMenu()->add($item);

        return $this;
    }

    public function setSubMenu(MenuInterface $menu): static
    {
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
        $this->before = $before;

        return $this;
    }

    public function getBefore(): string
    {
        return $this->before;
    }

    public function setAfter(string $after): static
    {
        $this->after = $after;

        return $this;
    }

    public function getAfter(): string
    {
        return $this->after;
    }

    public function setAttribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes, bool $merge = false): static
    {
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
        $this->ignoreQueryString = $ignoreQueryString;

        return $this;
    }

    public function getIgnoreQueryString(): ?bool
    {
        return $this->ignoreQueryString;
    }

    public function setFuzzyMatch(bool $fuzzyMatch = true): static
    {
        $this->fuzzyMatch = $fuzzyMatch;

        return $this;
    }

    public function isFuzzyMatch(): bool
    {
        return $this->fuzzyMatch;
    }
}
