<?php

namespace RankSimple\Ibenrm01\commands;

use pocketmine\player\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\permission\PermissionManager;

use RankSimple\Ibenrm01\Main;

class RankCommand extends Command {

    /**
     * RankCommand constructor.
     * @param Main $plugin
     */
    public function __construct(private Main $plugin){
        parent::__construct("ranksimple", "Rank Commands", "/ranksimple [help]", ['ranks', 'rank']);
    }

    /**
     * @param CommandSender $player
     * @param string $label
     * @param array $args
     * 
     * @return bool
     */
    public function execute(CommandSender $player, string $label, array $args): bool {
        if(!isset($args[0])){
            $player->sendMessage("§cUsage: §7/ranksimple [help], /ranks [help], /rank [help]");
            return false;
        }
        switch($args[0]){
            case "help":
                $player->sendMessage("§a> RankSimple Commands:\n".
                "§7/ranksimple [help] §b- show all commands\n".
                "§7/ranksimple create §b- show a UI to create a rank\n".
                "§7/ranksimple delete [rank_name] §b- delete/remove a ranks\n".
                "§7/ranksimple list §b- show all ranks\n".
                "§7/ranksimple sets [rank_name] [player] [days] [hours] [minutes] [seconds] §b- set a player rank\n".
                "§7/ranksimple shop §b- show a shop");
                break;
            case "create":
            case "make":
                if(!$player instanceof Player){
                    $player->sendMessage("§cYou must be a player to use this command!");
                    return false;
                }
                if(!$this->plugin->getServer()->isOp($player->getName())){
                    if(!$this->testPermissionSilent($player, "ranksimple.create")){
                        $player->sendMessage("§cYou don't have permission to use this command!");
                        return false;
                    }
                }
                $this->plugin->getMenu()->createRank($player);
                break;
            case "delete":
            case "remove":
                if(!$player instanceof Player){
                    $player->sendMessage("§cYou must be a player to use this command!");
                    return false;
                }
                if(!$this->plugin->getServer()->isOp($player->getName())){
                    if(!$this->testPermissionSilent($player, "ranksimple.delete")){
                        $player->sendMessage("§cYou don't have permission to use this command!");
                        return false;
                    }
                }
                if(!isset($args[1])){
                    $player->sendMessage("§cUsage: §7/ranksimple delete [rank_name]");
                    return false;
                }
                $player->sendMessage($this->plugin->getDatabase()->removeRank($args[1]));
                break;
            case "sets":
            case "set":
                if(!$player instanceof Player){
                    $player->sendMessage("§cYou must be a player to use this command!");
                    return false;
                }
                if(!$this->plugin->getServer()->isOp($player->getName())){
                    if(!$this->testPermissionSilent($player, "ranksimple.sets")){
                        $player->sendMessage("§cYou don't have permission to use this command!");
                        return false;
                    }
                }
                if(!isset($args[1])){
                    $player->sendMessage("§cUsage: §7/ranksimple sets [player] [rank_name] [type=permanent/temporary] [days] [hours] [minutes] [seconds]");
                    return false;
                }
                if(!isset($args[2])){
                    $player->sendMessage("§cUsage: §7/ranksimple sets [player] [rank_name] [type=permanent/temporary] [days] [hours] [minutes] [seconds]");
                    return false;
                }
                if(!isset($args[3])){
                    $player->sendMessage("§cUsage: §7/ranksimple sets [player] [rank_name] [type=permanent/temporary] [days] [hours] [minutes] [seconds]");
                    return false;
                }
                if(!$this->plugin->getDatabase()->isRank($args[2])){
                    $player->sendMessage("§cRank §d{$args[2]} doesn't exist in configurasi, §b/ranksimple create!");
                }
                $target = $this->plugin->getPurePerms()->getPlayer($args[1]);
                $days = isset($this->plugin->ranks['rank'][$args[2]]["cooldown"]["days"]) ? $this->plugin->ranks['rank'][$args[2]]['cooldown']['days'] : 0;
                $hours = isset($this->plugin->ranks['rank'][$args[2]]["cooldown"]["hours"]) ? $this->plugin->ranks['rank'][$args[2]]['cooldown']['hours'] : 0;
                $minutes = isset($this->plugin->ranks['rank'][$args[2]]["cooldown"]["minutes"]) ? $this->plugin->ranks['rank'][$args[2]]['cooldown']['minutes'] : 1;
                $seconds = isset($this->plugin->ranks['rank'][$args[2]]["cooldown"]["seconds"]) ? $this->plugin->ranks['rank'][$args[2]]['cooldown']['seconds'] : 0;
                    $cooldown = [
                        "days" => isset($args[4]) ? $args[4] : $days,
                        "hours" => isset($args[5]) ? $args[5] : $hours,
                        "minutes" => isset($args[6]) ? $args[6] : $minutes,
                        "seconds" => isset($args[7]) ? $args[7] : $seconds
                    ];
                    $this->plugin->getDatabase()->setupRank($target, $args[2], ucfirst($args[3]), $cooldown);
                break;
            case "shop":
                if(!$player instanceof Player){
                    $player->sendMessage("§cYou must be a player to use this command!");
                    return false;
                }
                $this->plugin->getMenu()->shopMenu($player);
                break;
            case "version":
            case "ver":
                $player->sendMessage("§a> RankSimple Version:\n".
                "§7Name: §b{$this->plugin->getDescription()->getName()}\n".
                "§7Author: §b[{$this->getAuthor()}]\n".
                "§7Api: §b[{$this->getApi()}]\n".
                "§7Version: §b{$this->plugin->getDescription()->getVersion()}");
                break;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getAuthor(): string {
        return implode(", ", $this->plugin->getDescription()->getAuthors());
    }

    /**
     * @return string
     */
    public function getApi(): string {
        return implode(", ", $this->plugin->getDescription()->getCompatibleApis());
    }
}