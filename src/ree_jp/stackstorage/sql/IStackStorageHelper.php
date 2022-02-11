<?php


namespace ree_jp\stackstorage\sql;


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
     * @param bool $isInitFunc
     */
    public function __construct(PluginBase $plugin, string $path, bool $isInitFunc);

    /**
     * @param string $xuid
     * @param Closure $func
     * @param Closure|null $failure
     */
    public function getStorage(string $xuid, Closure $func, ?Closure $failure): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @param Closure $func
     * @param Closure|null $failure
     */
    public function getItem(string $xuid, Item $item, Closure $func, ?Closure $failure): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @param Closure|null $func
     * @param Closure|null $failure
     */
    public function addItem(string $xuid, Item $item, ?Closure $func, ?Closure $failure): void;

    /**
     * @param string $xuid
     * @param Item|string $item
     * @param bool $isUpdate
     * @param Closure $func
     * @param Closure $failure
     * @param int $count
     */
    public function setItem(string $xuid, Item|string $item, bool $isUpdate, Closure $func, Closure $failure, int $count = 0): void;

    /**
     * @param Closure $func
     * @param Closure|null $failure
     * @return void
     */
    public function getUser(Closure $func, ?Closure $failure): void;

    public function close(): void;
}
