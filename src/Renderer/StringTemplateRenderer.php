<?php
declare(strict_types = 1);

namespace Menu\Renderer;

use Cake\Core\InstanceConfigTrait;
use Cake\View\StringTemplateTrait;
use Menu\Item\ItemInterface;
use Menu\Item\SelfRendererInterface;
use Menu\MenuInterface;

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
			'link' => '<a{{attributes}}>{{title}}</a>',
		],
	];

	/**
	 * @var array
	 */
	protected $_templates = [
	];

	/**
	 * @param \Menu\MenuInterface $menu
	 * @param array $options
	 *
	 * @return string
	 */
	public function render(MenuInterface $menu, array $options = []) {
		$result = '';
	    foreach ($menu->getItems() as $item) {
	    	$result .= $this->renderItem($item);
		}

		return $result;
	}

	/**
	 * @param \Menu\Item\ItemInterface $item
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

		$attributes = implode(' ', $attr);
		if ($attributes) {
			$attributes = ' ' . $attributes;
		}

		return $this->templater()->format('link', [
			'attributes' => $attributes,
			'title' => $item->getLabel(),
		]);
	}

}
