<?php


namespace ree\stackStorage\sqlite;


use pocketmine\item\Item;

interface IStackStorageHelper
{
	const STORAGE_NOT_FOUND = 1;
	const ITEM_NOT_FOUND = 2;
	const ENCHANT_ID_NOT_FOUND = 3;

	/**
	 * IStackStorageHelper constructor.
	 * @param string $path
	 */
	public function __construct(string $path);

	/**
	 * @return IStackStorageHelper
	 */
	public static function getInstance(): IStackStorageHelper ;

	/**
	 * @param string $n
	 * @return string|null
	 */
	public function getXuid(string $n): ?string ;

	/**
	 * @param string $xuid
	 * @return string|null
	 */
	public function getName(string $xuid): ?string ;

	/**
	 * @param string $xuid
	 * @param string $name
	 */
	public function setName(string $xuid, string $name): void ;

	/**
	 * @param string $xuid
	 * @return bool
	 */
	public function isExists(string $xuid): bool ;

	/**
	 * @param string $xuid
	 * @return array
	 */
	public function getStorage(string $xuid): array ;

	/**
	 * @param string $xuid
	 * @param array $items
	 */
	public function setStorage(string $xuid, array $items): void ;

	/**
	 * @param string $xuid
	 * @param Item $item
	 * @return Item
	 */
	public function getItem(string $xuid, Item $item): Item ;

	/**
	 * @param string $xuid
	 * @param Item $item
	 */
	public function setItem(string $xuid, Item $item): void ;

	/**
	 * @param Item $item
	 * @param string $enchant
	 * @return Item
	 */
	public function enchantEncode(Item $item, string $enchant) : Item ;

	/**
	 * @param Item $item
	 * @return string
	 */
	public function enchantDecode(Item $item) : ?string ;
}