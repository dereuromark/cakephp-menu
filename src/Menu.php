<?php
declare(strict_types = 1);

namespace Cake\Menu;

use Cake\Menu\Item\Item;
use Cake\Menu\Item\ItemInterface;

class Menu implements MenuInterface {

	/**
	 * @var array
	 */
	protected $_itemCollection;

	/**
	 * @var array
	 */
	protected $_attributes = [];

	/**
	 * @var string
	 */
	protected $_itemClass = Item::class;

	/**
	 * @param \Cake\Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function add(ItemInterface $item) {
		//TODO: object?
		$this->_itemCollection[] = $item;

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function getData($name) {
		if (!isset($this->_attributes[$name])) {
			return null;
		}

		return $this->_attributes[$name];
	}

	/**
	 * @param string $title
	 * @param \Cake\Menu\Link\LinkInterface|null $link
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function addRaw($title, $link = null, array $attributes = []) {
		/** @var \Cake\Menu\Item\ItemInterface $item */
		$item = new $this->_itemClass();
		$item
			->setTitle($title);
		if ($link) {
			$item->setLink($link);
		}
		if ($attributes) {
		    $item->setAttributes($attributes);
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function setData($name, $value) {
		$this->_attributes[$name] = $value;

		return $this;
	}

	/**
	 * @param string $name
	 * @param string $direction
	 *
	 * @return void
	 */
	public function sortBy($name, $direction = self::SORT_DESC) {
		if (is_callable($name)) {
			$name($this, $direction);
		}

		$this->_sort($name, $direction);
	}

	/**
	 * @param string $name
	 * @param string $direction
	 * @return void
	 */
	protected function _sort($name, $direction) {
	}

	/**
	 * @param string $itemId
	 *
	 * @return $this
	 */
	public function remove($itemId) {
		// TODO: Implement remove() method.

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->_attributes;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function setAttribute($name, $value) {
		// TODO: Implement setAttribute() method.

		return $this;
	}

	/**
	 * @param callable|string $by
	 * @param array $options
	 *
	 * @return void
	 */
	public function filter($by, array $options) {
		// TODO: Implement filter() method.
	}

}
