<?php
declare(strict_types = 1);

namespace Cake\Menu;

interface MenuInterface
{

    public function add(ItemInterface $item);

    public function remove($itemId);

    public function setData($name, $value);

    public function getData($name);

    public function getAttributes();

    public function setAttribute($name, $value);

    /**
     * @param callable|string $by
     */
    public function filter($by, $options);

    public function sortBy($name, $direction = 'desc');
}
