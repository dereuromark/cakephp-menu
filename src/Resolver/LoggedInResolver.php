<?php
namespace Cake\Menu\Resolver;

use Cake\Menu\ItemInterface;

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
	 * @param \Cake\Menu\ItemInterface $item
	 * @return void
	 */
	public function resolve(ItemInterface $item) {
		if ($this->_user) {
			$item->setVisibility(true);
		}
	}

}
