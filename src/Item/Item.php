<?php
declare(strict_types = 1);

namespace Cake\Menu\Item;

use Cake\Menu\Link\LinkInterface;
use Cake\Menu\MenuInterface;

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
	 * @var bool
	 */
	protected $_isActive = false;

	/**
	 * @var array
	 */
	protected $_attributes = [];

	/**
	 * @var string|null
	 */
	protected $_title;

	/**
	 * @var string|null
	 */
	protected $_data;

	/**
	 * @var string|array|null
	 */
	protected $_raw;

	/**
	 * @var \Cake\Menu\Link\LinkInterface|null
	 */
	protected $_link;

	/**
	 * @var \Cake\Menu\Item\ItemInterface|null
	 */
	protected $_parent;

	/**
	 * @param string|null $title
	 * @param \Cake\Menu\Link\LinkInterface|null $link
	 */
	public function __construct($title = null, $link = null) {
		$this->_id = str_replace('.', '', uniqid('id-', true));

		$this->setTitle($title);
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
	 * @param bool $isActive
	 *
	 * @return $this
	 */
	public function setActive($isActive) {
		$this->_isActive = $isActive;

		return $this;
	}

	/**
	 * @param \Cake\Menu\Link\LinkInterface|null $link
	 *
	 * @return $this
	 */
	public function setLink(?LinkInterface $link) {
		$this->_link = $link;

		return $this;
	}

	/**
	 * @return \Cake\Menu\Link\LinkInterface|null
	 */
	public function getLink() {
		return $this->_link;
	}

	/**
	 * @param string|null $title
	 *
	 * @return $this
	 */
	public function setTitle($title) {
		$this->_title = $title;

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
	 * @param \Cake\Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function add(ItemInterface $item) {
		$item->setParent($this);
		$this->getSubMenu()->add($item);

		return $this;
	}

	/**
	 * @return \Cake\Menu\MenuInterface
	 */
	public function getSubMenu() {
		//TODO
	}

	/**
	 * @param \Cake\Menu\MenuInterface $menu
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
	public function getTitle() {
		return $this->_title;
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
		return $this->_isActive;
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
	 * @param \Cake\Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function setParent(ItemInterface $item) {
		$this->_parent = $item;

		return $this;
	}

	/**
	 * @return \Cake\Menu\Item\ItemInterface|null
	 */
	public function getParent() {
		return $this->_parent;
	}

}
