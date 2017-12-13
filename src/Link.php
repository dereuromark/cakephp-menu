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
	 * @var string|array
	 */
	protected $_url;

	/**
	 * @var string
	 */
	protected $_title;

	/**
	 * @param string|array $url
	 *
	 * @return $this
	 */
	public function setUrl($url) {
		$this->_url = $url;

		return $this;
	}

	/**
	 * @param string $title
	 *
	 * @return $this
	 */
	public function setTitle($title) {
		$this->_title = $title;

		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 *
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
	 * @return string|array
	 */
	public function getRawUrl() {
		return $this->_url;
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
	 *
	 * @return string
	 */
	protected function _builder($rawUrl) {
	    // Really good? What about string relative ones?
		if (is_string($rawUrl)) {
			return $rawUrl;
		}

		return Router::url($rawUrl);
	}

}
