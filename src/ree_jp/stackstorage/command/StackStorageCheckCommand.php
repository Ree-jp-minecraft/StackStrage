<?php

namespace ree_jp\stackstorage\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use poggit\libasynql\SqlError;
use ree_jp\stackstorage\api\StackStorageAPI;
use ree_jp\stackstorage\sql\StackStorageHelper;

class StackStorageCheckCommand extends Command implements PluginOwned
{
    public function __construct(private Plugin $owner)
    {
        parent::__construct("stackstoragecheck", "storage checker", null, ["st-check"]);
        $this->setPermission("stackstorage.command.check");
        $this->setPermissionMessage('§cSet permissions from \'plugin.yml\' to \'true\' to allow use without permissions');
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->testPermission($sender)) return;

        if (!isset($args[0])) {
            $sender->sendMessage("/stackstoragecheck [xuid | all]");
            return;
        }

        if (strtolower($args[0]) === "all") {
            $sender->sendMessage("Check all user data");
            StackStorageHelper::$instance->getUser(function (array $rows) {
                foreach ($rows as $row) {
                    StackStorageAPI::$instance->solutionProblem($row["xuid"]);
                }
            }, function (SqlError $error) use ($sender) {
                $sender->sendMessage("problem auto solution : " . $error->getErrorMessage());
            });
        } else {
            $sender->sendMessage("Check user data for $args[0]");
            StackStorageAPI::$instance->solutionProblem($args[0]);
        }

    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
