<?php
declare(strict_types = 1);

namespace Cake\Menu;

class Item {

	public function __construct($title, $link = null) {
		$this->setTitle($title);
		if ($link instanceof LinkInterface) {
			$this->setLink($link);
		}
	}

	public function add(ItemInterface $item) {
		$item->setParent($this);
		$this->getSubMenu()->add($item);
	}

	public function getSubMenu() {

	}

	public function SetSubMenu() {

	}

	public function getData($name) {
		if (!isset($this->_attributes[$name])) {
			return null;
		}

		return $this->_attributes[$name];
	}

	public function setData($name, $value) {
		$this->_attributes[$name] = $value;

		return $this;
	}

}

/*
$menu = (new Menu())
	->setStatusResolver(new StatusResolver())
	->add(new Item())
	->addNewItem();
