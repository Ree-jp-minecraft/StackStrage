<?php


namespace Ree\StackStrage;

use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use Ree\seichi\Task\ClearTask;

class StackStrage_API
{
    const NOTSTACK = "s_notstack";
    const PLAYERNAME = "playername";
    const STRAGE = "s_strage";

    /**
     * @param array $itemdata
     * @return Item
     */
    public static function returnItem(array $itemdata)
    {
        if ($itemdata["count"] <= 64)
        {
            $item = Item::get($itemdata["id"] ,$itemdata["meta"] ,$itemdata["count"]);
        }else{
            $item = Item::get($itemdata["id"] ,$itemdata["meta"] ,64);
        }
        foreach ($itemdata["enchant"] as $ench)
        {
            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($ench["id"]) ,$ench["level"]));
        }

        return $item;
    }

    /**
     * @param Item|array $itemdata
     * @return Item
     */
    public static function getItem(array $itemdata)
    {
        if ($itemdata instanceof Item)
        {
            $itemdata = self::create($itemdata);
        }
        if ($itemdata["count"] > 64)
        {
            $count = 64;
        }else{
            $count = $itemdata["count"];
        }
        $item = Item::get($itemdata["id"] ,$itemdata["meta"] ,$count);
        $enchant = NULL;
        foreach ($itemdata["enchant"] as $ench)
        {
            $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($ench["id"]) ,$ench["level"]));
            $enchant[] = Enchantment::getEnchantment($ench["id"]);
        }
        if (!$enchant)
        {
            $item->setCustomName($item->getName()."\nID : ".$itemdata["id"] ."\nMeta : " .$itemdata["meta"] ."\nCount : " .$itemdata["count"]);
        }else{
            $item->setCustomName($item->getName()."\nID : ".$itemdata["id"] ."\nMeta : " .$itemdata["meta"] ."\nCount : " .$itemdata["count"] ."\nEnchant : " .$enchant);
        }
        $nbt = $item->getNamedTag();
        $nbt->setInt(self::STRAGE ,1);
        $item->setNamedTag($nbt);

        return $item;
    }


    /**
     * @param array $item
     * @param array $strage
     * @return int
     */
    public static function getCcount(array $item, array $strage)
    {
        if ($item instanceof Item) {
            $item = self::create($item);
        }
        if (!isset($strage[$item["id"]])) {
            return 0;
        }
        $strageitems = $strage[$item["id"]];
        foreach ($strageitems as $strageitem) {
            if ($item["meta"] == $strageitem["meta"]) {
                if ($item["enchant"] == $strageitem["enchant"]) {
                    return $strageitem["count"];
                }
            }
        }
        return 0;
    }


    /**
     * @param Player $p
     * @param Item|array $item
     * @return bool
     */
    public static function add(Player $p ,$item): bool
    {
        $n = $p->getName();

        if ($item instanceof Item) {
            if ($item instanceof Durable)
            {
                return false;
            }
            $tag = $item->getNamedTag();
            if ($tag->offsetExists(self::NOTSTACK))
            {
                return false;
            }

            $item = self::create($item);
        }
        if (!$item) {
            return false;
        }
        if ($item["id"] === 0)
        {
            return false;
        }

        $strage = self::getData($p);
        $count = self::getCcount($item, $strage);
        if ($count) {
            $strageitems = $strage[$item["id"]];
            $temp = 0;
            foreach ($strageitems as $strageitem) {
                if ($item["meta"] == $strageitem["meta"]) {
                    if ($item["enchant"] == $strageitem["enchant"]) {
                        $strageitem["count"] = $strageitem["count"] + $item["count"];
                        $strageitems[$temp] = $strageitem;
                        $strage[$item["id"]] = $strageitems;
                        self::setData($p, $strage);

                        return true;
                    }
                }
                $temp++;
            }
            return false;
        } else {
            if (!isset($strage[$item["id"]])) {
                $strage[$item["id"]] = [];
            }
            $strageitems = $strage[$item["id"]];
            $strageitems[] = $item;
            $strage[$item["id"]] = $strageitems;
            self::setData($p, $strage);

            return true;
        }
    }


    /**
     * @param Player $p
     * @param Item|array $item
     * @return bool
     */
    public static function remove(Player $p ,$item): bool
    {
        $n = $p->getName();

        if ($item instanceof Item) {
            $item = self::create($item);
        }
        if (!$item) {
            return false;
        }

        $strage = self::getData($p);
        $count = self::getCcount($item, $strage);
        if ($count) {
            $strageitems = $strage[$item["id"]];
            foreach ($strageitems as $strageitem) {
                $temp = 0;
                if ($item["meta"] == $strageitem["meta"]) {
                    if ($item["enchant"] == $strageitem["enchant"]) {
                        if ($strageitem["count"] < $item["count"]) {
                            return false;
                        }
                        $strageitem["count"] = $strageitem["count"] - $item["count"];
                        $strageitems[$temp] = $strageitem;
                        if (!$strageitem["count"]) {
                            unset($strageitems[$temp]);
                        }
                        $strage[$item["id"]] = $strageitems;
                        self::setData($p, $strage);

                        return true;
                    }
                }
                $temp++;
            }
            return false;
        } else {
            return false;
        }
    }

    /**
     * @param Player $p
     * @return array
     */
    public static function getItems(Player $p)
    {
        $data = [];
        $data[1] = [];
        $strage = self::getData($p);

        foreach ($strage as $ids)
        {
            foreach ($ids as $item)
            {
                $page = count($data);
                $count = count($data[$page]);
                if ($count >= 45)
                {
                    $data[] = [];
                    $page = count($data);
                    $count = count($data[$page]);
                }
                $pageitems = $data[$page];
                $pageitems[$count] =$item;
                $data[$page] = $pageitems;
            }
        }

        return $data;
    }

    /**
     * @param Player $p
     * @return array
     */
    private static function getData(Player $p): array
    {
        $n = $p->getName();
        $pT = \Ree\seichi\main::getpT($n);

        return $pT->s_strage;
    }

    /**
     * @param Player $p
     * @param array $strage
     */
    private static function setData(Player $p, array $strage)
    {
        $n = $p->getName();
        $pT = \Ree\seichi\main::getpT($n);

        $pT->s_strage = $strage;

        return;
    }

    /**
     * @param Item $item
     * @return array
     */
    private static function create(Item $item): array
    {
        $itemdata["id"] = $item->getId();
        $itemdata["meta"] = $item->getDamage();
        $itemdata["count"] = $item->getCount();
        $itemdata["enchant"] = [];

        if ($item->hasEnchantments()) {
            $enchant = [];
            foreach ($item->getEnchantments() as $tempench) {
                $ench["id"] = $tempench->getId();
                $ench["level"] = $tempench->getLevel();
                $enchant[] = $ench;
            }
            $itemdata["enchant"] = $enchant;
        }

        return $itemdata;

    }
}