<?php


namespace ree_jp\StackStorage;

use pocketmine\plugin\PluginBase;
use ree_jp\stackStorage\command\StackStorageCommand;
use ree_jp\stackStorage\listener\EventListener;

class StackStoragePlugin extends PluginBase
{
	const IS_BETA_VERSION = true;

	/**
	 * @var StackStoragePlugin
	 */
	public static $instance;

	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		$this->getServer()->getCommandMap()->register('stackstorage', new StackStorageCommand());
		self::$instance = $this;;
	}

	public function onDisable()
	{
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