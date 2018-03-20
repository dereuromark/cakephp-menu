<?php
declare(strict_types = 1);

namespace Menu;

use Menu\Item\ItemInterface;

interface MenuInterface {

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	/**
	 * @param \Menu\Item\ItemInterface $item
	 *
	 * @return $this
	 */
	public function add(ItemInterface $item);

	/**
	 * @param string $itemId
	 * @return $this
	 */
	public function remove($itemId);

	/**
	 * @return \Menu\Item\ItemInterface[]
	 */
	public function getItems();

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function setData($name, $value);

	/**
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function getData($name);

	/**
	 * @return array
	 */
	public function getAttributes();

	/**
	 * @param string $name
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function setAttribute($name, $value);

	/**
	 * Sets multiple HTML attributes
	 *
	 * @param array $attributes Attributes
	 * @param bool $merge Merge them or not
	 *
	 * @return void
	 */
	public function setAttributes(array $attributes, $merge = false);

	/**
	 * @param callable|string $by
	 * @param array $options
	 *
	 * @return void
	 */
	public function filter($by, array $options);

	/**
	 * @param string $name
	 * @param string $direction
	 *
	 * @return void
	 */
	public function sortBy($name, $direction = self::SORT_DESC);

}
