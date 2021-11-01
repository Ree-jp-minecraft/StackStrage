<?php

namespace ree_jp\stackStorage\sql;

use Closure;
use pocketmine\item\Item;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use poggit\libasynql\SqlError;
use ree_jp\StackStorage\StackStoragePlugin;

class Queue
{
    static array $queues = []; // データ取得関係のタスク (アイテムの数を取得するなど)
    static array $stockQueues = []; // 実行中の在庫関係のタスク (アイテムの追加、減らすなど)
    static array $cache = []; // 在庫関係のキャッシュ
    static array $blockedCache = []; // 在庫関係のタスクを中止されている間はここにキャッシュ
    static array $task = [];

    static bool $isBlockCache = false; // 在庫関係のタスクを中止するかどうか

    static function enqueue(string $xuid, Closure $func): void
    {
        if (empty(self::$queues[$xuid])) {
            self::$queues[$xuid] = [];
            self::$isBlockCache = true;
            array_push(self::$queues[$xuid], $func);

            if (empty(self::$stockQueues[$xuid]) && empty(self::$cache[$xuid])) { // 処理中とキャッシュがなければ即座にqueueに追加
                $func();
            }
        } else { // すでに別のキューがある場合
            array_push(self::$queues[$xuid], $func);
        }
    }

    static function dequeue(string $xuid): void
    {
        if (!empty(self::$queues[$xuid])) {
            array_shift(self::$queues[$xuid]);
            $next = current(self::$queues[$xuid]);
            if ($next === false) { // 次のキューがなければ在庫関係の処理を再開
                self::$isBlockCache = false;
                if (empty(self::$blockedCache[$xuid])) return;
                foreach (self::$blockedCache[$xuid] as $cacheItem) {
                    self::add($xuid, $cacheItem);
                }
            } else { // 次のキューがあれば連続で
                $next();
            }
        }
    }

    private static function stockEnqueue(string $xuid, Item $item)
    {
        if (empty(self::$stockQueues[$xuid])) {
            self::$queues[$xuid] = [];
        }
        self::$stockQueues[$xuid][] = $item;
        StackStorageHelper::$instance->getItem($xuid, $item, function (array $rows) use ($item, $xuid) {
            $arrayItem = array_shift($rows);
            if (isset($arrayItem['count'])) $item->setCount($arrayItem['count'] + $item->getCount());
            StackStorageHelper::$instance->setItem($xuid, $item, isset($arrayItem['count']), function () use ($item, $xuid) {
                Queue::stockDequeue($xuid, $item);
            }, function (SqlError $error) use ($item, $xuid) {
                Server::getInstance()->getLogger()->error('Could not set the item : ' . $error->getErrorMessage());
                Queue::stockDequeue($xuid, $item);
            });
        }, function (SqlError $error) use ($item, $xuid) {
            Queue::stockDequeue($xuid, $item);
            Server::getInstance()->getLogger()->error('Could not get the item : ' . $error->getErrorMessage());
        });
    }

    private static function stockDequeue(string $xuid, Item $item): void
    {
        if (isset(self::$stockQueues[$xuid])) {
            foreach (self::$stockQueues[$xuid] as $key => $queueItem) { // stockQueuesから削除
                if ($item->equals($queueItem)) {
                    unset(self::$stockQueues[$xuid][$key]);
                }
            }
        }
        if (isset(self::$cache[$xuid])) {
            foreach (self::$cache[$xuid] as $key => $cacheItem) { // cacheにそのアイテムがあれば保存する
                if ($item->equals($cacheItem)) {
                    unset(self::$cache[$xuid][$key]);
                    self::stockEnqueue($xuid, $cacheItem);
                }
            }
        }
        if (!empty(self::$queues[$xuid]) && empty(self::$stockQueues[$xuid]) && empty(self::$cache[$xuid])) {
            $func = current(self::$queues[$xuid]);
            if ($func !== false) $func();
        }
    }

    static function add(string $xuid, Item $item): void
    {
        if (self::$isBlockCache) {
            array_push(self::$blockedCache[$xuid], $item);
        } else {
            if (empty(self::$cache[$xuid])) {
                self::$cache[$xuid] = [];
                self::$task[$xuid] = StackStoragePlugin::getMain()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($xuid): void {
                    if (empty(self::$cache[$xuid])) return;
                    foreach (self::$cache[$xuid] as $item) self::stockEnqueue($xuid, $item);
                    unset(self::$cache[$xuid]);
                    unset(self::$task[$xuid]);
                }), 5 * 20);
            }
            foreach (self::$cache[$xuid] as $key => $cacheItem) {
                if (!$cacheItem instanceof Item) continue;
                if ($item->equals($cacheItem)) {
                    self::$cache[$xuid][$key] = $cacheItem->setCount($cacheItem->getCount() + $item->getCount());
                    return;
                }
            }
            array_push(self::$cache[$xuid], $item);
        }
    }

    static function reduce(string $xuid, Item $item): void
    {
        self::add($xuid, $item->setCount(-$item->getCount()));
    }

    static function checkCache(): void
    {
        foreach (self::$cache as $xuid => $items) {
            foreach ($items as $item) {
                if (!$item instanceof Item) continue;
                $isProcessing = false;

                foreach (self::$stockQueues as $queueItems) {
                    foreach ($queueItems as $queueItem) {
                        if ($item->equals($queueItem)) {
                            $isProcessing = true;
                            break 2;
                        }
                    }
                }
                if (!$isProcessing) { // もし処理中じゃなかったら保存する
                    self::stockEnqueue($xuid, $item);
                }
            }
        }
    }

    static function isEmpty(?string $xuid = null): bool
    {
        if (is_null($xuid)) {
            foreach (self::$queues as $queue) {
                if (!empty($queue)) return false;
            }
            foreach (self::$stockQueues as $stockQueue) {
                if (!empty($stockQueue)) return false;
            }
            foreach (self::$cache as $cache) {
                if (!empty($cache)) return false;
            }
            foreach (self::$blockedCache as $blockedCache) {
                if (!empty($blockedCache)) return false;
            }
        } else {
            if (empty(self::$queues[$xuid]) && empty(self::$stockQueues[$xuid]) && empty(self::$cache) && empty(self::$blockedCache)) return false;
        }
        return true;
    }
}
