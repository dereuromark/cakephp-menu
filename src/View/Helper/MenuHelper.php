<?php
declare(strict_types = 1);

namespace Menu\View\Helper;

use Cake\View\Helper;
use Menu\MenuInterface;
use Menu\Renderer\StringTemplateRenderer;

/**
 * Menu Helper
 *
 * A simple CakePHPish wrapper to render menu objects
 */
class MenuHelper extends Helper {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'renderer' => StringTemplateRenderer::class,
	];

	/**
	 * Renders a menu
	 *
	 * @param \Menu\MenuInterface $menu
	 * @param array $options Options
	 *
	 * @return string
	 */
	public function render(MenuInterface $menu, array $options = []) {
		$renderer = $this->getRenderer();

		return $renderer->render($menu, $options);
	}

	/**
	 * @return \Menu\Renderer\RendererInterface
	 */
	protected function getRenderer() {
		$className = $this->getConfig('renderer');

		return new $className($this->getConfig());
	}

}
