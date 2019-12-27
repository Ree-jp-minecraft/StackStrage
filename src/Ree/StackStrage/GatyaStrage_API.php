<?php


namespace Ree\StackStrage;

use pocketmine\item\Item;
use pocketmine\Player;
use Ree\seichi\Gatya;

class GatyaStrage_API
{
    const NOTSTACK = "s_notstack";
    const PLAYERNAME = "playername";
    const STRAGE = "s_strage";

    public static function getItem(Player $p ,int $data)
    {
        $item = Gatya::getGatya($data ,$p);
        $count = self::getCount($p ,$data);
        if ($item->getMaxStackSize() < $count)
        {
            $item->setCount($item->getMaxStackSize());
        }else{
            $item->setCount($count);
            if ($data === 7)
			{
				if ($count > 0)
				{
					$item->setCount(1);
				}
			}
        }
        $item->setCustomName($item->getCustomName()."\n\n個数 : ".$count);
        $nbt = $item->getNamedTag();
        $nbt->setInt(self::STRAGE ,1);
        $item->setNamedTag($nbt);
        return $item;
    }

    /**
     * @param Player $p
     * @param Item $item
     * @return bool
     */
    public static function add(Player $p ,Item $item): bool
    {
        $n = $p->getName();

        $nbt = $item->getNamedTag();
        if (!$nbt->offsetExists(Gatya::GATYA))
        {
            return false;
        }
        if ($nbt->getInt(Gatya::GATYA) <= 1000)
        {
            if (!$nbt->offsetExists(StackStrage_API::PLAYERNAME))
            {
                return false;
            }
            if ($nbt->getString(StackStrage_API::PLAYERNAME) != $p->getName())
            {
                if ($nbt->getString(StackStrage_API::PLAYERNAME) !== "true")
                {
                    return false;
                }
            }
        }
        $data = $nbt->getInt(Gatya::GATYA);
        $count = self::getCount($p ,$data);

        if ($count) {
            $count = $count + $item->getCount();
        } else {
            $count = $item->getCount();
        }
        self::setData($p ,$data ,$count);
        return true;
    }


    /**
     * @param Player $p
     * @param Item|array $item
     * @return bool
     */
    public static function remove(Player $p ,Item $item): bool
    {
        $n = $p->getName();

        $nbt = $item->getNamedTag();
        if (!$nbt->offsetExists(Gatya::GATYA))
        {
            return false;
        }
        $data = $nbt->getInt(Gatya::GATYA);
        $count = self::getCount($p ,$data);

        if ($count >= $item->getCount()) {
            $count = $count - $item->getCount();
            self::setData($p ,$data ,$count);
        } else {
            return false;
        }
        return true;
    }

    /**
     * @param Player $p
     * @param int $data
     * @return int
     */
    private static function getCount(Player $p ,int $data)
    {
        $strage = main::getGatyaStrage($p);
        if (!isset($strage[$data]))
        {
            return 0;
        }
        $count = $strage[$data];
        return $count;
    }

    /**
     * @param Player $p
     * @param int $data
     * @param int $count
     */
    private static function setData(Player $p, int $data ,int $count)
    {
        $strage = main::getGatyaStrage($p);
        $strage[$data] = $count;
        main::setGatyaStrage($p ,$strage);
        return;
    }
}