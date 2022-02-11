<?php


namespace ree_jp\stackstorage;

use JetBrains\PhpStorm\Pure;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use poggit\libasynql\SqlError;
use ree_jp\stackstorage\api\StackStorageAPI;
use ree_jp\stackstorage\command\StackStorageCommand;
use ree_jp\stackstorage\listener\EventListener;
use ree_jp\stackstorage\sql\Queue;
use ree_jp\stackstorage\sql\StackStorageHelper;

class StackStoragePlugin extends PluginBase
{
    const IS_BETA_VERSION = false;

    public static StackStoragePlugin $instance;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getCommandMap()->register('stackstorage', new StackStorageCommand($this));
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            Queue::doAllCache();
        }), $this->getConfig()->get("cache_interval") * 20);
        self::$instance = $this;
        StackStorageAPI::$instance = new StackStorageAPI();
        StackStorageHelper::$instance = new StackStorageHelper($this, $this->getDataFolder(), $this->getConfig()->get("init_func", true));

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        if ($this->getConfig()->get("problem_auto_solution")) {
            StackStorageHelper::$instance->getUser(function (array $rows) {
                foreach ($rows as $row) {
                    StackStorageAPI::$instance->solutionProblem($row["xuid"]);
                }
            }, function (SqlError $error) {
                $this->getLogger()->warning("problem auto solution : " . $error->getErrorMessage());
            });
        }
    }

    public function onDisable(): void
    {
        Queue::doAllCache();
        StackStorageHelper::$instance->close();
    }

    /**
     * @return string
     */
    #[Pure] public static function getVersion(): string
    {
        if (self::IS_BETA_VERSION) {
            return 'Version-β' . self::getMain()->getDescription()->getVersion();
        } else {
            return 'Version-' . self::getMain()->getDescription()->getVersion();
        }
    }

    public static function getMain(): StackStoragePlugin
    {
        return self::$instance;
    }
}
