<?php

declare(strict_types=1);

namespace Menu\Renderer;

use Menu\MenuInterface;
use function htmlspecialchars;
use function sprintf;
use function trim;
use const ENT_QUOTES;

/**
 * Renders a complete Bootstrap 5 navbar: the `<nav>` wrapper, optional brand, the responsive
 * toggler, and the collapsible `navbar-nav` list (with dropdowns inherited from
 * {@see \Menu\Renderer\Bootstrap5Renderer}).
 *
 * For just the `<ul class="navbar-nav">` (to place inside your own navbar chrome), use
 * `Bootstrap5Renderer` directly.
 */
class NavbarRenderer extends Bootstrap5Renderer
{
    /**
     * Counter used to generate unique collapse ids when none is provided.
     */
    protected static int $collapseInstances = 0;

    /**
     * @phpstan-param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        // User config wins; everything else falls back to these navbar defaults. Bootstrap 5
        // link/dropdown defaults are inherited from Bootstrap5Renderer's config.
        parent::__construct($config + [
            'rootClass' => 'navbar-nav',
            'expand' => 'lg',
            'theme' => 'bg-body-tertiary',
            'navbarClass' => null,
            'containerClass' => 'container-fluid',
            'brand' => null,
            'brandUrl' => '/',
            'togglerLabel' => 'Toggle navigation',
        ]);
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface $menu, array $options = []): string
    {
        // The <nav> is the navigation landmark, so the aria-label belongs there, not on the
        // inner <ul>. Capture it, then suppress it for the inner render.
        $ariaLabel = $this->getStringOption($options, 'ariaLabel');
        $options['ariaLabel'] = null;

        $nav = parent::render($menu, $options);

        $collapseId = $this->getStringOption($options, 'collapseId');
        if ($collapseId === '') {
            $collapseId = 'navbar-collapse-' . (++static::$collapseInstances);
        }

        $navAttributes = sprintf('class="%s"', $this->attribute($this->navbarClass($options)));
        if ($ariaLabel !== '') {
            $navAttributes .= sprintf(' aria-label="%s"', $this->attribute($ariaLabel));
        }

        return sprintf(
            '<nav %s><div class="%s">%s%s<div class="collapse navbar-collapse" id="%s">%s</div></div></nav>',
            $navAttributes,
            $this->attribute($this->getStringOption($options, 'containerClass') ?: 'container-fluid'),
            $this->brand($options),
            $this->toggler($collapseId, $options),
            $this->attribute($collapseId),
            $nav,
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function navbarClass(array $options): string
    {
        $class = $this->getStringOption($options, 'navbarClass');
        if ($class !== '') {
            return $class;
        }

        $expand = $this->getStringOption($options, 'expand');
        $theme = $this->getStringOption($options, 'theme');

        return trim('navbar' . ($expand !== '' ? ' navbar-expand-' . $expand : '') . ($theme !== '' ? ' ' . $theme : ''));
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function brand(array $options): string
    {
        $brand = $this->getStringOption($options, 'brand');
        if ($brand === '') {
            return '';
        }

        return sprintf(
            '<a class="navbar-brand" href="%s">%s</a>',
            $this->attribute($this->getStringOption($options, 'brandUrl') ?: '/'),
            htmlspecialchars($brand, ENT_QUOTES, 'UTF-8'),
        );
    }

    /**
     * @phpstan-param array<string, mixed> $options
     */
    protected function toggler(string $collapseId, array $options): string
    {
        $label = $this->getStringOption($options, 'togglerLabel') ?: 'Toggle navigation';

        return sprintf(
            '<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#%s"'
            . ' aria-controls="%s" aria-expanded="false" aria-label="%s">'
            . '<span class="navbar-toggler-icon"></span></button>',
            $this->attribute($collapseId),
            $this->attribute($collapseId),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
        );
    }

    protected function attribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
