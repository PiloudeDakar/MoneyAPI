<?php

declare(strict_types=1);

namespace PiloudeDakar\MoneyAPI;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;

class MListener implements Listener{

    public function onPlayerJoin(PlayerJoinEvent $event){
        $name = $event->getPlayer()->getName();
        MoneyAPI::getInstance()->getMoneyData()->setNested('players.' . $name, MoneyAPI::getInstance()->getBasicAmount());
        MoneyAPI::getInstance()->getMoneyData()->save();
    }
}