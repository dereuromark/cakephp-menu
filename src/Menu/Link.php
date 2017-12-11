<?php
namespace Cake\Menu;

use Cake\Routing\Router;

class Link implements LinkInterface {

	protected $_attributes = [];

	public function setAttribute($name, $value) {
		$this->_attributes[$name] = $value;

		return $this;
	}

	public function getAttributes() {
		return $this->_attributes;
	}

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
	 * @return string
	 */
	protected function _builder($rawUrl) {
		return Router::url($rawUrl);
	}
}
