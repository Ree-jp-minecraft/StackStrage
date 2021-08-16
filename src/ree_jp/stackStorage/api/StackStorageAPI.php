<?php


namespace ree_jp\stackStorage\api;


use Exception;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\sqlite\StackStorageHelper;
use ree_jp\stackStorage\gui\StackStorage;
use ree_jp\stackStorage\virtual\VirtualStackStorage;

class StackStorageAPI implements IStackStorageAPI
{
	static StackStorageAPI $instance;

	/**
	 * @var StackStorage[]
	 */
	private array $storage;
	
	/**
	 * @inheritDoc
	 */
	public function isOpen(string $n): bool
	{
		try {
			$gui = GuiAPI::getInstance()->getGui($n);
			if (!$gui instanceof VirtualStackStorage) return false;
		} catch (Exception $ex) {
			if ($ex->getCode() === IGuiAPI::PLAYER_NOT_FOUND | IGuiAPI::GUI_NOT_FOUND) return false;
		}
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function sendGui(string $n): void
	{
		$p = Server::getInstance()->getPlayer($n);
		if (!$p instanceof Player) return;
		try {
			if ($this->isOpen($n)) GuiAPI::getInstance()->closeGui($n);

			$storage = new StackStorage($p);
			$storage->sendGui();
			$storage->refresh();
			$this->storage[$n] = $storage;
		} catch (Exception $ex) {
			Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
			Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage(). $ex->getFile(). $ex->getLine());
			return;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function add(string $xuid, Item $item): void
	{
		$item->setCount($item->getCount() + $this->getItem($xuid, $item)->getCount());
		StackStorageHelper::$instance->setItem($xuid, $item);
	}

	/**
	 * @inheritDoc
	 */
	public function remove(string $xuid, Item $item): void
	{
		$count = $this->getItem($xuid, $item)->getCount() - $item->getCount();
		if ($count >= 0) {
			StackStorageHelper::$instance->setItem($xuid, $item->setCount($count));
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getXuid(string $n): ?string
	{
		return StackStorageHelper::$instance->getXuid($n);
	}

	/**
	 * @inheritDoc
	 */
	public function isExists(string $xuid): bool
	{
		return StackStorageHelper::$instance->isExists($xuid);
	}

	/**
	 * @inheritDoc
	 */
	public function addByName(string $n, Item $item): bool
	{
		$xuid = $this->getXuid($n);
		if (!$xuid) return false;

		$this->add($xuid, $item);
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function removeByName(string $n, Item $item): bool
	{
		$xuid = $this->getXuid($n);
		if (!$xuid) return false;

		$this->remove($xuid, $item);
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function isExistsByName(string $n): bool
	{
		$xuid = $this->getXuid($n);
		if (!$xuid) return false;

		return $this->isExists($xuid);
	}

	/**
	 * @inheritDoc
	 */
	public function set(string $xuid, Item $item): void
	{
		StackStorageHelper::$instance->getItem($xuid, $item);
	}

	/**
	 * @inheritDoc
	 */
	public function getItem(string $xuid, $item): Item
	{
		return StackStorageHelper::$instance->getItem($xuid, $item);
	}

	/**
	 * @inheritDoc
	 */
	public function isItemExists(string $xuid, Item $item): bool
	{
		$count = StackStorageHelper::$instance->getItem($xuid, $item)->getCount();
		if ($count <= 0) return false;
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getAllItem(string $xuid): array
	{
		return StackStorageHelper::$instance->getStorage($xuid);
	}

	/**
	 * @inheritDoc
	 */
	public function getItemByName(string $n, Item $item): ?Item
	{
		$xuid = $this->getXuid($n);
		if (!$xuid) return null;

		return $this->getItem($xuid, $item);
	}

	/**
	 * @inheritDoc
	 */
	public function isItemExistsByName(string $n, Item $item): bool
	{
		$xuid = $this->getXuid($n);
		if (!$xuid) return false;

		return $this->isItemExists($xuid, $item);
	}

	public function refresh(string $n): void
	{
		$storage = $this->getStorage($n);
		if ($storage instanceof StackStorage) {
			$storage->refresh();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function backPage(string $n): void
	{
		$storage = $this->getStorage($n);
		if ($storage instanceof StackStorage) {
			$storage->backPage();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function nextPage(string $n): void
	{
		$storage = $this->getStorage($n);
		if ($storage instanceof StackStorage) {
			$storage->nextPage();
		}
	}

	/**
	 * @param string $n
	 * @return StackStorage|null
	 */
	private function getStorage(string $n): ?StackStorage
	{
		if (isset($this->storage[$n])) return $this->storage[$n];

		return null;
	}
}
