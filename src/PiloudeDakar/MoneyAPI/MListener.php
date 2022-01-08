<?php

declare(strict_types=1);

namespace PiloudeDakar\MoneyAPI;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;

class MListener implements Listener{

    public function onPlayerJoin(PlayerJoinEvent $event){
        $name = $event->getPlayer()->getName();
        $moneyData = new Config(MoneyAPI::getInstance()->getDataFolder(), Config::JSON);
        $moneyData->setNested('players.' . $name, 0);
    }
}