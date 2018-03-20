<?php
declare(strict_types = 1);

namespace Menu\Link;

use Cake\Routing\Router;
use LogicException;

class Link implements LinkInterface {

	/**
	 * @var array
	 */
	protected $_attributes = [];

	/**
	 * @var string|array|null
	 */
	protected $_url;

	/**
	 * @var bool
	 */
	protected $_external = false;

	/**
	 * @var string
	 */
	protected $_title;

	/**
	 * Convenience factory method
	 *
	 * @param string|array|null $url
	 * @param bool $external
	 *
	 * @return static
	 */
	public static function create($url = null, $external = false) {
		$link = new static();
		if ($url !== null) {
			$link->setUrl($url, $external);
		}

		return $link;
	}

	/**
	 * @param string|array $url
	 * @param bool $external
	 *
	 * @return $this
	 */
	public function setUrl($url, $external = false) {
		if ($external && is_array($url)) {
			throw new LogicException('URL array must always be an internal link, otherwise transform into string manually before passing.');
		}

		$this->_url = $url;
		$this->_external = $external;

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
	 * @return string|array|null
	 */
	public function getRawUrl() {
		return $this->_url;
	}

	/**
	 * @return string|null
	 */
	public function getUrl() {
		$rawUrl = $this->getRawUrl();
		if ($rawUrl === null) {
			return null;
		}

		return $this->_builder($rawUrl);
	}

	/**
	 * @param string|array $rawUrl
	 *
	 * @return string
	 */
	protected function _builder($rawUrl) {
		if ($this->_external) {
			return $rawUrl;
		}

		return Router::url($rawUrl);
	}

}
