<?php
declare(strict_types = 1);

namespace Menu\TestCase\Resolver;

use Cake\Http\ServerRequestFactory;
use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Link\Link;
use Menu\Resolver\UrlArrayResolver;

class UrlArrayResolverTest extends TestCase {

	/**
	 * @return void
	 */
	public function testItemArray() {
		$link = new Link();
		$link->setUrl([
			'controller' => 'FooBar',
			'action' => 'edit'
		]);
		$item = new Item('User Listing', $link);

		$server = [
			'REQUEST_URI' => '...',
		];
		$request = ServerRequestFactory::fromGlobals($server);
		$params = [
			'controller' => 'FooBar',
			'action' => 'edit',
		];
		$request = $request->withAttribute('params', $params);

		$resolver = new UrlArrayResolver($request);
		$resolver->resolve($item);

		$this->assertTrue($item->isActive());

		$link->setUrl([
			'controller' => 'FooBar',
			'action' => 'add'
		]);
		$item = new Item('User Listing', $link);
		$this->assertFalse($item->isActive());
	}

}
