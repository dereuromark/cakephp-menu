<?php

namespace Menu\Resolver;

use Menu\Item\ItemInterface;

class LoggedInResolver implements ResolverInterface {

	/**
	 * @var array|\ArrayObject
	 */
	protected $_user;

	/**
	 * @param array|\ArrayObject $user
	 */
	public function __construct($user) {
		$this->_user = $user;
	}

	/**
	 * @param \Menu\Item\ItemInterface $item
	 * @return void
	 */
	public function resolve(ItemInterface $item) {
		if ($this->_user) {
			$item->setVisibility(true);
		}
	}

}
