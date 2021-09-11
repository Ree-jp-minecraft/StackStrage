<?php


namespace ree_jp\stackStorage\sql;


use Closure;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;

interface IStackStorageHelper
{
    const STORAGE_NOT_FOUND = 1;
    const ITEM_NOT_FOUND = 2;
    const ENCHANT_ID_NOT_FOUND = 3;

    /**
     * IStackStorageHelper constructor.
     * @param PluginBase $plugin
     * @param string $path
     */
    public function __construct(PluginBase $plugin, string $path);

    /**
     * @param string $xuid
     * @param Closure $func
     * @param Closure $failure
     */
    public function getStorage(string $xuid, Closure $func, Closure $failure): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @param Closure $func
     */
    public function getItem(string $xuid, Item $item, Closure $func): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @param Closure|null $func
     */
    public function setItem(string $xuid, Item $item, ?Closure $func): void;
}
