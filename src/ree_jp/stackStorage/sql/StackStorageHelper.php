<?php


namespace ree_jp\stackStorage\sql;


use Exception;
use PDO;
use pocketmine\item\Item;

class StackStorageHelper implements IStackStorageHelper
{
    static ?StackStorageHelper $instance = null;

    private PDO $db;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function __construct(string $database, string $host, string $db, string $user, string $pass)
    {
        $options = [PDO::ATTR_CASE => PDO::CASE_UPPER,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5];
        switch ($database) {
            case 'mysql':
                $dsn = "mysql:host=$host;dbname=$db;charset=utf8";
                $this->db = new PDO($dsn, $user, $pass, $options);
                break;

            case 'sqlite':
                $this->db = new PDO('sqlite:StackStorage.db', null, null, $options);
                break;

            default:
                throw new Exception($database . ' is not support');
        }
    }

    /**
     * @inheritDoc
     */
    public function isExists(string $xuid): bool
    {
        $prepare = $this->db->prepare('SHOW TABLES LIKE `:xuid`');
        $prepare->execute([':xuid' => $xuid]);
        return $prepare->fetch() !== false;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getStorage(string $xuid): array
    {
        $prepare = $this->db->prepare("SELECT * FROM `:xuid`");
        $prepare->execute([':xuid' => $xuid]);
        $list = [];
        while ($jsonItemArray = $prepare->fetch()) {
            $item = Item::jsonDeserialize(json_decode($jsonItemArray['ITEM'], true));
            if (!$item instanceof Item) throw new Exception('data is corrupted');
            $list[] = $item->setCount($jsonItemArray['COUNT']);
        }
        return $list;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setStorage(string $xuid, array $items): void
    {
        if ($this->isExists($xuid)) {
            $prepare = $this->db->prepare("TRUNCATE TABLE `:xuid`");
            $prepare->execute([':xuid' => $xuid]);
        }
        $this->setTable($xuid);
        foreach ($items as $item) {
            $this->setItem($xuid, $item);
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getItem(string $xuid, Item $item): Item
    {
        $jsonItem = json_encode((clone $item)->setCount(0));
        $prepare = $this->db->prepare("SELECT COUNT FROM `:xuid` WHERE ITEM = :item");
        $prepare->execute([':xuid' => $xuid, ':item' => $jsonItem]);
        $result = $prepare->fetchColumn();
        if ($result) {
            return (clone $item)->setCount($result);
        } else return (clone $item)->setCount(0);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setItem(string $xuid, Item $item): void
    {
        $count = $item->getCount();
        $jsonItem = json_encode((clone $item)->setCount(0));
        if ($count > 0) {

            if ($this->getItem($xuid, $item)->getCount() === 0) {
                $prepare = $this->db->prepare("INSERT INTO `:xuid` VALUES (:item ,:count)");
            } else {
                $prepare = $this->db->prepare("UPDATE `:xuid` SET COUNT = :count WHERE ITEM = :item");
            }
            $prepare->execute([':xuid' => $xuid, ':item' => $jsonItem, ':count' => $count]);
        } else {
            $prepare = $this->db->prepare("DELETE FROM `:xuid` WHERE ITEM = :item");
            $prepare->execute([':xuid' => $xuid, ':item' => $jsonItem]);
        }
    }

    /**
     * @param string $xuid
     */
    public function setTable(string $xuid): void
    {
        $prepare = $this->db->prepare("CREATE TABLE IF NOT EXISTS `:xuid` (ITEM JSON NOT NULL ,COUNT INTEGER UNSIGNED NOT NULL)");
        $prepare->execute([':xuid' => $xuid]);
    }
}
