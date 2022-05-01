<?php


namespace ree_jp\stackstorage\api;


use Closure;
use pocketmine\item\Item;
use pocketmine\player\Player;

interface IStackStorageAPI
{
    const NOT_STACK = 1;

    /**
     * @param string $xuid
     * @return bool
     */
    public function isOpen(string $xuid): bool;

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
     * @param Player $p
     * @param string $xuid
     */
    public function sendGui(Player $p, string $xuid): void;

    /**
     * @param Item $item
     * @return Item|null
     */
    public function setStoredNbtTag(Item $item): ?Item;

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

    /**
     * @param string $xuid
     * @return void
     */
    public function solutionProblem(string $xuid): void;

}
