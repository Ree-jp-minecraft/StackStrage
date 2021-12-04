<?php


namespace ree_jp\stackStorage\listener;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use ree_jp\stackStorage\api\StackStorageAPI;

class EventListener implements Listener
{
    public function onClose(InventoryCloseEvent $ev)
    {
        $p = $ev->getPlayer();

        for ($slot = 0; $slot < $p->getInventory()->getSize(); $slot++) {
            $item = $p->getInventory()->getItem($slot);
            $p->getInventory()->setItem($slot, StackStorageAPI::$instance->setStoredNbtTag($item));
        }
    }
}
