<?php

/*
 * Copyright (c) 2019 tedo0627
 *
 *Permission is hereby granted, free of charge, to any person obtaining a copy
 *of this software and associated documentation files (the "Software"), to deal
 *in the Software without restriction, including without limitation the rights
 *to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *copies of the Software, and to permit persons to whom the Software is
 *furnished to do so, subject to the following conditions:
 *
 *The above copyright notice and this permission notice shall be included in all
 *copies or substantial portions of the Software.
 *
 *THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *SOFTWARE.
 *
 *
 * Copyright (c) 2019 ツキミヤ
 *
 *Permission is hereby granted, free of charge, to any person obtaining a copy
 *of this software and associated documentation files (the "Software"), to deal
 *in the Software without restriction, including without limitation the rights
 *to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *copies of the Software, and to permit persons to whom the Software is
 *furnished to do so, subject to the following conditions:

 *The above copyright notice and this permission notice shall be included in all
 *copies or substantial portions of the Software.

 *THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *SOFTWARE.
 */

namespace Ree\StackStrage;

use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\Task;

use pocketmine\block\Block;
use pocketmine\math\Vector3;

class ChestGuiManager
{
    const stackstrage = "StackStrage";
    const worldserect = "WorldSerect";
    const skilselect = "SkilSelect";
    const dust = "dust";

    /**
     * @param InventoryCloseEvent $ev
     */
    static public function onClose($ev)
    {
        $p = $ev->getPlayer();
        $n = $p->getName();
        $pT = \Ree\seichi\main::getpT($n);

        if (!$pT->s_chestInstance)
        {
            return;
        }

        if ($pT->s_open === true)
        {
            $pT->s_open = false;
            return;
        }

        $pT->s_chestInstance->getInstance()->clearAll();

        if (isset($pT->s_gui)) {
            $x = $pT->s_gui[0];
            $y = $pT->s_gui[1];
            $z = $pT->s_gui[2];

            $p->level->sendBlocks([$p], [$p->level->getBlockAt($x, $y, $z)]);
            $p->level->sendBlocks([$p], [$p->level->getBlockAt($x + 1, $y, $z)]);
        }

        $pT->s_gui = NULL;
    }

    /**
     * @param Player $p
     * @param int $x
     * @param int $y
     * @param int $z
     */
    static public function CloseInventory(Player $p,int $x,int $y,int $z)
    {
        $vector = new Vector3($x, (int)$p->y + 1, $z);
        $p->getLevel()->setBlock($vector, Block::get(90, 0));
        main::getMain()->getScheduler()->scheduleDelayedTask(new CloseTask($p, $vector), 4);
        \Ree\seichi\main::getpT($p->getName())->updateInventory();
    }
}

class CloseTask extends Task
{
    /**
     * @var Player
     */
    private $p;

    /**
     * @var Vector3
     */
    private $vector3;

    public function __construct(Player $p,Vector3 $vector3)
    {
        $this->p = $p;
        $this->vector3 = $vector3;
    }

    public function onRun(int $currentTick)
    {
        $this->p->getLevel()->setBlock($this->vector3, Block::get(Item::AIR, 0));
    }
}