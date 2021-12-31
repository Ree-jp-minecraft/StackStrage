<?php


namespace ree_jp\stackStorage\listener;

use Exception;
use pocketmine\block\BlockIds;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\GuiAPI;
use ree_jp\stackStorage\api\IGuiAPI;
use ree_jp\stackStorage\api\StackStorageAPI;
use ree_jp\stackStorage\gui\StackStorage;
use ree_jp\stackStorage\virtual\VirtualStackStorage;

class EventListener implements Listener
{

    public function onClose(InventoryCloseEvent $ev)
    {
        $p = $ev->getPlayer();
        $n = $p->getName();

        try {
            GuiAPI::$instance->getGui($n);
            GuiAPI::$instance->closeGui($n);
            StackStorageAPI::$instance->closeCache($p->getXuid());
        } catch (Exception $ex) {
            if ($ex->getCode() === IGuiAPI::GUI_NOT_FOUND) return;
            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
        }

        $this->removeLore($p);
    }

    public function onChange(InventoryTransactionEvent $ev)
    {
        $tr = $ev->getTransaction();
        $p = $tr->getSource();
        $n = $p->getName();
        $xuid = $p->getXuid();
        $isNavigate = false;

        foreach ($tr->getActions() as $act) {
            if ($ev->isCancelled()) return;

            if ($act instanceof SlotChangeAction) {
                if ($act->getInventory() instanceof VirtualStackStorage) {
                    if (!StackStorageAPI::$instance->isOpen($n)) {
                        $ev->setCancelled();
                        $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                        $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : access to unauthorized storage');
                    }
                    if ($act->getSourceItem()->getId() !== BlockIds::AIR) {
                        switch ($act->getSlot()) {
                            case StackStorage::BACK:
                                StackStorageAPI::$instance->backPage($xuid);
                                $isNavigate = true;
                                break;

                            case StackStorage::NEXT:
                                StackStorageAPI::$instance->nextPage($xuid);
                                $isNavigate = true;
                                break;

//							crash problem
//							https://github.com/Ree-jp-minecraft/StackStrage/issues/8
//
//							case StackStorage::CLOSE:
//								try {
//									GuiAPI::$instance->closeGui($n);
//								} catch (Exception $ex) {
//									$p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
//									$p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
//								}
//								$ev->setCancelled();
//								return;
                        }
                    }
                    if ($act->getTargetItem()->getId() !== BlockIds::AIR) {
                        $item = $act->getTargetItem();
                        try {
                            StackStorageAPI::$instance->add($xuid, $item);
                        } catch (Exception $e) {
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $e->getMessage());
                            $ev->setCancelled();
                            return;
                        }
                        StackStorageAPI::$instance->refresh($xuid);
                    }
                    if ($act->getSourceItem()->getId() !== BlockIds::AIR) {
                        try {
                            $item = $act->getSourceItem();
                            if (!StackStorageAPI::$instance->hasCountFromCache($xuid, $item)) throw new Exception('could not reduce items');
                            StackStorageAPI::$instance->remove($xuid, $item);
                        } catch (Exception $e) {
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $e->getMessage());
                            $ev->setCancelled();
                            return;
                        }
                        StackStorageAPI::$instance->refresh($xuid);
                    }
                }

            }
        }
        if ($isNavigate) {
            $ev->setCancelled();
        }
//        $this->removeLore($p);
    }

    private function removeLore(Player $p): void
    {
        for ($slot = 0; $slot < $p->getInventory()->getSize(); $slot++) {
            $item = $p->getInventory()->getItem($slot);
            $p->getInventory()->setItem($slot, StackStorageAPI::$instance->setStoredNbtTag($item));
        }
    }
}
