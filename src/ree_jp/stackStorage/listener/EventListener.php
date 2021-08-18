<?php


namespace ree_jp\stackStorage\listener;

use Exception;
use pocketmine\block\BlockIds;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\GuiAPI;
use ree_jp\stackStorage\api\IGuiAPI;
use ree_jp\stackStorage\api\StackStorageAPI;
use ree_jp\stackStorage\gui\StackStorage;
use ree_jp\stackStorage\sql\StackStorageHelper;
use ree_jp\stackStorage\virtual\VirtualStackStorage;

class EventListener implements Listener
{
    public function onLogin(PlayerLoginEvent $ev)
    {
        $p = $ev->getPlayer();

        try {
            StackStorageHelper::$instance->setTable($p->getXuid());
        } catch (Exception $ex) {
            Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
            Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
        }
    }

    public function onClose(InventoryCloseEvent $ev)
    {
        $p = $ev->getPlayer();
        $n = $p->getName();

        try {
            GuiAPI::getInstance()->getGui($n);
            GuiAPI::getInstance()->closeGui($n);
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
                                StackStorageAPI::$instance->backPage($n);
                                $ev->setCancelled();
                                return;

                            case StackStorage::NEXT:
                                StackStorageAPI::$instance->nextPage($n);
                                $ev->setCancelled();
                                return;

//							crash problem
//							https://github.com/Ree-jp-minecraft/StackStrage/issues/8
//
//							case StackStorage::CLOSE:
//								try {
//									GuiAPI::getInstance()->closeGui($n);
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
                        StackStorageAPI::$instance->refresh($n);
                    }
                    if ($act->getSourceItem()->getId() !== BlockIds::AIR and $act->getSlot() < 45) {
                        $item = $act->getSourceItem();
                        if (StackStorageAPI::$instance->getItem($xuid, $item)->getCount() < $item->getCount()) {
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : could not reduce items');
                            $ev->setCancelled();
                            return;
                        }
                        try {
                            StackStorageAPI::$instance->remove($xuid, $item);
                        } catch (Exception $e) {
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $e->getMessage());
                            $ev->setCancelled();
                            return;
                        }
                        StackStorageAPI::$instance->refresh($n);
                    }
                }

            }
        }
        $this->removeLore($p);
    }

    private function removeLore(Player $p): void
    {
        for ($slot = 0; $slot < $p->getInventory()->getSize(); $slot++) {
            if ($p->getInventory()->getItem($slot)->getLore() !== []) {
                $p->getInventory()->setItem($slot, $p->getInventory()->getItem($slot)->setLore([]));
            }
        }
    }
}
