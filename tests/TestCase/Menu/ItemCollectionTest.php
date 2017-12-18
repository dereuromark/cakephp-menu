<?php
declare(strict_types = 1);

namespace Cake\Menu\TestCase\Menu;

use Cake\Menu\Item\Item;
use Cake\Menu\ItemCollection;
use Cake\TestSuite\TestCase;

/**
 * Item Collection Test
 */
class ItemCollectionTest extends TestCase {

	/**
	 * Testing the collection
	 *
	 * @return void
	 */
	public function testCollection() {
		$item1 = (new Item('Test1'))->setId(1);
		$item2 = (new Item('Test2'))->setId(2);
		$item2_1 = (new Item('Test2'))->setId(3)->setParent($item2);

		$collection = new ItemCollection(compact('item1', 'item2', 'item2_1'));

		$item = $collection->findById(3);
		$this->assertEquals(3, $item->getId());

		debug($collection->findByParent(3));

		$menu->add('1');
		$menu->add('2');
		$menu->add('3');
		$menu->add('3.1')->setParent($menu->get('3'));
		$menu->add('3.2')->setParent($menu->get('3'));

		$three = $menu->get(3);
		$three->addChildren([
			new Item('3.1'),
			new Item('3.2')
		]);
	}

}
