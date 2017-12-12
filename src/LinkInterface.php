<?php
declare(strict_types = 1);

namespace Cake\Menu;

interface LinkInterface {

	/**
	 * Sets HTML attributes for the link
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setAttribute($name, $value);

	/**
	 * Gets all attributes as array
	 *
	 * @return array Attributes
	 */
	public function getAttributes();

	/**
	 * Returns whatever the implementation expects to build an URL from
	 *
	 * @return mixed
	 */
	public function getRawUrl();

	/**
	 * Gets the URL
	 *
	 * @return string
	 */
	public function getUrl();

}
