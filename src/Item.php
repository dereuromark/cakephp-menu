<?php
declare(strict_types = 1);

namespace Cake\Menu;

class Item implements ItemInterface
{

    protected $_id;
    protected $_isRaw = true;
    protected $_isDivider = true;
    protected $_isVisible = true;
    protected $_isActive = false;
    protected $_attributes = [];
    protected $_title;
    protected $_data;
    protected $_link;
    protected $_parent;
    protected $_raw;

    public function __construct($title = null, $link = null)
    {
        $this->_id = str_replace('.', '', uniqid('id-', true));

        $this->setTitle($title);
        if ($link instanceof LinkInterface) {
            $this->setLink($link);
        }
    }

    public function setVisibility($isVisible)
    {
        $this->_isVisible = $isVisible;

        return $this;
    }

    public function setActive($isActive)
    {
        $this->_isActive = $isActive;

        return $this;
    }

    public function setLink(LinkInterface $link)
    {
        $this->_link = $link;

        return $this;
    }

    public function getLink() {
        return $this->_link;
    }

    public function setTitle($title)
    {
        $this->_title = $title;

        return $this;
    }

    public function isRaw()
    {
        return $this->_isRaw;
    }

    public function setRaw($data)
    {
        $this->_raw = $data;
        $this->_isRaw = true;

        return $this;
    }

    public function getRaw()
    {
    	return $this->_raw;
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

    public function getData($name = null)
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

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setId($id)
    {
        $this->_id = $id;

        return $this;
    }

    public function isActive()
    {
        return $this->_isActive;
    }

    public function isVisible()
    {
        return $this->_isVisible;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->_attributes[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return !empty($this->_parent);
    }

    public function setParent(ItemInterface $item)
    {
        $this->_parent= $item;

        return $this;
    }

    public function getParent()
    {
        return $this->_parent;
    }
}
