<?php
declare(strict_types = 1);

namespace Cake\Menu;

interface MenuInterface {

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	public function add(ItemInterface $item);

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
