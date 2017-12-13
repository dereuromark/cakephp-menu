<?php
declare(strict_types = 1);

namespace Cake\Menu\Renderer;

use Cake\Core\InstanceConfigTrait;
use Cake\Menu\Item\ItemInterface;
use Cake\Menu\Item\SelfRendererInterface;
use Cake\Menu\MenuInterface;
use Cake\View\StringTemplateTrait;

class StringTemplateRenderer {

	use InstanceConfigTrait;
	use StringTemplateTrait;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'templates' => [
			'menuWrapper' => '<ul>{{items}}</ul>',
			'item' => '<li{{attributes}}>{{before}}{{item}}{{after}}</li>',
			'link' => '<a{{attributes}}>{{title}}</a>'
		]
	];

	/**
	 * @var array
	 */
	protected $_templates = [
	];

	/**
	 * @param \Cake\Menu\MenuInterface $menu
	 * @param array $options
	 *
	 * @return string
	 */
	public function render(MenuInterface $menu, array $options = []) {
	    //TODO
	    return '';
	}

	/**
	 * @param \Cake\Menu\Item\ItemInterface $item
	 *
	 * @return string
	 */
	public function renderItem(ItemInterface $item) {
		if ($item instanceof SelfRendererInterface) {
			return $item->render();
		}

		$attributes = $item->getLink()->getAttributes();
		$attributes['href'] = $item->getLink()->getUrl();

		$attr = [];
		//TODO: escape
		foreach ($attributes as $name => $value) {
			$attr[] = $name . '="' . $value . '"';
		}

		return $this->templater()->format('link', [
			'attributes' => implode(' ', $attr),
			'title' => $item->getTitle(),
		]);
	}

}
