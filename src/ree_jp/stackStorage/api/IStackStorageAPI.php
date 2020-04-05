<?php


namespace ree_jp\stackStorage\api;


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
	 */
	public function set(string $xuid, Item $item): void ;

	/**
	 * @param string $xuid
	 * @param $item
	 * @return Item
	 */
	public function getItem(string $xuid, $item): Item ;

	/**
	 * @param string $xuid
	 * @param Item $item
	 * @return bool
	 */
	public function isItemExists(string $xuid, Item $item): bool ;

	/**
	 * @param string $xuid
	 * @return array
	 */
	public function getAllItem(string $xuid): array ;

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
	 * @return Item|null
	 */
	public function getItemByName(string $n, Item $item): ?Item ;

	/**
	 * @param string $n
	 * @param Item $item
	 * @return bool
	 */
	public function isItemExistsByName(string $n, Item $item): bool ;

	/**
	 * @param string $n
	 */
	public function sendGui(string $n): void;

	/**
	 * @param string $n
	 */
	public function refresh(string $n): void ;

	/**
	 * @param string $n
	 */
	public function backPage(string $n): void ;

	/**
	 * @param string $n
	 */
	public function nextPage(string $n): void ;
}