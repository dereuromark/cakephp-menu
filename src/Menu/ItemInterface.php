<?php
namespace Cake\Menu;

interface ItemInterface {

	/**
	 * @param string $title
	 */
	public function setTitle($title);

	/**
	 * @return string
	 */
	public function getTitle();

	/**
	 * @return $this
	 */
	public function setLink(LinkInterface $link);

	public function getId();

	public function setId();

	public function isActive();

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
