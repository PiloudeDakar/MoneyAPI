<?php

declare(strict_types=1);

namespace PiloudeDakar\MoneyAPI;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\utils\Config;
use function pocketmine\server;

class MoneyAPI extends PluginBase
{

    private Config $moneyData;
    private Config $bannedData;

    private static MoneyAPI $MoneyAPI;

    public const RETURN_NICE = -1;
    public const RETURN_NO_ENOUGH_MONEY = -2;
    public const RETURN_RECEIVER_NOT_FOUNDED = -3;
    public const RETURN_SENDER_NOT_FOUNDED = -4;
    public const RETURN_BANNED = -5;
    public const RETURN_ERROR = -6;

    protected function onEnable(): void
    {
        @mkdir($this->getDataFolder() . 'moneys');
        if (!file_exists($this->getDataFolder() . 'moneys/basic.json')) {
            fopen($this->getDataFolder() . 'moneys/basic.json', 'x+');
            file_put_contents($this->getDataFolder() . 'moneys/basic.json', DefaultConfigs::DEVICE_JSON);
        }
        if (!file_exists($this->getDataFolder() . 'banned.yml')) {
            fopen($this->getDataFolder() . 'banned.yml', 'x+');
            file_put_contents($this->getDataFolder() . 'banned.yml', DefaultConfigs::YAML);
        }
        $this->getServer()->getPluginManager()->registerEvents(new MListener(), $this);
        $this->bannedData = new Config($this->getDataFolder() . 'banned.yml', Config::YAML);
        $this->moneyData = new Config($this->getDataFolder() . 'moneys/basic.json', Config::JSON);
        self::$MoneyAPI = $this;
        $this->getScheduler()->scheduleRepeatingTask(new UnbanningTask(), 1200);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch ($command) {
            case 'pay':
                if ($sender instanceof Player) {
                    if (!isset($args[0]) or !isset($args[1])) {
                        $sender->sendMessage('§cUsage : /pay [string : player] [int : amount]');
                        return true;
                    }
                    $args = [strval($args[0]), intval($args[1])];
                    if ($this->isBan($sender->getName()) or $this->isBan($args[0])){
                        $sender->sendMessage('§cImpossible : one of the players is banned from MoneyAPI');
                        return true;
                    }
                    $this->pay($sender, $args[0], $args[1]);
                }
                break;
            case 'topmoney':
                $topMoney = $this->topMoney(5);
                if ($topMoney == self::RETURN_ERROR){
                    $sender->sendMessage("§cNo enough players to display the leaderboard");
                    return true;
                }
                $arrayKeys = array_keys($topMoney);
                $sender->sendMessage("§2Money leaderboard :
§c1 - §1$arrayKeys[0] : §9" . $topMoney[$arrayKeys[0]] . "
§c2 - §1$arrayKeys[1] : §9" . $topMoney[$arrayKeys[1]] . "
§c3 - §1$arrayKeys[2] : §9" . $topMoney[$arrayKeys[2]] . "
§c4 - §1$arrayKeys[3] : §9" . $topMoney[$arrayKeys[3]] . "
§c5 - §1$arrayKeys[4] : §9" . $topMoney[$arrayKeys[4]]);
                break;
            case 'mymoney':
                if ($sender instanceof Player) $sender->sendMessage('§9 You have : §a' . $this->getBalance($sender->getName()) . '§9$');
                break;
            case 'getmoney':
                if (!isset($args[0])) {
                    $sender->sendMessage('§cUsage : /getmoney [string : player]');
                    return true;
                }
                $args[0] = strval($args[0]);
                $amount = $this->getBalance($args[0]);
                if ($amount == self::RETURN_RECEIVER_NOT_FOUNDED) {
                    $sender->sendMessage('§cPlayer not founded.');
                    return true;
                }
                $sender->sendMessage("§9$args[0] have a balance of §1$amount §9$");
                break;
            case 'addmoney':
                if (!isset($args[0]) or !isset($args[1])) {
                    $sender->sendMessage('§cUsage : /addmoney [string : player] [int : amount]');
                    return true;
                }
                $args = [strval($args[0]), intval($args[1])];
                if ($this->transaction(null, $args[0], $args[1]) == self::RETURN_RECEIVER_NOT_FOUNDED) {
                    $sender->sendMessage("§cThis player isn't registered on the server ( §3$args[0] §c) !");
                } else {
                    $sender->sendMessage("§9$args[0] §ahave been credited of §9$args[1]§a$. His new sold is §9" . $this->moneyData->getNested("players.$args[0]") . '§a$');
                }
                break;
            case 'removemoney':
                if (!isset($args[0]) or !isset($args[1])) {
                    $sender->sendMessage('§cUsage : /removemoney [string : player] [int : amount]');
                    return true;
                }
                $args = [strval($args[0]), intval($args[1])];
                $transaction = $this->transaction(null, $args[0], -$args[1]);
                if ($transaction == self::RETURN_RECEIVER_NOT_FOUNDED) {
                    $sender->sendMessage("§cThis player isn't registered on the server ( §3$args[0] §c) !");
                } elseif ($transaction == self::RETURN_NO_ENOUGH_MONEY) {
                    $sender->sendMessage("§4 $args[0] §c haven't enough money to be uncredited of §4$args[1]§c. His sold is §4" . $this->moneyData->getNested("players.$args[0]"));
                } else {
                    $sender->sendMessage("§9$args[0] §ahave been uncredited of §9$args[1]§a$. His new sold is §9" . $this->moneyData->getNested("players.$args[0]") . '§a$');
                }
                break;
            case 'clearmoney':
                if (!isset($args[0])) {
                    $sender->sendMessage('§cUsage : /clearmoney [string : player]');
                    return true;
                }
                $args[0] = strval($args[0]);
                $clear = $this->clearBalance($args[0]);
                if ($clear == self::RETURN_RECEIVER_NOT_FOUNDED) {
                    $sender->sendMessage("§cThis player isn't registered on the server ( §3$args[0] §c) !");
                    return true;
                }
                $sender->sendMessage("§9$args[0] §ahave been cleared of §9$clear §a$.");
                break;
            case 'setmoney':
                if (!isset($args[0]) or !isset($args[1])) {
                    $sender->sendMessage('§cUsage : /setmoney [string : player] [int : amount]');
                    return true;
                }
                $args = [strval($args[0]), intval($args[1])];
                if ($this->setBalance($args[0], $args[1]) == self::RETURN_RECEIVER_NOT_FOUNDED) {
                    $sender->sendMessage('§cPlayer not founded.');
                    return true;
                }
                $sender->sendMessage("§9$args[0]'s balance have been set to §a$args[1] §9$");
                break;
            case 'banmoney':

                if (!isset($args[0])) {
                    $sender->sendMessage('§aUsage : /banmoney [string : player] [int|null : duration] ["m"|"h"|"d"|null : duration type]');
                    return true;
                }
                if (!isset($args[1])) $args[1] = null;
                if (!isset($args[2])) $args[2] = null;
                $args = [strval($args[0]), intval($args[1]), strval($args[2])];
                $this->banBalance($args[0], $args[1], $args[2]);
                break;
            case 'unbanmoney':
                if (!isset($args[0])) {
                    $sender->sendMessage('§aUsage : /unbanmoney [string : player]');
                    return true;
                }
                $args[0] = strval($args[0]);
                $this->unbanBalance($args[0]);
        }
        return true;
    }


    #   ███╗░░░███╗░█████╗░███╗░░██╗███████╗██╗░░░██╗░░█████╗░██████╗░██╗
    #   ████╗░████║██╔══██╗████╗░██║██╔════╝╚██╗░██╔╝░██╔══██╗██╔══██╗██║
    #   ██╔████╔██║██║░░██║██╔██╗██║█████╗░░░╚████╔╝░░███████║██████╔╝██║
    #   ██║╚██╔╝██║██║░░██║██║╚████║██╔══╝░░░░╚██╔╝░░░██╔══██║██╔═══╝░██║
    #   ██║░╚═╝░██║╚█████╔╝██║░╚███║███████╗░░░██║░░░░██║░░██║██║░░░░░██║
    #   ╚═╝░░░░░╚═╝░╚════╝░╚═╝░░╚══╝╚══════╝░░░╚═╝░░░░╚═╝░░╚═╝╚═╝░░░░░╚═╝
    #
    #By PiloudeDakar

    public static function getInstance(): MoneyAPI
    {
        return self::$MoneyAPI;
    }

    public function canSubtractBalance(string|int $balance, int $subtraction): bool
    {
        if (is_string($balance)) {
            $balances = $this->moneyData->get('players');
            if (key_exists($balance, $balances)) {
                $balance = $balances[$balance];
            }
        }
        if ($balance >= $subtraction) {
            return true;
        } else {
            return false;
        }
    }

    public function pay(Player $sender, string $receiver, int $amount): int
    {
        $transaction = $this->transaction($sender->getName(), $receiver, $amount);
        switch ($transaction) {
            case self::RETURN_NICE:
                $sender->sendMessage('§aYou have successfully paid §2' . $receiver . ' §a( §2' . $amount . '§a$.');
                return self::RETURN_NICE;
            case self::RETURN_NO_ENOUGH_MONEY:
                $sender->sendMessage('§cYou haven\'t enough money !');
                return self::RETURN_NO_ENOUGH_MONEY;
            case self::RETURN_RECEIVER_NOT_FOUNDED:
                $sender->sendMessage('§cThis player isn\'t registered on the server ( §3' . $receiver . ' §c) !');
                return self::RETURN_RECEIVER_NOT_FOUNDED;
        }
        return self::RETURN_NICE;
    }

    public function topMoney(int $length = null): array|int{
        $balances = $this->moneyData->getNested('players');
        if ($length > count($balances)) return self::RETURN_ERROR;
        arsort($balances);
        return array_slice($balances, 0, $length, true);
    }

    public function getBalance(string $balance): int{
        $balances = $this->moneyData->get('players');
        if (key_exists($balance, $balances)) return $this->moneyData->getNested('players.' . $balance);
        return self::RETURN_RECEIVER_NOT_FOUNDED;
    }

    public function setBalance(string $balance, int $amount): int{
        $balances = $this->moneyData->get('players');
        if (!key_exists($balance, $balances)) return self::RETURN_RECEIVER_NOT_FOUNDED;
        $this->moneyData->setNested("players.$balance", $amount);
        $this->moneyData->save();
        return $balances[$balance];
    }

    public function clearPlayer(Player $player): int{
        return $this->setBalance($player->getName(), 0);
    }

    public function clearBalance(string $balance): int{
        return $this->setBalance($balance, 0);
    }

    public function banBalance(string $balance, int|null $duration = null, string|null $duration_type){
        $expiration = null;
        if (!is_null($duration) && !is_null($duration_type)) {
            $expiration = time();
            switch ($duration_type) {
                case 'm':
                    $expiration = $expiration + 60 * $duration;
                    break;
                case 'h':
                    $expiration = $expiration + 3600 * $duration;
                    break;
                case 'd':
                    $expiration = $expiration + 86400 * $duration;
                    break;
            }
            $this->bannedData->set($balance, $expiration);
            $this->bannedData->save();
        }
    }

    public function isBan(string $balance): bool{
        return $this->bannedData->exists($balance);
    }

    public function unbanBalance(string $balance){
        if ($this->bannedData->exists($balance)) $this->bannedData->remove($balance);
        $this->bannedData->save();
    }

    public function transaction(string|null $sender, string $receiver, int $amount): int
    {
        $balances = $this->moneyData->get('players');
        if ($sender !== null){
            if (!$this->canSubtractBalance($sender, $amount)) return self::RETURN_NO_ENOUGH_MONEY;
            if (!key_exists($sender, $balances)) return self::RETURN_SENDER_NOT_FOUNDED;
            $this->moneyData->setNested('players.' . $sender, $balances[$sender] - $amount);
        }
        if ($amount < 0 && !$this->canSubtractBalance($receiver, $amount)) return self::RETURN_NO_ENOUGH_MONEY;

        if (!key_exists($receiver, $balances)) return self::RETURN_RECEIVER_NOT_FOUNDED;
        $this->moneyData->setNested('players.' . $receiver, $balances[$receiver] + $amount);
        $this->moneyData->save();

        return self::RETURN_NICE;
    }
}