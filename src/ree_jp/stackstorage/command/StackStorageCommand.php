<?php


namespace ree_jp\stackstorage\command;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\stackstorage\api\StackStorageAPI;

class StackStorageCommand extends Command implements PluginOwned
{
    public function __construct(private readonly Plugin $owner)
    {
        parent::__construct('stackstorage', "simple storage", null, ["st"]);
        $this->setPermission("stackstorage.command.my");
        $this->setPermissionMessage('§cSet permissions from \'plugin.yml\' to \'true\' to allow use without permissions');
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorageCommand error');
            return;
        }
        if (!$this->testPermission($sender)) return;

        if (isset($args[0])) {
            if ($sender->hasPermission("stackstorage.command.user")) {
                $p = Server::getInstance()->getPlayerExact($args[0]);
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

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
