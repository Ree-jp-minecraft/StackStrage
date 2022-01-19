<?php


namespace ree_jp\stackstorage\api;


use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\stackstorage\sql\Queue;
use ree_jp\stackstorage\sql\StackStorageHelper;
use ree_jp\stackstorage\StackStoragePlugin;
use ree_jp\stackstorage\StackStorageService;

class StackStorageAPI implements IStackStorageAPI
{
    static StackStorageAPI $instance;

    /**
     * @var StackStorageService[]
     */
    private array $storage;

    /**
     * @inheritDoc
     */
    public function isOpen(string $xuid): bool
    {
        return $this->getStorage($xuid) instanceof StackStorageService;
    }

    /**
     * @inheritDoc
     */
    public function sendGui(Player $p, string $xuid): void
    {
        $this->getAllItems($xuid, function (array $items) use ($p, $xuid) {
            if ($p->isOnline()) {
                $service = new StackStorageService($this, InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST), $p, $xuid, $items);
                $this->storage[$xuid] = $service;
            }
        }, function (SqlError $error) use ($xuid, $p) {
            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $error->getErrorMessage());
        });
    }

    /**
     * @inheritDoc
     */
    public function setStoredNbtTag(Item $item): ?Item
    {
        $tag = $item->getNamedTag();

        if ($tag->getInt("stackstorage_item_value", 0) === StackStorageService::SYSTEM_ITEM) return null;

        $storeNbt = $tag->getString('stackstorage_store_nbt', "no");
        if ($storeNbt !== "no") {
            if ($storeNbt === "") {
                return (clone $item)->clearNamedTag();
            } else {
                return (clone $item)->setNamedTag((new LittleEndianNbtSerializer())->read($storeNbt)->mustGetCompoundTag());
            }
        }
        return clone $item;
    }

    /**
     * @inheritDoc
     */
    public function add(string $xuid, Item $item): void
    {
        $item = $this->setStoredNbtTag($item);
        if (!$item instanceof Item) return;

        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorageService) {
            $has = false;
            foreach ($storage->items as $key => $storageItem) {
                if ($storageItem->equals($item)) {
                    $has = true;
                    $storage->items[$key] = $storageItem->setCount($item->getCount() + $storageItem->getCount());
                }
            }
            if (!$has) {
                $storage->items[] = $item;
            }
            $storage->refresh();
        }
        Queue::add($xuid, clone $item);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $xuid, Item $item): void
    {
        $item = $this->setStoredNbtTag($item);
        if (!$item instanceof Item) return;

        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorageService) {
            foreach ($storage->items as $key => $storageItem) {
                if ($storageItem->equals($item)) {
                    $count = $storageItem->getCount() - $item->getCount();
                    if ($count > 0) {
                        $storage->items[$key] = $storageItem->setCount($count);
                    } else {
                        array_splice($storage->items, $key, 1);
                    }
                    break;
                }
            }
            $storage->refresh();
        }
        Queue::reduce($xuid, clone $item);
    }

    public function refresh(string $xuid): void
    {
        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorageService) {
            StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($storage): void {
                $storage->refresh();
            }), 3);
        }
    }

    /**
     * @inheritDoc
     */
    public function backPage(string $xuid): void
    {
        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorageService) {
            $storage->backPage();
        }
    }

    /**
     * @inheritDoc
     */
    public function nextPage(string $xuid): void
    {
        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorageService) {
            $storage->nextPage();
        }
    }

    /**
     * @inheritDoc
     */
    public function getCount(string $xuid, Item $item, Closure $func, ?Closure $failure): void
    {
        Queue::doCache($xuid);
        StackStorageHelper::$instance->getItem($xuid, $item, function (array $rows) use ($xuid, $func) {
            $arrayItem = array_shift($rows);
            $count = 0;
            if (isset($arrayItem['count'])) $count = $arrayItem['count'];
            $func($count);
        }, $failure);
    }

    /**
     * @inheritDoc
     */
    public function getAllItems(string $xuid, Closure $func, ?Closure $failure): void
    {
        Queue::doCache($xuid);
        StackStorageHelper::$instance->getStorage($xuid, function (array $rows) use ($xuid, $func) {
            $items = [];
            foreach ($rows as $row) {
                $item = Item::jsonDeserialize(json_decode($row['item'], true));
                $items[] = $item->setCount($row['count']);
            }
            $func($items);
        }, $failure);
    }

    /**
     * @inheritDoc
     */
    public function hasCountFromCache(string $xuid, Item $item): bool
    {
        $item = $this->setStoredNbtTag($item);
        if (!$item instanceof Item) return false;

        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorageService) {
            foreach ($storage->items as $storageItem) {
                if ($storageItem->equals($item)) {
                    return $storageItem->getCount() >= $item->getCount();
                }
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function closeCache(string $xuid): void
    {
        if (isset($this->storage[$xuid])) unset($this->storage[$xuid]);
    }

    private function getStorage(string $xuid): ?StackStorageService
    {
        if (isset($this->storage[$xuid])) return $this->storage[$xuid];

        return null;
    }
}
