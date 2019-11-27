<?php


namespace Ree\StackStrage;


use pocketmine\Player;
use pocketmine\scheduler\Task;

class ChestTask extends Task
{
    /**
     * @var Player
     */
    private $p;

    private $instance;

    public function __construct(Player $p, $in)
    {
        $this->p = $p;
        $this->instance = $in;
    }

    public function onRun(int $currentTick)
    {
        $this->p->addWindow($this->instance);
    }
}