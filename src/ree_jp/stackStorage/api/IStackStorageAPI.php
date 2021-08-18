<?php


namespace ree_jp\stackStorage\api;


use Exception;
use pocketmine\item\Item;

interface IStackStorageAPI
{
    const NOT_STACK = 1;

    /**
     * @param string $n
     * @return bool
     */
    public function isOpen(string $n): bool;

    /**
     * @param string $xuid
     * @param Item $item
     * @throws Exception
     */
    public function add(string $xuid, Item $item): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @throws Exception
     */
    public function remove(string $xuid, Item $item): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @throws Exception
     */
    public function set(string $xuid, Item $item): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @return Item
     * @throws Exception
     */
    public function getItem(string $xuid, Item $item): Item;

    /**
     * @param string $xuid
     * @param Item $item
     * @return bool
     * @throws Exception
     */
    public function isItemExists(string $xuid, Item $item): bool;

    /**
     * @param string $xuid
     * @return array
     * @throws Exception
     */
    public function getAllItem(string $xuid): array;

    /**
     * @param string $n
     */
    public function sendGui(string $n): void;

    /**
     * @param string $n
     */
    public function refresh(string $n): void;

    /**
     * @param string $n
     */
    public function backPage(string $n): void;

    /**
     * @param string $n
     */
    public function nextPage(string $n): void;
}
