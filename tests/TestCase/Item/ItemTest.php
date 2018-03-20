<?php
declare(strict_types = 1);

namespace Menu\TestCase\Item;

use Cake\TestSuite\TestCase;
use Menu\Item\Item;
use Menu\Link\Link;
use Menu\Renderer\StringTemplateRenderer;

class ItemTest extends TestCase {
	/**
	 * @return void
	 */
	public function testItem() {
		$item = (new Item())
			->setLabel('First<>')
			->setLink(new Link());

		$item2 = (new Item())
			->setLabel('Se<b>co</b>nd', true)
			->setLink(new Link());

		$this->assertSame('First&lt;&gt;', $item->getLabel());
		$this->assertSame('Se<b>co</b>nd', $item2->getLabel());
	}

	/**
	 * @return void
	 */
	public function testItemRender() {
		$item = (new Item())
			->setLabel('First')
			->setLink(Link::create('/abc'));

		$renderer = new StringTemplateRenderer();

		$expected = '<a href="/abc">First</a>';
		$result = $renderer->renderItem($item);
		$this->assertSame($expected, $result);
	}

}
