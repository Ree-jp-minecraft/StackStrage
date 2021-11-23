<?php

namespace ree_jp\stackStorage\gui;


use Exception;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\GuiAPI;
use ree_jp\stackStorage\api\IGuiAPI;
use ree_jp\stackStorage\api\StackStorageAPI;
use ree_jp\stackStorage\stackStoragePlugin;
use ree_jp\stackStorage\virtual\VirtualStackStorage;


class StackStorage
{
    const BACK = 45;
    const NEXT = 53;
    const CLOSE = 49;
    const SYSTEM_ITEM = 1;

    public array $storage;

    private const TITLE = 'StackStorage';
    private Player $p;
    private VirtualStackStorage $gui;
    private int $page = 1;

    public function __construct(Player $p, array $storage)
    {
        $this->p = $p;
        $this->storage = $storage;
        try {
            $v = $p->up(2);
            $bl1 = Block::get(BlockIds::CHEST)->setComponents($v->getFloorX(), $v->getFloorY(), $v->getFloorZ());
            $bl2 = Block::get(BlockIds::CHEST)->setComponents($v->west()->getFloorX(), $v->getFloorY(), $v->getFloorZ());
            $p->getLevel()->sendBlocks([$p], [$bl1, $bl2]);
            $gui = $this->createGui(self::TITLE . StackStoragePlugin::getVersion(), $bl1, $bl2, $this->p->getLevel());
            $this->gui = $gui;
            StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask(
                new ClosureTask(
                    function (int $tick) use ($gui): void {
                        GuiAPI::$instance->sendGui($this->p->getName(), $gui);
                    }
                ), 3);
            StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask( // もし開けなかったら消す
                new ClosureTask(
                    function (int $tick) use ($gui): void {
                        if (!StackStorageAPI::$instance->isOpen($this->p->getName())) {
                            try {
                                GuiAPI::$instance->closeGui($this->p->getName());
                            } catch (Exception $ex) {
                                if ($ex->getCode() === IGuiAPI::PLAYER_NOT_FOUND | IGuiAPI::GUI_NOT_FOUND) return;
                            }
                        }
                    }
                ), 10);
        } catch (Exception $ex) {
            $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
            $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage());
        }
    }

    public function refresh()
    {
        $gui = $this->gui;

        $gui->clearAll();

        $chunk = array_chunk($this->storage, 45);
        $count = 0;

        if (isset($chunk[$this->page - 1])) {
            foreach ($chunk[$this->page - 1] as $item) {
                $item = clone $item;
                if (!$item instanceof Item) {
                    $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                    $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : storage data is corrupted');
                    return;
                }

                if ($item->getMaxStackSize() < $item->getCount()) {
                    $storeCount = $item->getCount();
                    $item->setCount($item->getMaxStackSize());
                    $item->setNamedTagEntry(new StringTag('stackstorage_store_nbt', base64_encode($item->getCompoundTag())));
                    $item->setLore(['Count', $storeCount]);
                }
                $this->gui->setItem($count, $item);
                $count++;
            }
        } else {
            if ($this->page !== 1) {
                $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : access to unauthorized storage');
                StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask(
                    new ClosureTask(
                        function (int $tick): void {
                            try {
                                GuiAPI::$instance->closeGui($this->p->getName());
                            } catch (Exception $ex) {
                                $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                                $this->p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
                            }
                        }
                    ), 5);
            }
        }

        if (isset($chunk[$this->page])) {
            $item = Item::get(ItemIds::ARROW)->setCustomName('NextPage');
            $item->setNamedTagEntry(new IntTag("stackstorage_item_value", StackStorage::SYSTEM_ITEM));
            $gui->setItem(self::NEXT, $item);
        }
        if (isset($chunk[$this->page - 2])) {
            $item = Item::get(ItemIds::ARROW)->setCustomName('BackPage');
            $item->setNamedTagEntry(new IntTag("stackstorage_item_value", StackStorage::SYSTEM_ITEM));
            $gui->setItem(self::BACK, $item);
        }
//		$gui->setItem(self::CLOSE, Item::get(Item::BOOK)->setCustomName('ClosePage'));
    }

    public function backPage()
    {
        $this->page -= 1;
        $this->refresh();
    }

    public function nextPage()
    {
        $this->page += 1;
        $this->refresh();
    }

    /**
     * @param string $title
     * @param Vector3 $bl1
     * @param Vector3 $bl2
     * @param Level $level
     * @return VirtualStackStorage
     * @throws Exception
     */
    private function createGui(string $title, Vector3 $bl1, Vector3 $bl2, Level $level): VirtualStackStorage
    {
        $bl = Chest::createTile(Tile::CHEST, $level, Chest::createNBT($bl1));
        $bl_2 = Chest::createTile(Tile::CHEST, $level, Chest::createNBT($bl2));
        if ($bl instanceof Chest and $bl_2 instanceof Chest) {
            $bl->setName($title);
            $bl_2->setName($title);
            $bl->pairWith($bl_2);
            return new VirtualStackStorage($bl, $bl_2);
        } else {
            throw new Exception('could not open block');
        }
    }
}
