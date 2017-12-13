<?php
namespace Cake\Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Cake\Menu\Item\ItemInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlArrayResolver implements ResolverInterface {

	use InstanceConfigTrait;

	/**
	 * @var \Psr\Http\Message\ServerRequestInterface
	 */
	protected $_request;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
	];

	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param array $options
	 */
	public function __construct(ServerRequestInterface $request, array $options = []) {
		$this->_request = $request;
		$this->setConfig($options);
	}

	/**
	 * @param \Cake\Menu\Item\ItemInterface $item
	 * @return void
	 */
	public function resolve(ItemInterface $item) {
		$linkArray = $item->getLink()->getRawUrl();

		$requestArray = $this->_request->getAttribute('params');

		if ($this->_match($requestArray, $linkArray)) {
			$item->setActive(true);
		}
	}

	/**
	 * @param array $requestArray
	 * @param array $linkArray
	 * @return bool
	 */
	protected function _match(array $requestArray, array $linkArray) {
		//TODO: also plugin and prefix
		if ($requestArray['controller'] !== $linkArray['controller']) {
			return false;
		}
		if ($requestArray['action'] !== $linkArray['action']) {
			return false;
		}

		return true;
	}

}
