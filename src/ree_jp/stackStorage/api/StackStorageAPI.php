<?php


namespace ree_jp\stackStorage\api;


use Closure;
use Exception;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\stackStorage\gui\StackStorage;
use ree_jp\stackStorage\sql\Queue;
use ree_jp\stackStorage\sql\StackStorageHelper;
use ree_jp\StackStorage\StackStoragePlugin;
use ree_jp\stackStorage\virtual\VirtualStackStorage;

class StackStorageAPI implements IStackStorageAPI
{
    static StackStorageAPI $instance;

    /**
     * @var StackStorage[]
     */
    private array $storage;

    /**
     * @inheritDoc
     */
    public function isOpen(string $n): bool
    {
        try {
            $gui = GuiAPI::$instance->getGui($n);
            if (!$gui instanceof VirtualStackStorage) return false;
        } catch (Exception $ex) {
            if ($ex->getCode() === IGuiAPI::PLAYER_NOT_FOUND | IGuiAPI::GUI_NOT_FOUND) return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function sendGui(Player $p, string $xuid): void
    {
        if ($this->isOpen($p->getName())) try {
            GuiAPI::$instance->closeGui($p->getName());
        } catch (Exception $ex) {
            Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
            Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
            return;
        }

        $this->getAllItems($xuid, function (array $items) use ($p, $xuid) {
            $storage = new StackStorage($p, $items);
            $storage->refresh();
            $this->storage[$xuid] = $storage;
        }, function (SqlError $error) use ($xuid, $p) {
            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
            $p->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $error->getErrorMessage());
        });
    }

    /**
     * @inheritDoc
     */
    public function setStoredNbtTag(Item $item): Item
    {
        $tag = $item->getNamedTag();
        if ($tag->offsetExists('stackstorage_store_nbt')) {
            $storeTag = base64_decode($tag->getString('stackstorage_store_nbt'));
            return (clone $item)->setCompoundTag($storeTag);
        }
        return clone $item;
    }

    /**
     * @inheritDoc
     */
    public function add(string $xuid, Item $item): void
    {
        $item = $this->setStoredNbtTag($item);
        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorage) {
            $has = false;
            foreach ($storage->storage as $key => $storageItem) {
                if (!$storageItem instanceof Item) return;
                if ($storageItem->equals($item)) {
                    $has = true;
                    $storage->storage[$key] = $storageItem->setCount($item->getCount() + $storageItem->getCount());
                }
            }
            if (!$has) $storage->storage[] = $item;
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
        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorage) {
            foreach ($storage->storage as $key => $storageItem) {
                if (!$storageItem instanceof Item) return;
                if ($storageItem->equals($item)) {
                    $count = $storageItem->getCount() - $item->getCount();
                    if ($count > 0) {
                        $storage->storage[$key] = $storageItem->setCount($count);
                    } else {
                        array_splice($storage->storage, $key, 1);
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
        if ($storage instanceof StackStorage) {
            StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($storage): void {
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
        if ($storage instanceof StackStorage) {
            $storage->backPage();
        }
    }

    /**
     * @inheritDoc
     */
    public function nextPage(string $xuid): void
    {
        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorage) {
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
        $storage = $this->getStorage($xuid);
        if ($storage instanceof StackStorage) {
            foreach ($storage->storage as $storageItem) {
                if (!$storageItem instanceof Item) continue;
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

    /**
     * @param string $xuid
     * @return StackStorage|null
     */
    private function getStorage(string $xuid): ?StackStorage
    {
        if (isset($this->storage[$xuid])) return $this->storage[$xuid];

        return null;
    }
}
