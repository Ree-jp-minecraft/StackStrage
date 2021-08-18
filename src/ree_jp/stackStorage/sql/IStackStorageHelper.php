<?php


namespace ree_jp\stackStorage\sqlite;


use pocketmine\item\Item;

interface IStackStorageHelper
{
    const STORAGE_NOT_FOUND = 1;
    const ITEM_NOT_FOUND = 2;
    const ENCHANT_ID_NOT_FOUND = 3;

    /**
     * IStackStorageHelper constructor.
     * @param string $host
     * @param string $user
     * @param string $pass
     */
    public function __construct(string $host, string $user, string $pass);

    /**
     * @param string $xuid
     * @return bool
     */
    public function isExists(string $xuid): bool;

    /**
     * @param string $xuid
     * @return array
     */
    public function getStorage(string $xuid): array;

    /**
     * @param string $xuid
     * @param array $items
     */
    public function setStorage(string $xuid, array $items): void;

    /**
     * @param string $xuid
     * @param Item $item
     * @return Item
     */
    public function getItem(string $xuid, Item $item): Item;

    /**
     * @param string $xuid
     * @param Item $item
     */
    public function setItem(string $xuid, Item $item): void;
}
