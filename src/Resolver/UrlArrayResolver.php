<?php
namespace Cake\Menu\Resolver;

use Cake\Menu\ItemInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlArrayResolver implements ResolverInterface {

	/**
	 * @var \Psr\Http\Message\ServerRequestInterface
	 */
	protected $_request;

	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @param array $options
	 */
	public function __construct(ServerRequestInterface $request, array $options = []) {
		$this->_request = $request;
	}

	/**
	 * @param \Cake\Menu\ItemInterface $item
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
