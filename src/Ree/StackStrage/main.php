<?php


namespace Ree\StackStrage;


use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Ree\seichi\Task\TeleportTask;
use Ree\StackStrage\Virchal\GatyaStrage;
use Ree\StackStrage\Virchal\SkilSelect;
use Ree\StackStrage\Virchal\SkilUnlock;
use Ree\StackStrage\Virchal\StackStrage;
use Ree\StackStrage\VirchalInterface\VirchaGatyaStrage;

class main extends PluginBase implements Listener
{
    /**
     * @var main
     */
    public static $main;

    /**
     * @var Config
     */
    public $gatyaStrage;

    public function onEnable()
    {
        echo "StackStrage >> loading now...\n";
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->gatyaStrage = new Config($this->getDataFolder() . "gatya_strage.yml", Config::YAML);
        self::$main = $this;
        echo "StackStrage >> Complete\n";
    }

    public function onDisable()
    {
        $this->gatyaStrage->save();
    }

    public function onTransaction(InventoryTransactionEvent $ev)
    {
        $tr = $ev->getTransaction();
        $inve = $tr->getInventories();

        foreach ($inve as $inv) {
            foreach ($tr->getActions() as $action) {
                if ($action instanceof SlotChangeAction) {
                    $p = $tr->getSource()->getPlayer();
                    $n = $p->getName();
                    $pT = \Ree\seichi\main::getpT($n);
                    $p = $pT->getPlayer();

                    if ($action->getInventory() instanceof VirchaStackStrage) {
                        if (!$pT->s_chestInstance instanceof StackStrage)
                        {
                            \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " StackStrageにアクセスできません", $this);
                        }
                        if ($action->getSourceItem()->getId() !== Item::AIR) //取り出す
                        {
                            if ($action->getSlot() < 45) {
                                $item = $action->getSourceItem();
                                StackStrage_API::remove($p, $item);
                                $this->getScheduler()->scheduleDelayedTask(new UpdatePage($pT), 2);
                                return;
                            }
                        }
                        if ($action->getTargetItem()->getId() !== Item::AIR) //入れる
                        {
                            $item = $action->getTargetItem();
                            $bool = StackStrage_API::add($p, $item);
                            $this->getScheduler()->scheduleDelayedTask(new UpdatePage($pT), 2);
                            if (!$bool) {
                                $ev->setCancelled();
                                $pT->errer("line" . __LINE__ . " StackStrageにアイテムを入れれませんでした", $this);
                            }
                            return;
                        }
                        if ($action->getSlot() == 48) {
                            $ev->setCancelled();
                            $bool = $pT->s_chestInstance->backpage();
                            return;
                        }
                        if ($action->getSlot() == 50) {
                            $ev->setCancelled();
                            $bool = $pT->s_chestInstance->nextpage();
                            return;
                        }
                        if ($action->getSlot() == 49) {
                            ChestGuiManager::CloseInventory($p, $p->getFloorX(), $p->getFloorY(), $p->getFloorZ());
                            $ev->setCancelled();
                            return;
                        }
                        if ($action->getSlot() === 53) {
                            $pT->s_open = true;
                            $pT->s_chestInstance = new GatyaStrage($pT);
                            $ev->setCancelled();
                            return;
                        }
                    }

                    if ($action->getInventory() instanceof VirchaGatyaStrage)
                    {
                        if (!$pT->s_chestInstance instanceof GatyaStrage)
                        {
                            \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " GatyaStrageにアクセスできません", $this);
                        }
                        if ($action->getSourceItem()->getId() !== Item::AIR) //取り出す
                        {
                            if ($action->getSlot() < 45) {
                                $item = $action->getSourceItem();
                                GatyaStrage_API::remove($p, $item);
                                $this->getScheduler()->scheduleDelayedTask(new UpdatePage($pT), 2);
                                return;
                            }
                        }
                        if ($action->getTargetItem()->getId() !== Item::AIR) //入れる
                        {
                            $item = $action->getTargetItem();
                            $bool = GatyaStrage_API::add($p, $item);
                            $this->getScheduler()->scheduleDelayedTask(new UpdatePage($pT), 2);
                            if (!$bool) {
                                $ev->setCancelled();
                                $pT->errer("line" . __LINE__ . " GatyaStrageにアイテムを入れれませんでした", $this);
                            }
                            return;
                        }
                        if ($action->getSlot() == 49) {
                            ChestGuiManager::CloseInventory($p, $p->getFloorX(), $p->getFloorY(), $p->getFloorZ());
                            $ev->setCancelled();
                            return;
                        }
                    }

                    if ($action->getInventory() instanceof VirchalSkilSelect) {
                        if (!$pT->s_chestInstance instanceof SkilSelect)
                        {
                            \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " SkilSlectにアクセスできません", $this);
                        }
                        if ($action->getSlot() === 53) {
                            $pT->s_open = true;
                            $pT->s_chestInstance = new SkilUnlock($pT);
                            $ev->setCancelled();
                            return;
                        }

                        $slot = $action->getSlot();
                        $skillist = $pT->s_chestInstance->getSkil();
                        if (isset($skillist[$slot])) {
                            $skil = $skillist[$slot];
                            $pT->s_nowSkil = $skil;
                            $p->sendMessage("Skilを" . $pT->s_nowSkil::getName() . "に変更しました");
                            ChestGuiManager::CloseInventory($p, $p->getFloorX(), $p->getFloorY(), $p->getFloorZ());
                        }
                        $ev->setCancelled();
                        return;
                    }

                    if ($action->getInventory() instanceof VirchalSkilUnlock) {
                        if (!$pT->s_chestInstance instanceof SkilUnlock)
                        {
                            \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " SkilUnlockにアクセスできません", $this);
                        }
                        if ($action->getSlot() === 53) {
                            $pT->s_open = true;
                            $pT->s_chestInstance = new SkilSelect($pT);
                            $ev->setCancelled();
                            return;
                        }
                        $slot = $action->getSlot();
                        $skillist = $pT->s_chestInstance->getSkil();
                        if (isset($skillist[$slot])) {
                            $skil = $skillist[$slot];
                            if ($pT->s_skilpoint >= $skil::getSkilpoint()) {
                                $pT->s_skilpoint = $pT->s_skilpoint - $skil::getSkilpoint();
                                $pT->s_skil[] = $skil::getClassName();
                                $p->sendMessage($skil::getName() . "を解禁しました");
                                $x = (int)$p->x;
                                $y = (int)$p->y + 3;
                                $z = (int)$p->z;
                                ChestGuiManager::CloseInventory($p, $x, $y, $z);
                            } else {
                                $p->sendMessage("§cスキルポイントが足りません");
                            }
                        }
                        $ev->setCancelled();
                        return;
                    }

