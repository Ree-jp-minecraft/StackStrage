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
        $this->setPermission("stackstorage.command.my");
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

        if (isset($args[0])) {
            if ($sender->hasPermission("stackstorage.command.user")) {
                $p = Server::getInstance()->getPlayer($args[0]);
                if ($p instanceof Player) {
                    StackStorageAPI::$instance->sendGui($sender, $p->getXuid());
                } else StackStorageAPI::$instance->sendGui($sender, $args[0]);
            } else {
                $sender->sendMessage('not allow permission stackstorage.command.user');
            }
        } else {
            StackStorageAPI::$instance->sendGui($sender, $sender->getXuid());
        }
    }
}
