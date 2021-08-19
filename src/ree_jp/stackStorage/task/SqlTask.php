<?php

namespace ree_jp\stackStorage\task;

use Exception;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use ree_jp\stackStorage\sql\StackStorageHelper;

class SqlTask extends AsyncTask
{
    private string $database;
    private string $host;
    private string $db;
    private string $user;
    private string $pass;
    private StackStorageHelper $helper;

    public function __construct(string $database, string $host, string $db, string $user, string $pass)
    {
        $this->database = $database;
        $this->host = $host;
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * @throws Exception
     */
    public function onRun()
    {
        StackStorageHelper::$instance = new StackStorageHelper($this->database, $this->host, $this->db, $this->user, $this->pass);
    }

    public function onCompletion(Server $server)
    {
        $server->getLogger()->info('[StackStorage] ready sql');
    }
}
