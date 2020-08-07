<?php


namespace ree_jp\stackStorage\command;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\StackStorageAPI;

class StackStorageCommand extends PluginCommand
{
	public function __construct(Plugin $owner)
	{
		parent::__construct('stackstorage', $owner);
		$this->setPermission("stackstorage.command");
		$this->setPermissionMessage('§cSet permissions from \'plugin.yml\' to \'true\' to allow use without permissions');
		$this->setAliases(["st"]);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorageCommand error');
			return;
		}
		if (!$this->testPermission($sender)) return;

		StackStorageAPI::getInstance()->sendGui($sender->getName());
	}
}
