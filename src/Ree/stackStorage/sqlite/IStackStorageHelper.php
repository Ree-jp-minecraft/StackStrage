<?php


namespace ree\stackStorage\sqlite;


use pocketmine\item\Item;

interface IStackStorageHelper
{
	const STORAGE_NOT_FOUND = 1;

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
	 * @return bool
	 */
	public function isExists(string $n): bool ;

	/**
	 * @param string $n
	 * @return array
	 */
	public function getStorage(string $n): array ;

	/**
	 * @param string $n
	 * @param array $items
	 */
	public function setStorage(string $n, array $items): void ;

	/**
	 * @param string $n
	 * @param Item $item
	 * @return int
	 */
	public function getItem(string $n, Item $item): int ;

	/**
	 * @param string $n
	 * @param Item $item
	 */
	public function addItem(string $n, Item $item): void ;

	/**
	 * @param string $n
	 * @param Item $item
	 */
	public function removeItem(string $n, Item $item): void ;
}