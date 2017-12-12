<?php
declare(strict_types = 1);

namespace Cake\Menu;

use Cake\Routing\Router;

class Link implements LinkInterface {

	/**
	 * @var array
	 */
	protected $_attributes = [];

	/**
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setAttribute($name, $value) {
		$this->_attributes[$name] = $value;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return $this->_attributes;
	}

	/**
	 * @return string
	 */
	public function getRawUrl() {
		// TODO: Implement getRawUrl() method.
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		$rawUrl = $this->getRawUrl();
		if (is_string($rawUrl)) {
			return $rawUrl;
		}

		return $this->_builder($rawUrl);
	}

	/**
	 * @param string|array $rawUrl
	 * @return string
	 */
	protected function _builder($rawUrl) {
		return Router::url($rawUrl);
	}

}
