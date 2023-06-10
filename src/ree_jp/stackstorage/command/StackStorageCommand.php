<?php


namespace ree_jp\stackstorage\command;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\stackstorage\api\StackStorageAPI;
use ree_jp\stackstorage\migrate\MigrateV2;
use ree_jp\stackstorage\StackStoragePlugin;

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
            if (!$sender instanceof ConsoleCommandSender) {
                $sender->sendMessage(TextFormat::RED . '>> ' . TextFormat::RESET . 'StackStorageCommand error');
                return;
            }

            if (isset($args[0])) {
                switch ($args[0]) {
                    case "migrate-v2":
                        StackStoragePlugin::$instance->getLogger()->warning("Be sure to back up your data! \n Starts in 5 seconds");
                        StackStoragePlugin::$instance->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
                            MigrateV2::migrate();
                        }), 20 * 5);
                        break;
                    default:
                        $sender->sendMessage("You can use this command to migrate your Stack Storage data\n"
                            . "Please back up your data before migrating data.\n"
                            . "See the Stack Storage poggit page for detailed instructions.");
                }
            }
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
