<?php


namespace ree_jp\stackStorage\api;


use Exception;
use pocketmine\item\Item;
use pocketmine\Player;

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
     * @param Player $p
     * @param string $xuid
     */
    public function sendGui(Player $p, string $xuid): void;

    /**
     * @param Item $item
     * @return Item
     */
    public function setStoredNbtTag(Item $item): Item;

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

    /**
     * @param string $xuid
     * @param Item $item
     * @return Item|null
     */
    public function getItem(string $xuid, Item $item): ?Item;

    /**
     * @param string $xuid
     */
    public function closeCache(string $xuid): void;

}
