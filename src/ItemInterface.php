<?php
declare(strict_types = 1);

namespace Cake\Menu;

interface ItemInterface
{

    /**
     * @param string $title
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param \Cake\Menu\LinkInterface $link Link Object
     * @return $this
     */
    public function setLink(LinkInterface $link);

    public function getId();

    public function setId();

    public function isActive();

    /**
     * @param bool $isActive Sets the active status
     */
    public function setActive($isActive);

    /**
     * Sets the visibility of the item
     *
     * @param bool $isVisible Is visible or not
     */
    public function setVisibility($isVisible);

    public function isVisible();

    public function add(ItemInterface $item);

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setData($name, $value);

    /**
     * @param string|null
     * @return mixed
     */
    public function getData($name = null);

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setAttribute($name, $value);

    /**
     * @return array
     */
    public function getAttributes();

    /**
     * @return bool
     */
    public function hasParent();

    public function setParent(ItemInterface $item);

    public function getParent();
}
