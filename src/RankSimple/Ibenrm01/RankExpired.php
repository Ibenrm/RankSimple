<?php

namespace RankSimple\Ibenrm01;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;

use RankSimple\Ibenrm01\Main;

class RankExpired extends Task {

    /**
     * RankExpired contructor.
     * @param Main $plugin
     */
    public function __construct(private Main $plugin) {
    }

    /**
     * @return void
     */
    public function onRun(): void {
        foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
            $rank = $this->plugin->getDatabase()->getRanks($player);
            if($this->getExpired($player, $rank)){
                $this->plugin->getDatabase()->unsetRank($player);
            }
        }
    }

    /**
     * @param Player $player
     * @param string $rank
     * @return bool
     */
    public function getExpired(Player $player, string $rank): bool {
        if($this->plugin->getDatabase()->getRank($player) != $this->plugin->getConfig()->get("default-rank")){
            if($this->plugin->getType($player) == "Temporary"){
                $expired = $this->plugin->getDatabase()->getExpired($player);
                if($expired["seconds"] <= -1){
                    return true;
                }
                return false;
            } elseif($this->plugin->getType($player) == "Permanent"){
                return false;
            }
        }
        return false;
    }
}