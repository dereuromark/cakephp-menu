<?php

declare(strict_types=1);

namespace Menu\Link;

interface LinkInterface
{
    /**
     * @phpstan-param array<string|int, mixed>|string|null $url
     * @phpstan-param array<string, mixed> $attributes
     */
    public static function create(
        array|string|null $url = null,
        array $attributes = [],
        bool $external = false,
    ): static;

    /**
     * @phpstan-param array<string|int, mixed>|string|null $url
     */
    public function setUrl(array|string|null $url, bool $external = false): static;

    public function setAttribute(string $name, mixed $value): static;

    /**
     * @phpstan-param array<string, mixed> $attributes
     */
    public function setAttributes(array $attributes, bool $merge = false): static;

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * @phpstan-return array<string|int, mixed>|string|null
     */
    public function getRawUrl(): array|string|null;

    public function getUrl(): ?string;

    public function isExternal(): bool;
}
