<?php


namespace ree_jp\stackstorage\listener;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use ree_jp\stackstorage\api\StackStorageAPI;
use ree_jp\stackstorage\StackStoragePlugin;

class EventListener implements Listener
{
    public function onLogin(PlayerLoginEvent $ev)
    {
        if (StackStoragePlugin::$instance->getConfig()->get("problem_auto_solution")) {
            StackStorageAPI::$instance->solutionProblem($ev->getPlayer()->getXuid());
        }
    }

    public function onClose(InventoryCloseEvent $ev)
    {
        $p = $ev->getPlayer();

        for ($slot = 0; $slot < $p->getInventory()->getSize(); $slot++) {
            $item = $p->getInventory()->getItem($slot);
            $afterItem = StackStorageAPI::$instance->setStoredNbtTag($item);
            if (!is_null($afterItem)) {
                $p->getInventory()->setItem($slot, $afterItem);
            }
        }
    }
}
