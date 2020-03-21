<?php


namespace Ree\StackStorage;

use pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use ree\stackStorage\api\StackStorageAPI;
use ree\stackStorage\command\StackStorageCommand;
use ree\stackStorage\listener\EventListener;

class StackStoragePlugin extends PluginBase
{
	const IS_BETA_VERSION = true;

	/**
	 * @var StackStoragePlugin
	 */
	public static $instance;

	public function onEnable()
	{
		$this->getLogger()->info('loading now...');
		if (self::IS_BETA_VERSION) {
			$this->getLogger()->warning('This plugin is BETA version');
		}
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
		$this->getServer()->getCommandMap()->register('stackstorage', new StackStorageCommand());
		self::$instance = $this;
		$this->getLogger()->info('Complete');
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
			return 'Version-β' . self::getMain()->getDescription()->getVersion() ."\n\n\n\n\n\n\n\n\n\n\n" . ' by-' . implode(self::getMain()->getDescription()->getAuthors(), ',');
		} else {
			return 'Version-' . self::getMain()->getDescription()->getVersion();
		}
	}

	public static function getMain(): StackStoragePlugin
	{
		return self::$instance;
	}
}