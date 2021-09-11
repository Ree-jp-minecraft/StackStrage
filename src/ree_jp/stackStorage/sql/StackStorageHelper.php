<?php


namespace ree_jp\stackStorage\sql;


use Closure;
use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class StackStorageHelper implements IStackStorageHelper
{
    static StackStorageHelper $instance;

    private DataConnector $db;

    /**
     * @inheritDoc
     */
    public function __construct(PluginBase $plugin, string $path)
    {
        $config = new Config($path . 'sql.yml');
        $this->db = libasynql::create($plugin, $config->get('database'), [
            'mysql' => 'mysql.sql',
            'sqlite' => 'sqlite.sql'
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getStorage(string $xuid, Closure $func, Closure $failure): void
    {
        $this->db->executeSelect('StackStorage.get_all', ['xuid' => $xuid], $func, $failure);
    }

    /**
     * @inheritDoc
     */
    public function getItem(string $xuid, Item $item, Closure $func): void
    {
        $jsonItem = json_encode((clone $item)->setCount(0));
        $this->db->executeSelect('StackStorage.get', ['xuid' => $xuid, 'item' => $jsonItem], $func, function (SqlError $error) {
            Server::getInstance()->getLogger()->error('Could not get the item : ' . $error->getErrorMessage());
        });
    }

    /**
     * @inheritDoc
     */
    public function setItem(string $xuid, Item $item, ?Closure $func = null): void
    {
        $count = $item->getCount();
        $jsonItem = json_encode((clone $item)->setCount(0));
        if ($count > 0) {
            $this->db->executeInsert('StackStorage.set', ['xuid' => $xuid, 'item' => $jsonItem, 'count' => $count], $func,
                function (SqlError $error) {
                    Server::getInstance()->getLogger()->error('Could not set the item : ' . $error->getErrorMessage());
                });
        } else {
            $this->db->executeGeneric('StackStorage.delete', ['xuid' => $xuid, 'item' => $jsonItem], $func,
                function (SqlError $error) {
                    Server::getInstance()->getLogger()->error('Could not delete the item : ' . $error->getErrorMessage());
                });
        }
    }
}
