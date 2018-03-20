<?php
declare(strict_types = 1);

namespace Menu;

use Cake\Collection\Collection;
use Menu\Item\ItemInterface;
use RuntimeException;

/**
 * Item Collection for a Menu
 */
class ItemCollection extends Collection {

	/**
	 * Adds an item to the collection
	 *
	 * @param \Menu\Item\ItemInterface $item Item
	 * @return void
	 */
	public function add(ItemInterface $item) {
		$this->addMany([$item]);
	}

	/**
	 * Adds many items to the collection
	 *
	 * @param array $items
	 * @return void
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

	/**
	 * Find all items by parent id
	 *
	 * @param int|string|\Menu\Item\ItemInterface $parentId Parent Id or Item
	 * @return array
	 */
	public function findByParent($parentId) {
		if ($parentId instanceof ItemInterface) {
			$parentId = $parentId->getId();
		}

		return $this->filter(function($item) use ($parentId) {
			return $item->getParentId() === $parentId;
		});
	}

	/**
	 * Finds a single item by its id
	 *
	 * @param string $id
	 * @return null|string|int
	 */
	public function findById($id) {
		$result = $this->filter(function(ItemInterface $item) use ($id) {
			return $item->getId() === $id;
		});

		return $result->first();
	}

}
