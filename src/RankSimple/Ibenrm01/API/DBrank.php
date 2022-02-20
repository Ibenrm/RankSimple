<?php

namespace RankSimple\Ibenrm01\API;

use pocketmine\player\{
    Player, IPlayer
};

use RankSimple\Ibenrm01\Main;

class DBrank {

    /**
     * DBrank constructor.
     * @param Main $plugin
     */
    public function __construct(private Main $plugin) {
    }

    /**
     * @return array
     */
    public function countTime($time): array{
        $time = $time - time();
        $days = floor($time / 86400);
        $time %= 86400;
        $hours = floor($time / 3600);
        $time %= 3600;
        $minutes = floor($time / 60);
        $time %= 60;
        $seconds = floor($time);
        return [
            "days" => (int)$days,
            "hours" => (int)$hours,
            "minutes" => (int)$minutes,
            "seconds" => (int)$seconds
        ];
    }

    /**
     * @param Player $player
     * @return string
     */
    public function getRanks(Player $player): string{
        return (string)$this->plugin->getPurePerms()->getUserDataMgr()->getGroup($player);
    }

    /**
     * @param Player $player
     * @return string
     */
    public function getRank(Player $player): string{
        if(!isset($this->plugin->ranks['player'][$player->getName()]['rank'])){
            return (string)$this->plugin->getConfig()->get("default-rank");
        }
        return (string)$this->plugin->ranks['player'][$player->getName()]['rank'];
    }

