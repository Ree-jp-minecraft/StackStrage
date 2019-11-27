<?php


namespace Ree\StackStrage\Virchal;


use pocketmine\block\Block;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Chest;
use Ree\seichi\PlayerTask;
use Ree\StackStrage\ChestGuiManager;
use Ree\StackStrage\ChestTask;
use Ree\StackStrage\main;
use Ree\StackStrage\VirchalDust;

class Dust
{
    /**
     * @var DoubleChestInventory
     */
    private $instance;

    /**
     * @var PlayerTask
     */
    private $pT;

    public function __construct(PlayerTask $pT)
    {
        $this->pT = $pT;
        $p = $pT->getPlayer();
        $n = $p->getName();

        $x = (int)$p->x;
        $y = (int)$p->y + 3;
        $z = (int)$p->z;

        ChestGuiManager::CloseInventory($p, $x, $y, $z);

        $pT->s_gui = [$x, $y, $z];

        $block1 = Block::get(Block::CHEST);
        $block1->setComponents($x, $y, $z);
        $p->level->sendBlocks([$p], [$block1]);

        $block2 = Block::get(Block::CHEST);
        $block2->setComponents($x + 1, $y, $z);
        $p->level->sendBlocks([$p], [$block2]);

        $nbt = Chest::createNBT($block1);
        $nbt->setString("CustomName", "Dust");
        $nbt->setInt("pairx", $x + 1);
        $nbt->setInt("pairz", $z);
        $nbt->setTag(new CompoundTag("s_chest",
            [
                new StringTag("name", $n),
            ]));
        $block1 = Chest::createTile(Chest::CHEST, $p->level, $nbt);

        $nbt = Chest::createNBT($block2);
        $nbt->setString("CustomName", "Dust");
        $nbt->setInt("pairx", $x);
        $nbt->setInt("pairz", $z);
        $nbt->setTag(new CompoundTag("s_chest",
            [
                new StringTag("name", $n),
            ]));
        $block2 = Chest::createTile(Chest::CHEST, $p->level, $nbt);

        $instance = new VirchalDust($block1, $block2);
        $this->instance = $instance;

        main::getMain()->getScheduler()->scheduleDelayedTask(new ChestTask($p, $instance), 13);
    }

    private function setPage(): void
    {
    }

    /**
     * @return DoubleChestInventory
     */
    public function getInstance(): DoubleChestInventory
    {
        return $this->instance;
    }
}