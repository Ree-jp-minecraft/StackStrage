<?php


namespace ree_jp\StackStorage;

use Exception;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\GuiAPI;
use ree_jp\stackStorage\api\StackStorageAPI;
use ree_jp\stackStorage\command\StackStorageCommand;
use ree_jp\stackStorage\listener\EventListener;
use ree_jp\stackStorage\sql\Queue;
use ree_jp\stackStorage\sql\StackStorageHelper;

class StackStoragePlugin extends PluginBase
{
    const IS_BETA_VERSION = false;

    public static StackStoragePlugin $instance;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getCommandMap()->register('stackstorage', new StackStorageCommand($this));
        self::$instance = $this;
        GuiAPI::$instance = new GuiAPI();
        StackStorageAPI::$instance = new StackStorageAPI();
        StackStorageHelper::$instance = new StackStorageHelper($this, $this->getDataFolder());
    }

    public function onDisable()
    {
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            if (StackStorageAPI::$instance->isOpen($p->getName())) try {
                GuiAPI::$instance->closeGui($p->getName());
            } catch (Exception $ex) {
                Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorage error');
                Server::getInstance()->getLogger()->error(TextFormat::RED . '>> ' . TextFormat::RESET . 'Details : ' . $ex->getMessage() . $ex->getFile() . $ex->getLine());
                return;
            }
        }
        Queue::doAllCache();
        $timer = 0;
        while (!Queue::isEmpty() && $timer < 30) {
            $timer++;
            sleep(1);
        }
        if ($timer >= 30) {
            $this->getLogger()->critical('The data could not be saved');
        }
        StackStorageHelper::$instance->close();
    }

    /**
     * @return string
     */
    public static function getVersion(): string
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
