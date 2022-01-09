<?php

declare(strict_types=1);

namespace PiloudeDakar\MoneyAPI;

use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class UnbanningTask extends Task{

    public function onRun(): void
    {
        $banned = new Config(MoneyAPI::getInstance()->getDataFolder() . 'moneys/basic.json', Config::JSON);
        $bans = $banned->getAll();
        foreach ($bans as $key => $time) if ($time < time()) $banned->remove($key);
        $banned->save();
    }
}