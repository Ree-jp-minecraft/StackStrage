<?php


namespace ree\stackStorage\sqlite;


use Exception;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use Ree\StackStorage\stackStoragePlugin;
use SQLite3;

class StackStorageHelper implements IStackStorageHelper
{
	/**
	 * @var StackStorageHelper
	 */
	private static $instance;

	/**
	 * @var SQLite3
	 */
	private $db;

	/**
	 * @inheritDoc
	 */
	public function __construct(string $path)
	{
		$this->db = new SQLite3($path);
		$this->db->exec('CREATE TABLE IF NOT EXISTS xuid (xuid TEXT NOT NULL PRIMARY KEY , name TEXT NOT NULL)');
	}

	/**
	 * @inheritDoc
	 */
	public static function getInstance(): IStackStorageHelper
	{
		if (!self::$instance instanceof StackStorageHelper) {
			self::$instance = new StackStorageHelper(stackStoragePlugin::getMain()->getDataFolder() . '/StackStorage.db');
		}
		return self::$instance;
	}

	/**
	 * @inheritDoc
	 */
	public function getXuid(string $n): ?string
	{
		$name = mb_strtolower($n);
		$stmt = $this->db->prepare('SELECT xuid FROM xuid WHERE name = :name');
		$stmt->bindParam(':name', $name, SQLITE3_TEXT);
		$result = $stmt->execute()->fetchArray();
		if (!$result) return null;
		$result = current($result);
		return $result;
	}

	/**
	 * @inheritDoc
	 */
	public function getName(string $xuid): ?string
	{
		$stmt = $this->db->prepare('SELECT name FROM xuid WHERE xuid = :xuid');
		$stmt->bindParam(':xuid', $xuid, SQLITE3_TEXT);
		$result = $stmt->execute()->fetchArray();
		if ($result) {
			return mb_strtolower(current($result));
		} else {
			return null;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setName(string $xuid, string $name): void
	{
		$name = mb_strtolower($name);
		$old = $this->getName($xuid);
		if ($old == !$name) {
			if (!$old) {
				$stmt = $this->db->prepare('INSERT INTO xuid VALUES (:xuid, :name)');
				$stmt->bindParam(':name', $name, SQLITE3_TEXT);
				$stmt->bindParam(':xuid', $xuid, SQLITE3_TEXT);
				$stmt->execute();
			}
			$stmt = $this->db->prepare('UPDATE xuid SET name = :name WHERE xuid = :xuid');
			$stmt->bindParam(':name', $name, SQLITE3_TEXT);
			$stmt->bindParam(':xuid', $xuid, SQLITE3_TEXT);
			$stmt->execute();
		}

	}

	/**
	 * @inheritDoc
	 */
	public function isExists(string $xuid): bool
	{
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = :xuid");
		$stmt->bindParam(':xuid', $xuid, SQLITE3_TEXT);
		return current($stmt->execute()->fetchArray(SQLITE3_NUM));
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function getStorage(string $xuid): array
	{
		if (!$this->isExists($xuid)) throw new Exception('storage not found', self::STORAGE_NOT_FOUND);
		$result = $this->db->query("SELECT * FROM [${xuid}]");
		$list = [];
		while ($array = $result->fetchArray(SQLITE3_NUM)) {
			$item = Item::get($array[0]);
			$item->setDamage($array[1]);
			$this->enchantEncode($item, $array[2]);
			$item->setCount($array[3]);
			$list[] = $item;
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
			$this->db->exec("DELETE FROM [${xuid}]");
		}
		$this->setTable($xuid);
		foreach ($items as $item) {
			if (!$item instanceof Item) throw new Exception('request item object');

			$this->setItem($xuid, $item);
		}
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function getItem(string $xuid, Item $item): Item
	{
		if (!$item instanceof Item) throw new Exception('request item object');

		if (!$this->isExists($xuid)) throw new Exception('storage not found', self::STORAGE_NOT_FOUND);

		$id = $item->getId();
		$meta = $item->getDamage();
		$item = clone $item;
		$enchant = $this->enchantDecode($item);
		$stmt = $this->db->prepare("SELECT count FROM [${xuid}] WHERE id = :id AND meta = :meta AND enchant = :enchant");
		$stmt->bindValue(':id', $id, SQLITE3_NUM);
		$stmt->bindValue(':meta', $meta, SQLITE3_NUM);
		$stmt->bindParam(':enchant', $enchant, SQLITE3_TEXT);
		$result = $stmt->execute()->fetchArray();
		if ($result) {
			return $item->setCount(current($result));
		} else {
			return $item->setCount(0);
		}
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function setItem(string $xuid, Item $item): void
	{
		if (!$item instanceof Item) throw new Exception('request item object');

		if (!$this->isExists($xuid)) throw new Exception('storage not found', self::STORAGE_NOT_FOUND);

		$count = $item->getCount();
		$enchant = $this->enchantDecode($item);
		$stmt = null;
		if ($this->getItem($xuid, $item)->getCount()) {
			if ($count) {
				$stmt = $this->db->prepare("UPDATE [${xuid}] SET count = :count WHERE id = :id AND meta = :meta AND enchant = :enchant");
			} else {
				$stmt = $this->db->prepare("DELETE FROM [${xuid}] WHERE id = :id AND meta = :meta AND enchant = :enchant");
			}
		} else {
			if (!$count) return;
			$stmt = $this->db->prepare("INSERT INTO [${xuid}] VALUES (:id, :meta, :enchant, :count)");
		}
		$stmt->bindValue(':id', $item->getId(), SQLITE3_NUM);
		$stmt->bindValue(':meta', $item->getDamage(), SQLITE3_NUM);
		$stmt->bindParam(':enchant', $enchant, SQLITE3_TEXT);
		$stmt->bindValue(':count', $count, SQLITE3_NUM);
		$stmt->execute();
	}

	/**
	 * @inheritDoc
	 * @throws Exception
	 */
	public function enchantEncode(Item $item, string $enchant): Item
	{
		$array = json_decode($enchant, true);
		$key = array_keys($array);
		foreach ($key as $id) {
			$level = $array[$id];
			$enchant = Enchantment::getEnchantment($id);
			if ($enchant) {
				$item->addEnchantment(new EnchantmentInstance($enchant, $level));
			} else {
				throw new Exception('enchant not found', self::ENCHANT_ID_NOT_FOUND);
			}
		}
		return $item;
	}

	/**
	 * @inheritDoc
	 */
	public function enchantDecode(Item $item): string
	{
		$list = [];
		foreach ($item->getEnchantments() as $enchantment) {
			$list[$enchantment->getId()] = $enchantment->getLevel();
		}
		$result = json_encode($list);
		if ($result) return $result;
		return null;
	}

	/**
	 * @param string $xuid
	 */
	private function setTable(string $xuid): void
	{
		$this->db->exec("CREATE TABLE IF NOT EXISTS [${xuid}] (id INTEGER NOT NULL, meta INTEGER NOT NULL, enchant TEXT, count INTEGER NOT NULL, PRIMARY KEY (id, meta, enchant))");
	}
}