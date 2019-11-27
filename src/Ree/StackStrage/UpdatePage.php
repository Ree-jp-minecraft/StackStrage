<?php


namespace Ree\StackStrage;


use pocketmine\scheduler\Task;
use Ree\seichi\PlayerTask;
use Ree\StackStrage\Virchal\GatyaStrage;
use Ree\StackStrage\Virchal\StackStrage;

class UpdatePage extends Task
{
    /**
     * @var PlayerTask
     */
    private $pT;

    public function __construct(PlayerTask $pT)
    {
        $this->pT = $pT;
    }

    public function onRun(int $currentTick)
    {
        if ($this->pT->s_chestInstance instanceof StackStrage)
        {
            $this->pT->s_chestInstance->setPage();
        }elseif ($this->pT->s_chestInstance instanceof GatyaStrage)
        {
            $this->pT->s_chestInstance->setPage();
        }else{
            $this->pT->errer("line" . __LINE__ . " Strageにアクセスできません", $this);
        }
    }
}