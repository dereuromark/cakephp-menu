<?php
declare(strict_types = 1);

namespace Menu;

use Menu\Item\Item;
use Menu\Item\ItemInterface;

class Menu implements MenuInterface {

	/**
	 * @var \Menu\Item\ItemInterface[]
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
	 * Convenience factory method
	 *
	 * @return static
	 */
	public static function create() {
		return new static();
	}

	/**
	 * Convenience method to get a new item
	 *
	 * @param string|null $label
	 *
	 * @return \Menu\Item\ItemInterface
	 */
	public function newItem($label = null) {
		/** @var \Menu\Item\ItemInterface $item */
		$item = new $this->_itemClass();
		if ($label !== null) {
			$item->setLabel($label);
		}

		return $item;
	}

	/**
	 * @param \Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function add(ItemInterface $item) {
		//TODO: object?
		$this->_itemCollection[] = $item;

		return $this;
	}

	/**
	 * @return \Menu\Item\ItemInterface[]
	 */
	public function getItems() {
		return $this->_itemCollection;
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
	 * @param \Menu\Link\LinkInterface|null $link
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function addRaw($title, $link = null, array $attributes = []) {
		/** @var \Menu\Item\ItemInterface $item */
		$item = new $this->_itemClass();
		$item->setLabel($title);
		if ($link !== null) {
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
	 * Sets multiple HTML attributes
	 *
	 * @param array $attributes Attributes
	 * @param bool $merge Merge them or not
	 *
	 * @return void
	 */
	public function setAttributes(array $attributes, $merge = false) {
		if ($merge) {
			$this->_attributes = $attributes + $this->_attributes;

			return;
		}

		$this->_attributes = $attributes;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function setAttribute($name, $value) {
		$this->_attributes[$name] = $value;

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
