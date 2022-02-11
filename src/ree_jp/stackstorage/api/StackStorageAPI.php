<?php


namespace ree_jp\stackstorage\api;


use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
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
                try {
                    return (clone $item)->setNamedTag((new LittleEndianNbtSerializer())->read($storeNbt)->mustGetCompoundTag());
                } catch (NbtDataException $e) {
                    StackStoragePlugin::$instance->getLogger()->critical("An error occurred while loading the saved nbt" . $e->getMessage());
                }
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
        Queue::doCache($xuid);
    }

    /**
     * @inheritDoc
     */
    public function solutionProblem(string $xuid): void
    {
        StackStorageHelper::$instance->getStorage($xuid, function (array $rows) use ($xuid): void {
            $items = [];
            $duplicate = [];
            foreach ($rows as $row) {
                // アイテムをデコード、エンコードしてNBTがちゃんと同じか検知
                if ($row["item"] !== json_encode(($afterItem = Item::jsonDeserialize(json_decode($row["item"], true))))) {
                    $afterItem->setCount($row["count"]);
                    $fuckJson = $row["item"];
                    Server::getInstance()->getLogger()->notice("inaccurate nbt($xuid) : " . $fuckJson);
                    StackStorageHelper::$instance->setItem($xuid, $fuckJson, false, function () use ($afterItem, $fuckJson, $xuid): void {
                        StackStorageHelper::$instance->addItem($xuid, $afterItem, function () use ($fuckJson, $xuid): void {
                            Server::getInstance()->getLogger()->notice("solution inaccurate data complete($xuid) : " . $fuckJson);
                            $this->solutionProblem($xuid);
                        }, function (SqlError $error) use ($xuid) {
                            Server::getInstance()->getLogger()->warning("solution inaccurate data compensation($xuid) : " . $error->getErrorMessage());
                        });
                    }, function (SqlError $error) use ($xuid) {
                        Server::getInstance()->getLogger()->warning("solution inaccurate data init($xuid) : " . $error->getErrorMessage());
                    });
                    return;
                }

                // アイテム重複検知
                if (isset($items[$row["item"]])) {
                    if (!isset($duplicate[$row["item"]])) {
                        $duplicate[] = $row["item"];
                    }
                    $items[$row["item"]] += $row["count"];
                } else {
                    $items[$row["item"]] = $row["count"];
                }
            }
            // 検知された重複を解消
            if (!empty($duplicate)) {
                foreach ($duplicate as $itemJson) {
                    $count = $items[$itemJson];
                    $itemInst = Item::jsonDeserialize(json_decode($itemJson, true));
                    $itemInst->setCount($count);
                    Server::getInstance()->getLogger()->notice("solution duplicate($xuid) : " . $itemJson);

                    StackStorageHelper::$instance->setItem($xuid, (clone $itemInst)->setCount(0), false, function () use ($itemJson, $itemInst, $xuid, $count) {
                        StackStorageHelper::$instance->setItem($xuid, $itemInst, false, function () use ($itemJson, $xuid): void {
                            Server::getInstance()->getLogger()->notice("solution duplicate complete($xuid) : " . $itemJson);
                        }, function (SqlError $error) use ($xuid, $count) {
                            Server::getInstance()->getLogger()->warning("solution duplicate compensation($xuid) : " . $error->getErrorMessage());
                        });
                    }, function (SqlError $error) use ($xuid, $count) {
                        Server::getInstance()->getLogger()->warning("solution duplicate init($xuid) : " . $error->getErrorMessage());
                    });
                }
            }
        }, function (SqlError $error) use ($xuid) {
            Server::getInstance()->getLogger()->error("Could not solution duplicate : " . $error->getErrorMessage());
        });
    }

    private function getStorage(string $xuid): ?StackStorageService
    {
        if (isset($this->storage[$xuid])) return $this->storage[$xuid];

        return null;
    }
}
