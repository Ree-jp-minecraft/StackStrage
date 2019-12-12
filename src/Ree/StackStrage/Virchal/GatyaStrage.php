<?php

namespace Ree\StackStrage\Virchal;


use pocketmine\block\Block;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Chest;

use Ree\seichi\PlayerTask;
use Ree\StackStrage\ChestGuiManager;
use Ree\StackStrage\ChestTask;
use Ree\StackStrage\GatyaStrage_API;
use Ree\StackStrage\main;
use Ree\StackStrage\StackStrage_API;
use Ree\StackStrage\VirchalInterface\VirchaGatyaStrage;


class GatyaStrage
{
    /**
     * @var DoubleChestInventory
     */
    private $instance;

    /**
     * @var int
     */
    private $page = 1;

    /**
     * @var PlayerTask
     */
    private $pT;

    /**
     * @var Item[]
     */
    private $items;

    public function __construct(PlayerTask $pT ,bool $bool = true)
    {
        $this->pT = $pT;
        $p = $pT->getPlayer();
        $n = $p->getName();

        $x = (int)$p->x;
        $y = (int)$p->y + 3;
        $z = (int)$p->z;

        if ($bool) {
            ChestGuiManager::CloseInventory($p, $x, $y, $z);
        }

        $pT->s_gui = [$x, $y, $z];

        $block1 = Block::get(Block::CHEST);
        $block1->setComponents($x, $y, $z);
        $p->level->sendBlocks([$p], [$block1]);

        $block2 = Block::get(Block::CHEST);
        $block2->setComponents($x + 1, $y, $z);
        $p->level->sendBlocks([$p], [$block2]);

        $nbt = Chest::createNBT($block1);
        $nbt->setString("CustomName", "GatyaStrage");
        $nbt->setInt("pairx", $x + 1);
        $nbt->setInt("pairz", $z);
        $nbt->setTag(new CompoundTag("s_chest",
            [
                new StringTag("name", $n),
            ]));
        $block1 = Chest::createTile(Chest::CHEST, $p->level, $nbt);

        $nbt = Chest::createNBT($block2);
        $nbt->setString("CustomName", "GatyaStrage");
        $nbt->setInt("pairx", $x);
        $nbt->setInt("pairz", $z);
        $nbt->setTag(new CompoundTag("s_chest",
            [
                new StringTag("name", $n),
            ]));
        $block2 = Chest::createTile(Chest::CHEST, $p->level, $nbt);

        $instance = new VirchaGatyaStrage($block1, $block2);
        $this->instance = $instance;
        $this->setPage();

        if ($bool)
        {
            $tick = 13;
        }else{
            $tick = 3;
        }
        main::getMain()->getScheduler()->scheduleDelayedTask(new ChestTask($p, $instance), $tick);
    }

    /**
     * @return DoubleChestInventory
     */
    public function getInstance(): DoubleChestInventory
    {
        return $this->instance;
    }

    /**
     * @return bool
     */
    public function setPage(): bool
    {
        $instance = $this->getInstance();
        $p = $this->pT->getPlayer();
        $items = main::getGatyaStrage($p);
        $array = array_keys($items);
        for ($i = 0 ;$i <= 53 ;$i++)
        {
            $item = Item::get(Item::AIR, 0, 1);
            $instance->setItem($i ,$item);
        }

        $temp = 0;
        foreach ($array as $data) {
            $item = GatyaStrage_API::getItem($p ,$data);
            $instance->getInventory()->setItem($temp, $item);
            $this->items[$temp] = $item;
            $temp++;
        }

        $item = Item::get(Item::BOOK, 0, 1);
        $item->setCustomName($this->page." ページ\n\n§7閉じる");
        $instance->getInventory()->setItem(49, $item);
        return true;
    }

    /**
     * @return bool
     */
    public function nextpage(): bool
    {
        $this->page++;
        $bool = $this->setPage();
        if (!$bool)
        {
            $this->page--;
        }
        return $bool;
    }

    /**
     * @return bool
     */
    public function backpage(): bool
    {
        $this->page--;
        $bool = $this->setPage();
        if (!$bool) {
            $this->page++;
        }
        return $bool;
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}