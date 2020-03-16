<?php


namespace ree\stackStorage\listener;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use Ree\StackStrage\gui\StackStorage;

class EventListener implements Listener
{
	public function onChange(InventoryTransactionEvent $ev) {
		$tr = $ev->getTransaction();
		$p = $tr->getSource();
		$inventories = $tr->getInventories();

		foreach ($inventories as $inv) {
			if ($inv instanceof StackStorage) {
				foreach ($tr->getActions() as $act) {
				}
			}
		}
	}
}