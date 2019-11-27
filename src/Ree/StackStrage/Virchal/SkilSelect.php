<?php


namespace Ree\StackStrage\Virchal;


use pocketmine\block\Block;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\tile\Chest;
use Ree\seichi\PlayerTask;
use Ree\seichi\skil\Skil;
use Ree\StackStrage\ChestGuiManager;
use Ree\StackStrage\ChestTask;
use Ree\StackStrage\main;
use Ree\StackStrage\VirchalSkilSelect;

class SkilSelect
{
    /**
     * @var DoubleChestInventory
     */
    private $instance;

    /**
     * @var PlayerTask
     */
    private $pT;

    /**
     * @var Skil[]
     */
    private $skil;

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
        $nbt->setString("CustomName", "SkilSelect");
        $nbt->setInt("pairx", $x + 1);
        $nbt->setInt("pairz", $z);
        $nbt->setTag(new CompoundTag("s_chest",
            [
                new StringTag("name", $n),
            ]));
        $block1 = Chest::createTile(Chest::CHEST, $p->level, $nbt);

        $nbt = Chest::createNBT($block2);
        $nbt->setString("CustomName", "SkilSelect");
        $nbt->setInt("pairx", $x);
        $nbt->setInt("pairz", $z);
        $nbt->setTag(new CompoundTag("s_chest",
            [
                new StringTag("name", $n),
            ]));
        $block2 = Chest::createTile(Chest::CHEST, $p->level, $nbt);

        $instance = new VirchalSkilSelect($block1, $block2);
        $this->instance = $instance;
        $this->setPage();

        main::getMain()->getScheduler()->scheduleDelayedTask(new ChestTask($p, $instance), 13);
    }

    private function setPage(): void
    {
        for ($i = 0; $i <= 53; $i++) {
            $item = Item::get(106, 0, 1);
            $item->setCustomName("§0");
            $this->instance->getInventory()->setItem($i, $item);
        }
        foreach ($this->pT->s_skil as $skilname) {
            $skil = 'Ree\seichi\skil\\' . $skilname;
            $slot = $skil::getSlot();
            $item = $skil::getIcon();
            $this->skil[$slot] = $skilname;
            $item->setCustomName($skil::getName()."\n\n§9消費マナ : ".$skil::getMana());
            $this->instance->getInventory()->setItem($slot, $item);
            $this->skil[$slot] = $skilname;
        }
        $item = Item::get(Item::SAPLING, 0, 1);
        $item->setCustomName("§aスキルツリー");
        $this->instance->getInventory()->setItem(53, $item);
    }

    /**
     * @return DoubleChestInventory
     */
    public function getInstance(): DoubleChestInventory
    {
        return $this->instance;
    }

    /**
     * @return array
     */
    public function getSkil(): array
    {
        return $this->skil;
    }
}