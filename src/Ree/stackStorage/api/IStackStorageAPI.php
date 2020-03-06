<?php


namespace ree\stackStorage\api;


use pocketmine\item\Item;

interface IStackStorageAPI
{
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
	 * @param Item $item
	 */
	public function add(string $n, Item $item): void ;

	/**
	 * @param string $n
	 * @param Item $item
	 */
	public function remove(string $n,Item $item): void ;

	/**
	 * @param string $name
	 */
	public function sendGui(string $name): void ;
}