<?php
declare(strict_types = 1);

namespace Menu\Item;

use Menu\Link\LinkInterface;
use Menu\MenuInterface;
use RuntimeException;

class Item implements ItemInterface {

	/**
	 * @var string
	 */
	protected $_id;

	/**
	 * @var bool
	 */
	protected $_isRaw = false;

	/**
	 * @var bool
	 */
	protected $_isDivider = true;

	/**
	 * @var bool
	 */
	protected $_isVisible = true;

	/**
	 * @var bool|callable
	 */
	protected $_isActive = false;

	/**
	 * @var array
	 */
	protected $_attributes = [];

	/**
	 * @var string|null
	 */
	protected $_label;

	/**
	 * @var string|null
	 */
	protected $_data;

	/**
	 * @var string|array|null
	 */
	protected $_raw;

	/**
	 * @var \Menu\Link\LinkInterface|null
	 */
	protected $_link;

	/**
	 * @var \Menu\Item\ItemInterface|null
	 */
	protected $_parent;

	/**
	 * @var int|string
	 */
	protected $_parentId;

	/**
	 * @var string
	 */
	protected $_key = '';

	/**
	 * @param string|null $title
	 * @param \Menu\Link\LinkInterface|null $link
	 */
	public function __construct($title = null, $link = null) {
		$this->_id = str_replace('.', '', uniqid('id-', true));
		$this->_key = strtolower(str_replace(' ', '-', (string)$title));

		if ($title !== null) {
			$this->setLabel($title);
		}
		if ($link instanceof LinkInterface) {
			$this->setLink($link);
		}
	}

	/**
	 * @param bool $isVisible
	 *
	 * @return $this
	 */
	public function setVisibility($isVisible) {
		$this->_isVisible = $isVisible;

		return $this;
	}

	/**
	 * @param bool|callable $isActive
	 *
	 * @return $this
	 */
	public function setActive($isActive) {
		$this->_isActive = $isActive;

		return $this;
	}

	/**
	 * @param \Menu\Link\LinkInterface|null $link
	 *
	 * @return $this
	 */
	public function setLink(?LinkInterface $link) {
		$this->_link = $link;

		return $this;
	}

	/**
	 * @param string $key
	 * @return \Menu\Item\Item
	 */
	public function setKey($key) {
		$this->_key = $key;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getKey() {
		return $this->_key;
	}

	/**
	 * @return \Menu\Link\LinkInterface|null
	 */
	public function getLink() {
		return $this->_link;
	}

	/**
	 * @param string $title
	 * @param bool $html
	 *
	 * @return $this
	 */
	public function setLabel($title, $html = false) {
		$this->_label = $html ? $title : (string)h($title);

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isRaw() {
		return $this->_isRaw;
	}

	/**
	 * @param string|array $data
	 *
	 * @return $this
	 */
	public function setRaw($data) {
		$this->_raw = $data;
		$this->_isRaw = true;

		return $this;
	}

	/**
	 * @return string|array|null
	 */
	public function getRaw() {
		return $this->_raw;
	}

	/**
	 * @param \Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function add(ItemInterface $item) {
		$item->setParent($this);
		$this->getSubMenu()->add($item);

		return $this;
	}

	/**
	 * @return \Menu\MenuInterface
	 */
	public function getSubMenu() {
		//TODO
	}

	/**
	 * @param \Menu\MenuInterface $menu
	 *
	 * @return $this
	 */
	public function setSubMenu(MenuInterface $menu) {
		//TODO

		return $this;
	}

	/**
	 * @param string|null $name
	 *
	 * @return mixed|array|null
	 */
	public function getData($name = null) {
		if ($name === null) {
			return $this->_attributes;
		}

		if (!isset($this->_attributes[$name])) {
			return null;
		}

		return $this->_attributes[$name];
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
	 * @return string|null
	 */
	public function getLabel() {
		return $this->_label;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->_id;
	}

	/**
	 * @param string $id
	 *
	 * @return $this
	 */
	public function setId($id) {
		$this->_id = $id;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isActive() {
		if (is_bool($this->_isActive)) {
			return $this->_isActive;
		}

		if (is_callable($this->_isActive)) {
			$isActive = $this->_isActive;

			return $isActive($this);
		}

		throw new RuntimeException('Error getting the active status for item');
	}

	/**
	 * @return bool
	 */
	public function isVisible() {
		return $this->_isVisible;
	}

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setAttribute($name, $value) {
		$this->_attributes[$name] = $value;

		return $this;
	}

	/**
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function setAttributes(array $attributes) {
		$this->_attributes = $attributes;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->_attributes;
	}

	/**
	 * @return bool
	 */
	public function hasParent() {
		return !empty($this->_parent);
	}

	/**
	 * @param \Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function setParent(ItemInterface $item) {
		$this->_parent = $item;
		$this->_parentId = $item->getId();

		return $this;
	}

	/**
	 * @return \Menu\Item\ItemInterface|null
	 */
	public function getParent() {
		return $this->_parent;
	}

	/**
	 * @return int|string
	 */
	public function getParentId() {
		return $this->_parentId;
	}

}
