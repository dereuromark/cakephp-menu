<?php

declare(strict_types=1);

namespace Menu\Item;

use Menu\Link\LinkInterface;
use Menu\MenuInterface;

interface ItemInterface
{
    public function setId(string $id): static;

    public function getId(): string;

    public function setKey(string $key): static;

    public function getKey(): string;

    public function hasExplicitKey(): bool;

    public function setLabel(string $label, bool $escape = true): static;

    public function getLabel(): ?string;

    public function shouldEscapeLabel(): bool;

    /**
     * @phpstan-param \Menu\Link\LinkInterface|array<string|int, mixed>|string|null $link
     */
    public function setLink(LinkInterface|string|array|null $link): static;

    public function getLink(): ?LinkInterface;

    public function setRaw(string $content): static;

    public function getRaw(): ?string;

    public function isRaw(): bool;

    public function setDivider(bool $divider = true): static;

    public function isDivider(): bool;

    public function setHeader(bool $header = true): static;

    public function isHeader(): bool;

    public function setVisibility(bool $isVisible): static;

    public function isVisible(): bool;

    public function setActive(bool $isActive): static;

    public function isActive(): bool;

    public function add(ItemInterface $item): static;

    public function setSubMenu(MenuInterface $menu): static;

    public function getSubMenu(): MenuInterface;

    public function hasSubMenu(): bool;

    public function setParent(?ItemInterface $item): static;

    public function getParent(): ?ItemInterface;

    public function hasParent(): bool;

    public function getParentId(): ?string;

    public function setOwnerMenu(?MenuInterface $menu): static;

    public function getOwnerMenu(): ?MenuInterface;

    public function hasOwnerMenu(): bool;

    public function detach(): static;

    public function setBefore(string $before): static;

    public function getBefore(): string;

    public function setAfter(string $after): static;

    public function getAfter(): string;

    public function setIcon(?string $icon): static;

    public function getIcon(): ?string;

    public function setBadge(string|int|null $badge, ?string $type = null): static;

    public function getBadge(): ?string;

    public function getBadgeType(): ?string;

    public function setAttribute(string $name, mixed $value): static;

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes, bool $merge = false): static;

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getAttributes(): array;

    public function setData(string $name, mixed $value): static;

    public function getData(?string $name = null): mixed;

    /**
     * @phpstan-param list<array<string|int, mixed>|string> $routes
     */
    public function setMatchRoutes(array $routes): static;

    /**
     * @phpstan-param array<string|int, mixed>|string $route
     */
    public function addMatchRoute(string|array $route): static;

    /**
     * @phpstan-return list<array<string|int, mixed>|string>
     */
    public function getMatchRoutes(): array;

    public function setIgnoreQueryString(?bool $ignoreQueryString): static;

    public function getIgnoreQueryString(): ?bool;

    public function setFuzzyMatch(bool $fuzzyMatch = true): static;

    public function isFuzzyMatch(): bool;

    public function setExpanded(bool $expanded = true): static;

    public function isExpanded(): bool;

    public function setDisplayChildren(bool $displayChildren = true): static;

    public function displaysChildren(): bool;

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setLabelAttributes(array $attributes, bool $merge = false): static;

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getLabelAttributes(): array;

    public function freeze(): static;

    public function isFrozen(): bool;

    /**
     * @phpstan-return array<string, mixed>
     */
    public function toArray(): array;
}
