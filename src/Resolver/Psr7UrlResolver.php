<?php
namespace Cake\Menu\Resolver;

use Cake\Core\InstanceConfigTrait;
use Cake\Menu\ItemInterface;
use Psr\Http\Message\RequestInterface;

class Psr7UrlResolver implements ResolverInterface {

	use InstanceConfigTrait;

	/**
	 * @var \Psr\Http\Message\RequestInterface
	 */
	protected $_request;

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
	];

	/**
	 * @param \Psr\Http\Message\RequestInterface $request
	 * @param array $options
	 */
	public function __construct(RequestInterface $request, array $options = []) {
		$this->_request = $request;
		$this->setConfig($options);
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