    /**
     * @param string $rank
     * @return bool
     */
    public function isRank(string $rank): bool {
        if(!isset($this->plugin->ranks['rank'][$rank])){
            return false;
        }
        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function typeRank(Player $player): bool {
        if($this->getRank($player) == $this->plugin->getConfig()->get("default-rank")){
            return false;
        }
        if($this->plugin->ranks['player'][$player->getName()]['type'] == "Permanent"){
            return true;
        }
        return false;
    }

    /**
     * @param string $rank
     * @return array
     */
    public function structureRank(string $rank): array {
        return $this->plugin->ranks["rank"][$rank];
    }

    /**
     * @param string $rank
     * @param string $type
     * @param array $database
     * 
     * @return string
     */
    public function createRank(string $rank, string $type = "Permanent", array $database = []): string {
        if(isset($this->plugin->ranks['rank'][$rank])){
            return $this->plugin->replace($this->plugin->lang->get("rank-create.already"), [
                "rank" => $rank
            ]);
        }
        $this->plugin->ranks['rank'][$rank] = [
            "type" => ucfirst((string)$type),
            "icon" => isset($database['icon']) ? $database['icon'] : "",
            "price" => isset($database['price']) ? $database['price'] : 1000,
            "cooldown" => [
                "days" => isset($database["days"]) ? $database["days"] : 0,
                "hours" => isset($database["hours"]) ? $database["hours"] : 0,
                "minutes" => isset($database["minutes"]) ? $database["minutes"] : 0,
                "seconds" => isset($database["seconds"]) ? $database["seconds"] : 0
            ]
        ];
        return $this->plugin->replace($this->plugin->lang->get("rank-create.success"), [
            "rank" => $rank,
            "type" => $type,
            "price" => isset($database['price']) ? $database['price'] : 1000,
            "days" => isset($database['days']) ? $database['days'] : 0,
            "hours" => isset($database['hours']) ? $database['hours'] : 0,
            "minutes" => isset($database['minutes']) ? $database['minutes'] : 0,
            "seconds" => isset($database['seconds']) ? $database['seconds'] : 0
        ]);
    }

    /**
     * @param string $rank
     * @return string
     */
    public function removeRank(string $rank): string{
        if(!isset($this->plugin->ranks['rank'][$rank])){
            return $this->plugin->replace($this->plugin->lang->get("rank-remove.not-found"), [
                "rank" => $rank
            ]);
        }
        foreach(array_keys($this->plugin->ranks['player']) as $player_name) :
            if($this->plugin->ranks['player'][$player_name]['rank'] == $rank){
                $group = $this->plugin->getPurePerms()->getGroup($this->plugin->getConfig()->get("default-rank"));
                $player = $this->plugin->getPurePerms()->getPlayer($player_name);
                if($player instanceof Player){
                    $this->plugin->getPurePerms()->getUserDataMgr()->setGroup($player, $group, null, -1);
                    unset($this->plugin->ranks['player'][$player_name]);
                }
            }
        endforeach;
        unset($this->plugin->ranks['rank'][$rank]);
        return $this->plugin->replace($this->plugin->lang->get("rank-remove.success"), [
            "rank" => $rank
        ]);
    }

    /**
     * @param IPlayer $player
     * @param string $rank
     * @param array $cooldown
     * 
     * @return bool
     */
    public function setupRank(IPlayer $player, string $rank, string $type = "Temporary", array $cooldown = []): bool {
        if(!isset($this->plugin->ranks['rank'][$rank])){
            if($player instanceof Player){
                $player->sendMessage($this->plugin->replace($this->plugin->lang->get("rank-setup.not-found"), [
                    "rank" => $rank
                ]));
            }
            return false;
        }
        if($type != "Temporary"){
            if($type != "Permanent"){
                if($player instanceof Player){
                    $player->sendMessage("§eRankSimple > §cInvalid Type");
                    return false;
                }
            }
        }
        $group = $this->plugin->getPurePerms()->getGroup($rank);
        $this->plugin->getPurePerms()->getUserDataMgr()->setGroup($player, $group, null, -1);
        $this->plugin->ranks['player'][$player->getName()] = [
            "rank" => $rank,
            "type" => ucfirst($type),
            "expired" => time() + (isset($cooldown['seconds']) ? $cooldown['seconds'] : 1) +
            (isset($cooldown['minutes']) ? $cooldown['minutes'] : 1) * 60 +
            (isset($cooldown['hours']) ? $cooldown['hours'] : 0) * 3600 +
            (isset($cooldown['days']) ? $cooldown['days'] : 0) * 86400
        ];
        if($player instanceof Player){
            $player->sendMessage($this->plugin->replace($this->plugin->lang->get("rank-setup.success.player"), [
                "rank" => $rank,
                "type" => $type,
                "days" => isset($cooldown['days']) ? $cooldown['days'] : $this->plugin->ranks['rank'][$rank]['cooldown']['days'],
                "hours" => isset($cooldown['hours']) ? $cooldown['hours'] : $this->plugin->ranks['rank'][$rank]['cooldown']['hours'],
                "minutes" => isset($cooldown['minutes']) ? $cooldown['minutes'] : $this->plugin->ranks['rank'][$rank]['cooldown']['minutes'],
                "seconds" => isset($cooldown['seconds']) ? $cooldown['seconds'] : $this->plugin->ranks['rank'][$rank]['cooldown']['seconds']
            ]));
        }
        foreach($this->plugin->getServer()->getOnlinePlayers() as $players){
            $players->sendMessage($this->plugin->replace($this->plugin->lang->get("rank-setup.success.another"), [
                "username" => $player->getName(),
                "rank" => $rank
            ]));
        }
        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function unsetRank(Player $player): bool {
        if(!isset($this->plugin->ranks['player'][$player->getName()]['rank'])){
            return false;
        }
        $player->sendMessage($this->plugin->replace($this->plugin->lang->get("rank-unset.success"), [
            "rank" => $this->plugin->ranks['player'][$player->getName()]['rank']
        ]));
        foreach($this->plugin->getServer()->getOnlinePlayers() as $players) :
            $players->sendMessage($this->plugin->replace($this->plugin->lang->get("rank-unset.success.another"), [
                "username" => $player->getName(),
                "rank" => $this->plugin->ranks['player'][$player->getName()]['rank']
            ]));
        endforeach;
        $group = $this->plugin->getPurePerms()->getGroup($this->plugin->getConfig()->get("default-rank"));
        $this->plugin->getPurePerms()->getUserDataMgr()->setGroup($player, $group, null, -1);
        unset($this->plugin->ranks['player'][$player->getName()]);
        return true;
    }

    /**
     * @param Player $player
     * @return array
     */
    public function getExpired(Player $player): array {
        if(!isset($this->plugin->ranks['player'][$player->getName()]['expired'])){
            return ["days" => 0, "hours" => 0, "minutes" => 0, "seconds" => 3];
        }
        if($this->plugin->getType($player) == "Permanent"){
            return ["days" => 32, "hours" => 0, "minutes" => 0, "seconds" => 0];
        }
        $time = $this->plugin->ranks['player'][$player->getName()]["expired"];
        $expired = $this->countTime($time);
        return $expired;
    }
}