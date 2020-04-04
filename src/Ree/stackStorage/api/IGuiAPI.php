<?php


namespace ree\stackStorage\api;


use Exception;
use pocketmine\Player;
use ree\stackStorage\virtual\VirtualGui;

interface IGuiAPI
{
	const PLAYER_NOT_FOUND = 1;
	const GUI_NOT_FOUND = 2;

	/**
	 * @return IGuiAPI
	 */
	public static function getInstance(): IGuiAPI;

	/**
	 * @param string $n
	 * @return VirtualGui
	 * @throws Exception
	 */
	public function getGui(string $n): VirtualGui;

	/**
	 * @param string $n
	 * @param VirtualGui $gui
	 * @throws Exception
	 */
	public function sendGui(string $n, VirtualGui $gui): void ;

	/**
	 * @param string $n
	 * @throws Exception
	 */
	public function closeGui(string $n): void ;

	/**
	 * @param Player $p
	 * @return VirtualGui
	 */
	public function findWindow(Player $p): ?VirtualGui ;
}