<?php


namespace ree_jp\stackStorage\api;


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
     * @return bool
     */
    public function isExists(string $xuid): bool;

    /**
     * @param string $xuid
     * @param Item $item
     */
    public function add(string $xuid, Item $item): void;

    /**
     * @param string $xuid
     * @param Item $item
     */
    public function remove(string $xuid, Item $item): void;

    /**
     * @param string $xuid
     * @param Item $item
     */
    public function set(string $xuid, Item $item): void;

    /**
     * @param string $xuid
     * @param $item
     * @return Item
     */
    public function getItem(string $xuid, $item): Item;

    /**
     * @param string $xuid
     * @param Item $item
     * @return bool
     */
    public function isItemExists(string $xuid, Item $item): bool;

    /**
     * @param string $xuid
     * @return array
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
