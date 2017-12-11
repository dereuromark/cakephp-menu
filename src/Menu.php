<?php
declare(strict_types = 1);

namespace Cake\Menu;

class Menu implements MenuInterface
{

    protected $_attributes = [];

    public function add(ItemInterface $item)
    {
        return $this;
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

    public function sortBy($name, $direction = 'desc')
    {
        if (is_callable($name)) {
            $name($this, $direction);
        }

        $this->_sort($name, $direction);
    }

    protected function _sort($name, $direction)
    {
    }
}
