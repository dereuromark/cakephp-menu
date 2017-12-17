<?php
declare(strict_types = 1);

namespace Cake\Menu\TestCase\Menu;

use Cake\Menu\Item\Item;
use Cake\Menu\ItemCollection;
use Cake\TestSuite\TestCase;

class ItemCollectionTest extends TestCase {

	public function testCollection() {
		$collection = new ItemCollection([]);

		$item1 = (new Item('Test1'))->setId(1);
		$item2 = (new Item('Test2'))->setId(2);
		$item2_1 = (new Item('Test2'))->setId(3)->setParent($item2);

		$collection->addMany(compact('item1', 'item2', 'item2_1'));

		$item = $collection->findById(3);
		debug($item);
		debug($collection->findByParent(3));
	}
}
