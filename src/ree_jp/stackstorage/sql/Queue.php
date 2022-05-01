<?php

namespace ree_jp\stackstorage\sql;

use Closure;
use Generator;
use pocketmine\item\Item;
use pocketmine\Server;
use poggit\libasynql\SqlError;
use SOFe\AwaitGenerator\Await;

class Queue
{
    static array $cache = [];

    static function add(string $xuid, Item $item): void
    {
        if (empty(self::$cache[$xuid])) {
            self::$cache[$xuid] = [];
        }
        foreach (self::$cache[$xuid] as $key => $cacheItem) {
            if (!$cacheItem instanceof Item) continue;
            if ($item->equals($cacheItem)) {
                self::$cache[$xuid][$key] = $cacheItem->setCount($cacheItem->getCount() + $item->getCount());
                return;
            }
        }
        self::$cache[$xuid][] = $item;
    }

    static function reduce(string $xuid, Item $item): void
    {
        self::add($xuid, $item->setCount(-$item->getCount()));
    }

    private static function addItem(string $xuid, Item $item, ?Closure $func): void
    {
        if ($item->getCount() === 0) return;
        sleep(5);
        StackStorageHelper::$instance->addItem($xuid, $item, $func, function (SqlError $error) use ($xuid) {
            Server::getInstance()->getLogger()->error("Could not add the item : " . $error->getErrorMessage());
        });
    }

    static function doCache(string $xuid): Generator
    {
        var_dump("exe");
        if (!isset(self::$cache[$xuid])) return;

        $items = self::$cache[$xuid];
        $await = [];
        foreach ($items as $key => $item) {
            $func = function (): void {
            };
            $await[$key] = yield from Await::promise(fn($func) => self::addItem($xuid, $item, $func));
            unset(self::$cache[$xuid][$key]);
            self::addItem($xuid, $item, $func);
            var_dump("aaa$key");
        }
        var_dump($await);
        yield Await::all($await);
    }

    static function doAllCache(): Generator
    {
        foreach (self::$cache as $xuid => $items) {
            yield self::doCache($xuid);
        }
    }

    static function isEmpty(?string $xuid = null): bool
    {
        if (is_null($xuid)) {
            foreach (self::$cache as $cache) {
                if (!empty($cache)) return false;
            }
            return true;
        } else {
            return !isset(self::$cache[$xuid]) || empty(self::$cache[$xuid]);
        }
    }
}
