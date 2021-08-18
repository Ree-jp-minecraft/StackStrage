<?php


namespace ree_jp\stackStorage\sql;


use Exception;
use PDO;
use pocketmine\item\Item;

class StackStorageHelper implements IStackStorageHelper
{
    static StackStorageHelper $instance;

    private PDO $db;

    /**
     * @inheritDoc
     */
    public function __construct(string $host, string $user, string $pass)
    {
        $options = [PDO::ATTR_CASE => PDO::CASE_UPPER,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5];
        $dsn = "mysql:host=$host;dbname=StackStorage;charset=utf8";
        $this->db = new PDO($dsn, $user, $pass, $options);
    }

    /**
     * @inheritDoc
     */
    public function isExists(string $xuid): bool
    {
        $prepare = $this->db->prepare('SHOW TABLES LIKE `:xuid`');
        return $prepare->fetch([':xuid' => $xuid]) !== false;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getStorage(string $xuid): array
    {
        $prepare = $this->db->prepare("SELECT * FROM `:xuid`");
        $list = [];
        while ($jsonItemArray = $prepare->fetch()) {
            $item = json_decode($jsonItemArray['ITEM']);
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
        $result = $prepare->fetchColumn([':xuid' => $xuid, ':item' => $jsonItem]);
        if ($result) {
            return $item->setCount($result);
        } else return $item->setCount(0);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setItem(string $xuid, Item $item): void
    {
        $count = $item->getCount();
        $jsonItem = json_encode($item->setCount(0));
        if ($this->getItem($xuid, $item)->getCount()) {
            if ($count <= 0) {
                $prepare = $this->db->prepare("UPDATE `:xuid` SET COUNT = :count WHERE ITEM = :item");
            } else {
                $prepare = $this->db->prepare("DELETE FROM `:xuid` WHERE ITEM = :item");
            }
        } else {
            if ($count <= 0) throw new Exception('cannot store 0 items');
            $prepare = $this->db->prepare("INSERT INTO `:xuid` VALUES (:item ,:count)");
        }
        $prepare->execute([':xuid' => $xuid, ':item' => $jsonItem, ':count' => $count]);
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
