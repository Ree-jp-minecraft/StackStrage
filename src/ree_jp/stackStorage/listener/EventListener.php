<?php


namespace ree_jp\stackStorage\listener;

use Exception;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\GuiAPI;
use ree_jp\stackStorage\api\StackStorageAPI;
use ree_jp\stackStorage\gui\StackStorage;
use ree_jp\stackStorage\sqlite\StackStorageHelper;
use ree_jp\stackStorage\virtual\VirtualStackStorage;

class EventListener implements Listener
{
	public function onLogin(PlayerLoginEvent $ev)
	{
		$p = $ev->getPlayer();
		$n = $p->getName();
		$xuid = $p->getXuid();
		$helper = StackStorageHelper::getInstance();
		$api = StackStorageAPI::getInstance();

		try {
			if (!$api->isExists($xuid)) {
				$helper->setStorage($xuid, []);
				$helper->setName($xuid, $n);
			}
			$old = $helper->getName($xuid);
			if ($old == !$n) {
				$helper->setName($xuid, $n);
			}
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
			try {
				GuiAPI::getInstance()->getGui($n);
				GuiAPI::getInstance()->closeGui($n);
			} catch (Exception $ex) {
				if ($ex->getCode() === GuiAPI::GUI_NOT_FOUND) return;
				throw $ex;
			}
		} catch (Exception $ex) {
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
					if (!StackStorageAPI::getInstance()->isOpen($n)) {
						$ev->setCancelled();
						$p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
						$p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : access to unauthorized storage');
					}
					if ($act->getSourceItem()->getId() !== Item::AIR) {
						switch ($act->getSlot()) {
							case StackStorage::BACK:
								StackStorageAPI::getInstance()->backPage($n);
								$ev->setCancelled();
								return;

							case StackStorage::NEXT:
								StackStorageAPI::getInstance()->nextPage($n);
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
					if ($act->getTargetItem()->getId() !== Item::AIR) {
						$item = $act->getTargetItem();
						StackStorageAPI::getInstance()->add($xuid, $item);
						StackStorageAPI::getInstance()->refresh($n);
					}
					if ($act->getSourceItem()->getId() !== Item::AIR and $act->getSlot() < 45) {
						$item = $act->getSourceItem();
						if (StackStorageAPI::getInstance()->getItem($xuid, $item)->getCount() < $item->getCount()) {
							$p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
							$p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : could not reduce items');
							$ev->setCancelled();
							return;
						}
						StackStorageAPI::getInstance()->remove($xuid, $item);
						StackStorageAPI::getInstance()->refresh($n);
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