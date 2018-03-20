<?php
declare(strict_types = 1);

namespace Menu\Item;

use Menu\Link\LinkInterface;

/**
 * Item Interface
 */
interface ItemInterface {

	/**
	 * @param string $key
	 *
	 * @return $this
	 */
	public function setKey($key);

	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @return bool
	 */
	public function isRaw();

	/**
	 * @param string $content
	 *
	 * @return $this
	 */
	public function setRaw($content);

	/**
	 * @return string
	 */
	public function getRaw();

	/**
	 * @param string $title
	 *
	 * @return $this
	 */
	public function setLabel($title);

	/**
	 * @return string
	 */
	public function getLabel();

	/**
	 * @param \Menu\Link\LinkInterface|null $link Link Object
	 *
	 * @return $this
	 */
	public function setLink(?LinkInterface $link);

	/**
	 * @return \Menu\Link\LinkInterface
	 */
	public function getLink();

	/**
	 * @return string
	 */
	public function getId();

	/**
	 * @param string $id
	 *
	 * @return $this
	 */
	public function setId($id);

	/**
	 * @return bool
	 */
	public function isActive();

	/**
	 * @param bool|callable $isActive Sets the active status
	 *
	 * @return $this
	 */
	public function setActive($isActive);

	/**
	 * @return bool
	 */
	public function isVisible();

	/**
	 * Sets the visibility of the item
	 *
	 * @param bool $isVisible Is visible or not
	 *
	 * @return $this
	 */
	public function setVisibility($isVisible);

	/**
	 * @param \Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function add(ItemInterface $item);

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function setData($name, $value);

	/**
	 * @param string|null $name
	 *
	 * @return mixed
	 */
	public function getData($name = null);

	/**
	 * Sets a single attribute.
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setAttribute($name, $value);

	/**
	 * Replaces all attributes completely.
	 *
	 * @param array $attributes
	 *
	 * @return $this
	 */
	public function setAttributes(array $attributes);

	/**
	 * @return array
	 */
	public function getAttributes();

	/**
	 * @return bool
	 */
	public function hasParent();

	/**
	 * @param \Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function setParent(ItemInterface $item);

	/**
	 * @return \Menu\Item\ItemInterface
	 */
	public function getParent();

	/**
	 * @return string|int
	 */
	public function getParentId();

}
