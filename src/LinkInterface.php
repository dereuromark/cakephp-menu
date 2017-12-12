<?php
declare(strict_types = 1);

namespace Cake\Menu;

interface LinkInterface {

	public function setAttribute($name, $value);

	public function getAttributes();

	/**
	 * @return mixed Returns whatever the implementation expects to build an URL from
	 */
	public function getRawUrl();

	/**
	 * @return string
	 */
	public function getUrl();

}
