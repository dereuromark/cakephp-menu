<?php
declare(strict_types = 1);

namespace Menu\TestCase\Resolver;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Link\Link;
use Menu\Resolver\Psr7UrlResolver;
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
