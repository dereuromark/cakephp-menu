<?php
declare(strict_types = 1);

namespace Cake\Menu\TestCase\Resolver;

use Cake\Menu\Item\Item;
use Cake\Menu\Link\Link;
use Cake\Menu\Resolver\Psr7UrlResolver;
use Cake\TestSuite\TestCase;
use Zend\Diactoros\Request;

class Psr7UrlResolverTest extends TestCase {

	/**
	 * @return void
	 */
	public function testItem() {
		$link = new Link();
		$link->setUrl('http://www.cakephp.org/users?sort=desc&name=florian');
		$item = new Item('User Listing', $link);
		$request = new Request('http://www.cakephp.org/users?sort=desc&name=florian');
		$resolver = new Psr7UrlResolver($request);
		$resolver->resolve($item);

		$this->assertTrue($item->isActive());
	}

}
