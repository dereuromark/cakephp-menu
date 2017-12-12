<?php
declare(strict_types=1);

namespace Cake\Menu\Renderer;

use Cake\Core\InstanceConfigTrait;
use Cake\Menu\ItemInterface;
use Cake\View\StringTemplateTrait;

class StringTemplateRenderer {

	use StringTemplateTrait;
	use InstanceConfigTrait;

	protected $_defaultConfig = [
		'templates' => [
			'menuWrapper' => '<ul>{{items}}</ul>',
			'item' => '<li {{attributes}}>{{before}}{{item}}{{after}}</li>',
			'link' => '<a {{attributes}}>{{title}}</a>'
		]
	];

	protected $_templates = [

	];

	public function render() {
	}

    /**
     * @param \Cake\Menu\ItemInterface $item
     * @return string
     */
	public function renderItem(ItemInterface $item) {
		if ($item instanceof ItemSelfRenderer) {
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
			'title' => $item->getTitle()
		]);
	}
}
