<?php


namespace ree_jp\StackStorage;

use pocketmine\plugin\PluginBase;
use ree_jp\stackStorage\api\GuiAPI;
use ree_jp\stackStorage\api\StackStorageAPI;
use ree_jp\stackStorage\command\StackStorageCommand;
use ree_jp\stackStorage\listener\EventListener;
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
