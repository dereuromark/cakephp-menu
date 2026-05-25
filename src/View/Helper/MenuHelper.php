<?php

declare(strict_types=1);

namespace Menu\View\Helper;

use Cake\View\Helper;
use Menu\MenuInterface;
use Menu\Renderer\RendererInterface;
use Menu\Renderer\StringTemplateRenderer;

/**
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class MenuHelper extends Helper
{
    protected array $_defaultConfig = [
        'renderer' => StringTemplateRenderer::class,
    ];

    /**
     * @phpstan-param array<string, mixed> $options
     */
    public function render(MenuInterface $menu, array $options = []): string
    {
        return $this->getRenderer()->render($menu, $options);
    }

    protected function getRenderer(): RendererInterface
    {
        /** @var class-string<\Menu\Renderer\RendererInterface> $className */
        $className = $this->getConfig('renderer');

        return new $className($this->getConfig());
    }
}
