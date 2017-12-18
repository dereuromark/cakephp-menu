<?php
declare(strict_types = 1);

namespace Cake\Menu;

use Cake\Menu\Item\ItemInterface;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

interface MenuInterface {

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	/**
	 * @param \Cake\Menu\Item\ItemInterface $item
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

/*
$menu = new Menu();
$menu->setAttribute(['id' => 'menu']);
$menu->setAttribute(['id' => 'menu']);


