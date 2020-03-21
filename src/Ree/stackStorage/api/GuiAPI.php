<?php


namespace ree\stackStorage\api;


use pocketmine\Player;
use pocketmine\Server;

use ree\stackStorage\virtual\VirtualGui;

class GuiAPI implements IGuiAPI
{
	/**
	 * @var GuiAPI
	 */
	private static $instance;

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

		$window = $p->findWindow(VirtualGui::class);
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

		try {
			$p->addWindow($gui);
		}catch (\InvalidArgumentException | \InvalidStateException $ex) {
			throw $ex;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function closeGui(string $n): void
	{
		$p = Server::getInstance()->getPlayer($n);
		if (!$p instanceof Player) throw new \Exception('player not found', self::PLAYER_NOT_FOUND);

		$window = $p->findWindow(VirtualGui::class);
		if (!$window instanceof VirtualGui) throw new \Exception('gui not found', self::GUI_NOT_FOUND);

		$window->getInventory()->clearAll();
		$window->close($p);
//		$p->doCloseInventory();
	}
}