<?php


namespace Ree\StackStrage\Virchal;


use pocketmine\block\Block;
use pocketmine\inventory\ChestInventory;
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
use Ree\StackStrage\VirchalWorldSelect;

class WorldSelect
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

    public function __construct(PlayerTask $pT ,bool $bool = true)
    {
        $this->pT = $pT;
        $p = $pT->getPlayer();
        $n = $p->getName();

        $x = (int)$p->x;
        $y = (int)$p->y + 3;
        $z = (int)$p->z;

        if ($bool)
        {
            ChestGuiManager::CloseInventory($p, $x, $y, $z);
        }

        $pT->s_gui = [$x, $y, $z];

        $block = Block::get(Block::CHEST);
        $block->setComponents($x, $y, $z);
        $p->level->sendBlocks([$p], [$block]);

        $nbt = Chest::createNBT($block);
        $nbt->setString("CustomName", "WorldSelect");
        $nbt->setInt("pairx", $x + 1);
        $nbt->setInt("pairz", $z);
        $nbt->setTag(new CompoundTag("s_chest",
            [
                new StringTag("name", $n),
            ]));
        $block = Chest::createTile(Chest::CHEST, $p->level, $nbt);

        $instance = new VirchalWorldSelect($block);
        $this->instance = $instance;

        $this->setPage();
        main::getMain()->getScheduler()->scheduleDelayedTask(new ChestTask($p, $instance), 13);
    }

    private function setPage(): void
    {
        for ($i = 0; $i <= 26; $i++) {
            $item = Item::get(Item::VINE, 0, 1);
            $item->setCustomName("§0");
            $this->instance->setItem($i, $item);
        }
        $item = Item::get(Item::BED, 0, 1);
        $item->setCustomName("ロビー");
        $this->instance->setItem(10, $item);
        $item = Item::get(Item::DIAMOND_PICKAXE, 0, 1);
        $item->setCustomName("整地ワールド");
        $this->instance->setItem(11, $item);
        $item = Item::get(Item::DIAMOND, 0, 1);
        $item->setCustomName("公共施設");
        $this->instance->setItem(16, $item);
    }

    /**
     * @return DoubleChestInventory
     */
    public function getInstance(): ChestInventory
    {
        return $this->instance;
    }
}