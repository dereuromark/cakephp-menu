<?php
declare(strict_types = 1);

namespace Cake\Menu;

class Item
{
	protected $_isVisible = true;
	protected $_isActive = false;
	protected $_attributes = [];
	protected $_title;
	protected $_data;
	protected $_link;

    public function __construct($title = null, $link = null)
    {
        $this->setTitle($title);
        if ($link instanceof LinkInterface) {
            $this->setLink($link);
        }
    }

    public function setLink(LinkInterface $link) {
    	$this->_link = $link;
    }

    public function setTitle($title) {
    	$this->_title = $title;

    	return $this;
    }

    public function add(ItemInterface $item)
    {
        $item->setParent($this);
        $this->getSubMenu()->add($item);
    }

    public function getSubMenu()
    {
    }

    public function SetSubMenu()
    {
    }

    public function getData($name)
    {
        if (!isset($this->_attributes[$name])) {
            return null;
        }

        return $this->_attributes[$name];
    }

    public function setData($name, $value)
    {
        $this->_attributes[$name] = $value;

        return $this;
    }
}

/*
$menu = (new Menu())
	->setStatusResolver(new StatusResolver())
	->add(new Item())
	->addNewItem();
*/
