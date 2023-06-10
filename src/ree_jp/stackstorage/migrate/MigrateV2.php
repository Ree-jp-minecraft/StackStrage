<?php

namespace ree_jp\stackstorage\migrate;

use Exception;
use Generator;
use pocketmine\item\Item;
use poggit\libasynql\SqlError;
use ree_jp\stackstorage\api\StackStorageAPI;
use ree_jp\stackstorage\sql\Queue;
use ree_jp\stackstorage\sql\StackStorageHelper;
use ree_jp\stackstorage\StackStoragePlugin;
use SOFe\AwaitGenerator\Await;

class MigrateV2
{
    static function migrate(): void
    {
        StackStorageHelper::$instance->getUser(function (array $rows) {
            Await::f2c(function () use ($rows): Generator {
                foreach ($rows as $row) {
                    $xuid = $row["xuid"];
                    yield from self::migrateProcess($xuid);
                }
                StackStoragePlugin::$instance->getLogger()->notice("complete ALL migrate");
            });
        }, function (SqlError $error) {
            StackStoragePlugin::$instance->getLogger()->critical("An error occurred while preparing for data transfer : " . $error->getErrorMessage());
        });
    }

    private static function migrateProcess(string $xuid): Generator
    {
        yield from Queue::doCache($xuid);
        StackStoragePlugin::$instance->getLogger()->notice("start migrate of $xuid");
        $result = yield from Await::promise(
            fn($resolve, $reject) => StackStorageHelper::$instance->getStorage($xuid, $resolve, $reject));
        $count = 0;
        foreach ($result as $row) {
            $count++;
            try {
                $item = Item::legacyJsonDeserialize(json_decode($row["item"], true));
                $item->setCount($row["count"]);
                // 新しい形式で保存する
                StackStorageAPI::$instance->add($xuid, $item);
            } catch (Exception $error) {
                StackStoragePlugin::$instance->getLogger()->critical("An error occurred during the migration of $xuid data : " . $error->getMessage());
            }

            // データを消す
            yield from Await::promise(
                fn($resolve, $reject) => StackStorageHelper::$instance->setItem($xuid, $row['item'], false, $resolve, $reject, 0));
        }

        yield from Queue::doCache($xuid);
        StackStoragePlugin::$instance->getLogger()->notice("complete migrate of $xuid ($count items)");
    }
}
