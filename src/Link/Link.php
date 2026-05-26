<?php

declare(strict_types=1);

namespace Menu\Link;

use Cake\Routing\Router;
use LogicException;

class Link implements LinkInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * @var array<string|int, mixed>|string|null
     */
    protected string|array|null $url = null;

    protected bool $external = false;

    /**
     * Clone hook for symmetry with Item and Menu.
     */
    public function __clone(): void
    {
    }

    /**
     * @phpstan-param array<string|int, mixed>|string|null $url
     * @phpstan-param array<string, mixed> $attributes
     */
    public static function create(
        string|array|null $url = null,
        array $attributes = [],
        bool $external = false,
    ): static {
        $link = new static();
        $link->setUrl($url, $external);
        if ($attributes) {
            $link->setAttributes($attributes);
        }

        return $link;
    }

    /**
     * @phpstan-param array<string|int, mixed>|string|null $url
     *
     * @throws \LogicException
     */
    public function setUrl(string|array|null $url, bool $external = false): static
    {
        if ($external && is_array($url)) {
            throw new LogicException('External links must use a string URL.');
        }

        $this->url = $url;
        $this->external = $external;

        return $this;
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

    /**
     * @phpstan-return array<string|int, mixed>|string|null
     */
    public function getRawUrl(): string|array|null
    {
        return $this->url;
    }

    public function getUrl(): ?string
    {
        if ($this->url === null) {
            return null;
        }

        if ($this->external) {
            if (!is_string($this->url)) {
                throw new LogicException('External links must use a string URL.');
            }

            return $this->url;
        }

        return Router::url($this->url);
    }

    public function isExternal(): bool
    {
        return $this->external;
    }
}
