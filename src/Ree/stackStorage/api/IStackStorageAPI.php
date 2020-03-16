<?php


namespace ree\stackStorage\api;


use pocketmine\item\Item;

interface IStackStorageAPI
{
	const NOT_STACK = 1;

	/**
	 * @return IStackStorageAPI
	 */
	public static function getInstance(): IStackStorageAPI;

	/**
	 * @param string $n
	 * @return bool
	 */
	public function isOpen(string $n): bool ;

	/**
	 * @param string $n
	 * @return string|null
	 */
	public function getXuid(string $n): ?string ;

	/**
	 * @param string $xuid
	 * @param Item $item
	 */
	public function add(string $xuid, Item $item): void ;

	/**
	 * @param string $xuid
	 * @param Item $item
	 */
	public function remove(string $xuid,Item $item): void ;

	/**
	 * @param string $xuid
	 * @param Item $item
	 * @return bool
	 */
	public function isExists(string $xuid, Item $item): bool ;

	/**
	 * @param string $n
	 * @param Item $item
	 * @return bool
	 */
	public function addByName(string $n, Item $item): bool ;

	/**
	 * @param string $n
	 * @param Item $item
	 * @return bool
	 */
	public function removeByName(string $n,Item $item): bool ;

	/**
	 * @param string $n
	 * @param Item $item
	 * @return bool
	 */
	public function isExistsByName(string $n, Item $item): bool ;

	/**
	 * @param string $name
	 */
	public function sendGui(string $name): void ;
}