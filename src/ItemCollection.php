<?php
declare(strict_types = 1);

namespace Cake\Menu;

use RuntimeException;

use Cake\Collection\Collection;
use Cake\Menu\Item\ItemInterface;

class ItemCollection extends Collection {

	/**
	 * Adds an item to the collection
	 */
	public function add(ItemInterface $item) {
		$this->addMany([$item]);
	}

	/**
	 * Adds many items to the collection
	 */
	public function addMany(array $items = []) {
		foreach ($items as $key => $item) {
			if (!$item instanceof ItemInterface) {
				throw new RuntimeException(sprintf(
					'Item #`%s` does not implement `%s`',
					$key,
					ItemInterface::class
				));
			}
		}

		$this->append($items);
	}

	public function findByParent($parentId) {
		return $this->filter(function($item, $key) use ($parentId) {
			return $item->getParentId() === $parentId;
		});
	}

	public function findById($id) {
		return $this->filter(function($item, $key) use ($id) {
			return $item->getId() === $id;
		});
	}

}
