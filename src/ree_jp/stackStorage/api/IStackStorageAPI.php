<?php


namespace ree_jp\stackStorage\api;


use Closure;
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
     * @param string $xuid
     */
    public function refresh(string $xuid): void;

    /**
     * @param string $xuid
     */
    public function backPage(string $xuid): void;

    /**
     * @param string $xuid
     */
    public function nextPage(string $xuid): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @param Closure $func
     * @param Closure|null $failure
     */
    public function getCount(string $xuid, Item $item, Closure $func, ?Closure $failure): void;

    /**
     * @param string $xuid
     * @param Closure $func
     * @param Closure|null $failure
     */
    public function getAllItems(string $xuid, Closure $func, ?Closure $failure): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @return bool
     */
    public function hasCountFromCache(string $xuid, Item $item): bool;

    /**
     * @param string $xuid
     */
    public function closeCache(string $xuid): void;

}
