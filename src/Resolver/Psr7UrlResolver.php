<?php
namespace Cake\Menu\Resolver;

use Cake\Menu\ItemInterface;
use Psr\Http\Message\RequestInterface;

class Psr7UrlResolver implements ResolverInterface {

	/**
	 * @var \Psr\Http\Message\RequestInterface
	 */
	protected $_request;

	/**
	 * @param \Psr\Http\Message\RequestInterface $request
	 * @param array $options
	 */
	public function __construct(RequestInterface $request, array $options = []) {
		$this->_request = $request;
	}

	/**
	 * @param \Cake\Menu\ItemInterface $item
	 * @return void
	 */
	public function resolve(ItemInterface $item) {
		if ((string)$this->_request->getUri() === $item->getLink()->getUrl()) {
			$item->setActive(true);
		}
	}

}
