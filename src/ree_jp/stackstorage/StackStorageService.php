<?php

namespace ree_jp\stackstorage;

use Closure;
use Exception;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\block\BlockLegacyIds;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use ree_jp\stackstorage\api\StackStorageAPI;

class StackStorageService
{
    const BACK = 45;
    const NEXT = 53;
    const CLOSE = 49;
    const SYSTEM_ITEM = 1;

    private int $page = 1;

    /**
     * @param StackStorageAPI $api
     * @param InvMenu $gui
     * @param Player $p
     * @param string $xuid
     * @param Item[] $items
     */
    public function __construct(private StackStorageAPI $api, private InvMenu $gui, Player $p, private string $xuid, public array $items)
    {
        $gui->setName("StackStorage" . StackStoragePlugin::getVersion());
        $gui->setInventoryCloseListener(function (Player $p) use ($api): void {
            $api->closeCache($p->getXuid());
        });
        $gui->setListener(Closure::fromCallable([$this, "onTransaction"]));
        $gui->send($p);
        $this->refresh();
    }

    public function backPage(): void
    {
        $this->page -= 1;
        $this->refresh();
    }

    public function refresh(bool $force = false): void
    {
        if ($force) {
            $this->refreshForce();
        } else {
            StackStoragePlugin::$instance->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
                $this->refreshForce();
            }), 3);
        }
    }

    private function refreshForce(): void
    {
        $inv = $this->gui->getInventory();
        $inv->clearAll();

        $chunk = array_chunk($this->items, 45);
        $count = 0;

        if (!isset($chunk[$this->page - 1])) return;

        /** @var Item $item */
        foreach ($chunk[$this->page - 1] as $item) {
            $item = clone $item;

            if ($item->getMaxStackSize() < $item->getCount()) {
                $storeCount = $item->getCount();
                $item->setCount($item->getMaxStackSize());
                $tag = $item->getNamedTag();
                $tag->setString("stackstorage_store_nbt", (new LittleEndianNbtSerializer())->write(new TreeRoot($item->getNamedTag())));
                $item->setNamedTag($tag);
                $item->setLore(['Count', "$storeCount"]);
            }
            $inv->setItem($count, $item);
            $count++;
        }

        if (isset($chunk[$this->page])) {
            $item = VanillaItems::ARROW()->setCustomName('NextPage');
            $inv->setItem(self::NEXT, $this->setSystemItem($item));
        }
        if (isset($chunk[$this->page - 2])) {
            $item = VanillaItems::ARROW()->setCustomName('BackPage');
            $inv->setItem(self::BACK, $this->setSystemItem($item));
        }
    }

    private function setSystemItem(Item $item): Item
    {
        $tag = $item->getNamedTag();
        $tag->setInt("stackstorage_item_value", self::SYSTEM_ITEM);
        $item->setNamedTag($tag);
        return $item;
    }

    public function nextPage()
    {
        $this->page += 1;
        $this->refresh();
    }

    private function onTransaction(InvMenuTransaction $tran): InvMenuTransactionResult
    {
        if ($tran->getOut()->getId() !== BlockLegacyIds::AIR) {
            switch ($tran->getAction()->getSlot()) {
                case self::BACK:
                    $this->api->backPage($this->xuid);
                    return $tran->discard();

                case self::NEXT:
                    $this->api->nextPage($this->xuid);
                    return $tran->discard();
            }
        }
        if ($tran->getIn()->getId() !== BlockLegacyIds::AIR) {
            $this->api->add($this->xuid, $tran->getIn());
        }
        if ($tran->getOut()->getId() !== BlockLegacyIds::AIR) {
            try {
                $item = StackStorageAPI::$instance->setStoredNbtTag($tran->getOut());
                $cacheItem = StackStorageAPI::$instance->setStoredNbtTag($this->getCache($item, $tran->getAction()->getSlot()));
                if ($item->getCount() > $cacheItem->getCount()) throw new Exception("could not reduce items(There is no number)");

                // 原因不明の減らないバグの一時的な対策のためキャッシュしているアイテムを使用
                StackStorageAPI::$instance->remove($this->xuid, $cacheItem->setCount($item->getCount()));
            } catch (Exception $e) {
                StackStoragePlugin::$instance->getLogger()->logException($e);
                return $tran->discard();
            }
        }
        return $tran->continue();
    }

    /**
     * @throws Exception
     */
    private function getCache(Item $item, int $slot): Item
    {
        $pageItems = array_chunk($this->items, 45)[$this->page - 1];
        if (isset($pageItems[$slot]) && $item->equals($pageItems[$slot])) {
            return $pageItems[$slot];
        }
        foreach ($this->items as $storageItem) {
            if ($item->equals($storageItem)) {
                return $storageItem;
            }
        }
        throw new Exception("could not reduce items(Item not found)");
    }
}
