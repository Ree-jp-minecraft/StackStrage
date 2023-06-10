<?php


namespace ree_jp\stackstorage\sql;


use Closure;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use ree_jp\stackstorage\api\ItemService;

class StackStorageHelper implements IStackStorageHelper
{
    static StackStorageHelper $instance;

    private string $type;
    private DataConnector $db;

    /**
     * @inheritDoc
     */
    public function __construct(PluginBase $plugin, string $path, bool $isInitFunc)
    {
        $config = new Config($path . 'sql.yml');
        $this->type = strtolower($config->get("database")["type"]);
        $this->db = libasynql::create($plugin, $config->get('database'), [
            'mysql' => 'mysql.sql',
            'sqlite' => 'sqlite.sql'
        ]);
        $this->db->executeGeneric('StackStorage.init.table');
        if ($this->type === "mysql" && $isInitFunc) {
            $this->db->executeGeneric('StackStorage.init.function.drop');
            $this->db->executeGeneric('StackStorage.init.function.create');
        }
    }

    /**
     * @inheritDoc
     */
    public function getStorage(string $xuid, Closure $func, ?Closure $failure): void
    {
        $this->db->executeSelect('StackStorage.get_all', ['xuid' => $xuid], $func, $failure);
    }

    /**
     * @inheritDoc
     */
    public function getItem(string $xuid, Item $item, Closure $func, ?Closure $failure): void
    {
        $jsonItem = ItemService::itemToJson($item);
        $this->db->executeSelect('StackStorage.get', ['xuid' => $xuid, 'item' => $jsonItem], $func, $failure);
    }

    /**
     * @inheritDoc
     */
    public function addItem(string $xuid, Item $item, ?Closure $func, ?Closure $failure): void
    {
        $jsonItem = ItemService::itemToJson($item);
        if ($this->type === "mysql") {
            $this->db->executeGeneric('StackStorage.add', ["xuid" => $xuid, "item" => $jsonItem, "count" => $item->getCount()], $func, $failure);
        } elseif ($this->type === "sqlite") {
            $this->getItem($xuid, $item, function (array $rows) use ($item, $failure, $func, $jsonItem, $xuid): void {
                $arrayItem = array_shift($rows);

                if (isset($arrayItem['count'])) {
                    $count = $arrayItem['count'] + $item->getCount();
                } else {
                    $this->db->executeInsert('StackStorage.set', ['xuid' => $xuid, 'item' => $jsonItem, 'count' => $item->getCount()], $func, $failure);
                    return;
                }

                if ($count > 0) {
                    $this->db->executeInsert('StackStorage.update', ['xuid' => $xuid, 'item' => $jsonItem, 'count' => $count], $func, $failure);
                } else {
                    $this->db->executeGeneric('StackStorage.delete', ['xuid' => $xuid, 'item' => $jsonItem], $func, $failure);
                }
            }, $failure);
        }
    }


    /**
     * @inheritDoc
     */
    public function setItem(string $xuid, Item|string $item, bool $isUpdate, Closure $func, Closure $failure, int $count = 0): void
    {
        if ($item instanceof Item) {
            $count = $item->getCount();
            $jsonItem = ItemService::itemToJson($item);
        } else {
            $jsonItem = $item;
        }
        if ($count > 0) {
            if ($isUpdate) {
                $this->db->executeInsert('StackStorage.update', ['xuid' => $xuid, 'item' => $jsonItem, 'count' => $count], $func, $failure);
            } else {
                $this->db->executeInsert('StackStorage.set', ['xuid' => $xuid, 'item' => $jsonItem, 'count' => $count], $func, $failure);
            }
        } else {
            $this->db->executeGeneric('StackStorage.delete', ['xuid' => $xuid, 'item' => $jsonItem], $func, $failure);
        }
    }

    /**
     * @inheritDoc
     */
    public function getUser(Closure $func, ?Closure $failure): void
    {
        $this->db->executeSelect('StackStorage.get_user', [], $func, $failure);
    }

    public function close(): void
    {
        $this->db->waitAll();
        $this->db->close();
    }
}
