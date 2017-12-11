<?php
declare(strict_types = 1);

namespace Cake\Menu;

class Item implements ItemInterface
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
    public function getTitle() {
        return $this->_title;
    }

    public function getId() {
        // TODO: Implement getId() method.
    }

    public function setId() {
        // TODO: Implement setId() method.
    }

    public function isActive() {
        // TODO: Implement isActive() method.
    }

    public function isVisible() {
        // TODO: Implement isVisible() method.
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAttribute($name, $value) {
        // TODO: Implement setAttribute() method.
    }

    /**
     * @return array
     */
    public function getAttributes() {
        // TODO: Implement getAttributes() method.
    }

    /**
     * @return bool
     */
    public function hasParent() {
        // TODO: Implement hasParent() method.
    }

    public function setParent(ItemInterface $item) {
        // TODO: Implement setParent() method.
    }

    public function getParent() {
        // TODO: Implement getParent() method.
    }
}

/*
$menu = (new Menu())
    ->setStatusResolver(new StatusResolver())
    ->add(new Item())
    ->addNewItem();
*/
