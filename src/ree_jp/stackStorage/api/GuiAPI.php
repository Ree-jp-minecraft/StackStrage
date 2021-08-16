<?php


namespace ree_jp\stackStorage\api;


use pocketmine\Player;
use pocketmine\Server;

use ree_jp\stackStorage\virtual\VirtualGui;

class GuiAPI implements IGuiAPI
{
	/**
	 * @var GuiAPI
	 */
	private static GuiAPI $instance;

	/**
	 * @var int[]
	 */
	private array $ids;

	/**
	 * @inheritDoc
	 */
	public static function getInstance(): IGuiAPI
	{
		if (!self::$instance instanceof GuiAPI) {
			self::$instance = new GuiAPI();
		}
		return self::$instance;
	}

	/**
	 * @inheritDoc
	 */
	public function getGui(string $n): VirtualGui
	{
		$p = Server::getInstance()->getPlayer($n);
		if (!$p instanceof Player) throw new \Exception('player not found', self::PLAYER_NOT_FOUND);

		$window = $this->findWindow($p);
		if (!$window instanceof VirtualGui) throw new \Exception('gui not found', self::GUI_NOT_FOUND);

		return $window;
	}

	/**
	 * @inheritDoc
	 */
	public function sendGui(string $n, VirtualGui $gui): void
	{
		$p = Server::getInstance()->getPlayer($n);
		if (!$p instanceof Player) throw new \Exception('player not found', self::PLAYER_NOT_FOUND);

        $this->ids[$n] = $p->addWindow($gui);
    }

	/**
	 * @inheritDoc
	 */
	public function closeGui(string $n): void
	{
		$p = Server::getInstance()->getPlayer($n);
		if (!$p instanceof Player) throw new \Exception('player not found', self::PLAYER_NOT_FOUND);

		$window = $this->findWindow($p);
		if (!$window instanceof VirtualGui) throw new \Exception('gui not found', self::GUI_NOT_FOUND);

		$window->getInventory()->clearAll();
		$window->close($p);
		$p->getLevel()->sendBlocks([$p], [$p->getLevel()->getBlock($p->asVector3()->up(2)), $p->getLevel()->getBlock($p->asVector3()->up(2)->west())]);
//		$p->doCloseInventory();
	}

	/**
	 * @inheritDoc
	 */
	public function findWindow(Player $p): ?VirtualGui
	{
		$n = $p->getName();

		if (isset($this->ids[$n])) {
			$window = $p->getWindow($this->ids[$n]);
			if ($window instanceof VirtualGui) return $window;
		}
		return null;
	}
}
