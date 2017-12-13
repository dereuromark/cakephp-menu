<?php
declare(strict_types = 1);

namespace Cake\Menu\View\Helper;

use Cake\Menu\Menu;
use Cake\Menu\Renderer\StringTemplateRenderer;
use Cake\View\Helper;

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
	 * @param \Cake\Menu\Menu $menu
	 * @param array $options Options
	 *
	 * @return string
	 */
	public function render(Menu $menu, array $options = []) {
		$renderer = $this->getRenderer();

		return $renderer->render($menu, $options);
	}

	/**
	 * @return \Cake\Menu\Renderer\RendererInterface
	 */
	protected function getRenderer() {
		$className = $this->getConfig('renderer');

		return new $className($this->getConfig());
	}

}
