<?php


namespace ree_jp\stackStorage\command;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\stackStorage\api\StackStorageAPI;

class StackStorageCommand extends Command
{
	public function __construct()
	{
		parent::__construct('stackstorage', 'StackStorage Command', '/stackstorage', ['st']);
		$this->setPermission("command.stackstorage");
		$this->setPermissionMessage('§cSet permissions from \'plugin.yml\' to \'true\' to allow use without permissions');
	}

	/**
	 * @inheritDoc
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args)
	{
		if (!$sender instanceof Player) {
			$sender->sendMessage(TextFormat::RED . '>> '. TextFormat::RESET. 'StackStorageCommand error');
			return;
		}
		if (!$this->testPermission($sender)) return;

		StackStorageAPI::getInstance()->sendGui($sender->getName());
	}
}