                    if ($action->getInventory() instanceof VirchalWorldSelect) {
                        switch ($action->getSlot()) {
                            case 10:
                                if (!$this->getServer()->loadLevel("lobby")) {
                                    \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " ワールドlobbyが存在しません", $this);
                                } else {
                                    $p->sendMessage("ワールド: lobby にテレポートしています...");
                                    ChestGuiManager::CloseInventory($p, $p->x, $p->y, $p->z);
                                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($p, $this->getServer()->getLevelByName("lobby")->getSafeSpawn()), 20);
                                }
                                $ev->setCancelled();
                                return;

                            case 11:
                                if (!$this->getServer()->loadLevel("leveling_1")) {
                                    \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " ワールドleveling_1が存在しません", $this);
                                } else {
                                    $p->sendMessage("ワールド: leveling_1 にテレポートしています...");
                                    ChestGuiManager::CloseInventory($p, $p->x, $p->y, $p->z);
                                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($p, $this->getServer()->getLevelByName("leveling_1")->getSafeSpawn()), 20);
                                }
                                $ev->setCancelled();
                                return;

                            case 12:
                                if (!$this->getServer()->loadLevel("leveling_2")) {
                                    \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " ワールドleveling_2が存在しません", $this);
                                } else {
                                    $p->sendMessage("ワールド: leveling_2 にテレポートしています...");
                                    ChestGuiManager::CloseInventory($p, $p->x, $p->y, $p->z);
                                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($p, $this->getServer()->getLevelByName("leveling_2")->getSafeSpawn()), 20);
                                }
                                $ev->setCancelled();
                                return;

                            case 13:
                                if (!$this->getServer()->loadLevel("leveling_2")) {
                                    \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " ワールドleveling_3が存在しません", $this);
                                } else {
                                    $p->sendMessage("ワールド: leveling_3 にテレポートしています...");
                                    ChestGuiManager::CloseInventory($p, $p->x, $p->y, $p->z);
                                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($p, $this->getServer()->getLevelByName("leveling_3")->getSafeSpawn()), 20);
                                }
                                $ev->setCancelled();
                                return;

                            case 14:
                                if (!$this->getServer()->loadLevel("life_1")) {
                                    \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " ワールドlife_1が存在しません", $this);
                                } else {
                                    $p->sendMessage("ワールド: life_1 にテレポートしています...");
                                    ChestGuiManager::CloseInventory($p, $p->x, $p->y, $p->z);
                                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($p, $this->getServer()->getLevelByName("life_1")->getSafeSpawn()), 20);
                                }
                                $ev->setCancelled();
                                return;

                            case 15:
                                if (!$this->getServer()->loadLevel("life_1")) {
                                    \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " ワールドlife_2が存在しません", $this);
                                } else {
                                    $p->sendMessage("ワールド: life_2 にテレポートしています...");
                                    ChestGuiManager::CloseInventory($p, $p->x, $p->y, $p->z);
                                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($p, $this->getServer()->getLevelByName("life_2")->getSafeSpawn()), 20);
                                }
                                $ev->setCancelled();
                                return;

                            case 16:
                                if (!$this->getServer()->loadLevel("public")) {
                                    \Ree\seichi\main::getpT($p->getName())->errer("line" . __LINE__ . " ワールドpublicが存在しません", $this);
                                } else {
                                    $p->sendMessage("ワールド: public にテレポートしています...");
                                    ChestGuiManager::CloseInventory($p, $p->x, $p->y, $p->z);
                                    $this->getScheduler()->scheduleDelayedTask(new TeleportTask($p, $this->getServer()->getLevelByName("public")->getSafeSpawn()), 20);
                                }
                                $ev->setCancelled();
                                return;
                        }
                        $ev->setCancelled();
                        return;
                    }
                }
            }
        }
    }

    /**
     * @param Player $p
     * @return array
     */
    public static function getGatyaStrage(Player $p)
    {
        if (!self::getMain()->gatyaStrage->exists($p->getName()))
        {
            return [];
        }
        $strage = self::getMain()->gatyaStrage->get($p->getName());
        return $strage;
    }

    /**
     * @param Player $p
     * @param array $data
     */
    public static function setGatyaStrage(Player $p ,array $data)
    {
        self::getMain()->gatyaStrage->set($p->getName() ,$data);
    }

    public function onClose(InventoryCloseEvent $ev)
    {
        ChestGuiManager::onClose($ev);
    }

    public static function getMain(): main
    {
        return self::$main;
    }
}