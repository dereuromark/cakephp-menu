<?php
declare(strict_types = 1);

namespace Cake\Menu;

class Menu implements MenuInterface
{

    protected $_itemCollection;
    protected $_attributes = [];
    protected $_itemClass = Item::class;

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

    public function addRaw($title, $link, $attributes) {
        (new $this->_itemClass())
            ->setTitle($title)
            ->setLink();
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

    public function remove($itemId) {
        // TODO: Implement remove() method.
    }

    public function getAttributes() {
        // TODO: Implement getAttributes() method.
    }

    public function setAttribute($name, $value) {
        // TODO: Implement setAttribute() method.
    }

    /**
     * @param callable|string $by
     */
    public function filter($by, $options) {
        // TODO: Implement filter() method.
    }
}
