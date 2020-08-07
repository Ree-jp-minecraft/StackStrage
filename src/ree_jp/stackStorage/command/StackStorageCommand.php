<?php


namespace ree_jp\stackStorage\command;


use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\StackStorageAPI;

class StackStorageCommand extends PluginCommand
{
	public function __construct(Plugin $owner)
	{
		parent::__construct('stackstorage', $owner);
		$this->setUsage("/stackstorage <name>");
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

		if ($sender->hasPermission("stackstorage.command.user") && isset($args[0])) {
			if (!Server::getInstance()->getPlayer($args[0]) instanceof Player) {
				$sender->sendMessage("$args[0] not login");
				return;
			}
			StackStorageAPI::getInstance()->sendGui($args[0]);
		}else{
			StackStorageAPI::getInstance()->sendGui($sender->getName());
		}
	}
}
