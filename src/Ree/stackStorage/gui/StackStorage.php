<?php

namespace ree\stackStorage\gui;


use Exception;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\tile\Chest;

use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use ree\stackStorage\api\GuiAPI;
use ree\stackStorage\api\StackStorageAPI;
use ree\stackStorage\stackStoragePlugin;
use ree\stackStorage\virtual\VirtualStackStorage;


class StackStorage
{
	const BACK = 45;
	const NEXT = 53;
	const CLOSE = 49;
	const SYSTEM_ITEM = 1;

	private const TITLE = 'StackStorage';

	/**
	 * @var Player
	 */
	private $p;

	/**
	 * @var VirtualStackStorage
	 */
	private $gui;

	/**
	 * @var int
	 */
	private $page = 1;

	public function __construct(Player $p)
	{
		$this->p = $p;
	}

	public function sendGui()
	{
		try {
			$p = $this->p;
			$v = $this->p->up(2);
			$gui = $this->createGui(self::TITLE . StackStoragePlugin::getVersion(), $v, $this->p->getLevel());
			$p->getLevel()->sendBlocks([$p], [Block::get(Block::CHEST)->setComponents($v->getFloorX(), $v->getFloorY(), $v->getFloorZ())]);
			$p->getLevel()->sendBlocks([$p], [Block::get(Block::CHEST)->setComponents($v->west()->getFloorX(), $v->getFloorY(), $v->getFloorZ())]);
			$this->gui = $gui;
			StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask(
				new ClosureTask(
					function (int $tick) use ($gui): void {
						GuiAPI::getInstance()->sendGui($this->p->getName(), $gui);
					}
				), 3);
		} catch (Exception $ex) {
			$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
			$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage());
		}
	}

	public function refresh()
	{
		$gui = $this->gui;

		$gui->clearAll();

		$array = StackStorageAPI::getInstance()->getAllItem($this->p->getXuid());
		$chunk = array_chunk($array, 45);
		$count = 0;

		if (isset($chunk[$this->page - 1])) {
			foreach ($chunk[$this->page - 1] as $item) {
				if (!$item instanceof Item) {
					$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
					$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : storage data is corrupted');
					try {
						GuiAPI::getInstance()->closeGui($this->p->getName());
					} catch (Exception $ex) {
						$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
						$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
					}
					return;
				}

				if ($item->getMaxStackSize() < $item->getCount()) {
					$item->setLore(['Count', $item->getCount()]);
					$item->setCount($item->getMaxStackSize());
				}
				$this->gui->setItem($count, $item);
				$count++;
			}
		} else {
			if ($this->page !== 1) {
				$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
				$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : access to unauthorized storage');
				StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask(
					new ClosureTask(
						function (int $tick): void {
							try {
								GuiAPI::getInstance()->closeGui($this->p->getName());
							} catch (Exception $ex) {
								$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
								$this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
							}
						}
					), 5);
			}
		}

		if (isset($chunk[$this->page])) {
			$gui->setItem(self::NEXT, Item::get(Item::ARROW)->setCustomName('NextPage'));
		}
		if (isset($chunk[$this->page - 2])) {
			$gui->setItem(self::BACK, Item::get(Item::ARROW)->setCustomName('BackPage'));
		}
		$gui->setItem(self::CLOSE, Item::get(Item::BOOK)->setCustomName('ClosePage'));
	}

	public function backPage()
	{
		$this->page -= 1;
		$this->refresh();
	}

	public function nextPage()
	{
		$this->page += 1;
		$this->refresh();
	}

	/**
	 * @param string $title
	 * @param Vector3 $v
	 * @param Level $level
	 * @return VirtualStackStorage
	 * @throws Exception
	 */
	private function createGui(string $title, Vector3 $v, Level $level): VirtualStackStorage
	{
		$bl = Chest::createTile(Tile::CHEST, $level, Chest::createNBT($v));
		$bl_2 = Chest::createTile(Tile::CHEST, $level, Chest::createNBT($v->west()));
		if ($bl instanceof Chest and $bl_2 instanceof Chest) {
			$bl->setName($title);
			$bl_2->setName($title);
			$bl->pairWith($bl_2);
			return new VirtualStackStorage($bl, $bl_2);
		} else {
			throw new Exception('could not open block');
		}
	}
}