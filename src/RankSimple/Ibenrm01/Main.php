<?php

namespace RankSimple\Ibenrm01;

use pocketmine\Server;
use pocketmine\player\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\event\{
    Listener, player\PlayerChatEvent, player\PlayerJoinEvent
};
use pocketmine\utils\Config;

use RankSimple\Ibenrm01\{
    RankExpired,
    commands\RankCommand, API\DBrank, API\menu\RankMenu
};
use onebone\economyapi\EconomyAPI;
use _64FF00\PurePerms\PurePerms;

class Main extends PluginBase implements Listener {

    /** @var array[] $ranks */
    public $ranks = [];

    /** @var array[] $lang */
    public $lang;

    /**
     * @return void
     */
    public function onEnable(): void {
        if($this->getServer()->getPluginManager()->getPlugin("PurePerms") === null){
            $this->getLogger()->error("PurePerms not found!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") === null){
            $this->getLogger()->error("EconomyAPI not found!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->initConfig();
        $this->initArray();
        $this->getScheduler()->scheduleRepeatingTask(new RankExpired($this), $this->getConfig()->get("refresh-rank.time") * 20);
        $this->getServer()->getCommandMap()->register("ranksimple", new RankCommand($this));
    }

    /**
     * @return void
     */
    public function onDisable(): void {
        if(!file_exists($this->getDataFolder()."/ranks/ranks.yml")) {return;}
        file_put_contents($this->getDataFolder()."/ranks/ranks.yml", yaml_emit($this->ranks));
    }

    /**
     * @return void
     */
    public function initConfig(): void {
        @mkdir($this->getDataFolder());
        if(!is_dir($this->getDataFolder()."/ranks/")){
            @mkdir($this->getDataFolder()."/ranks/");
        }
        if(!is_dir($this->getDataFolder()."/lang/")){
            @mkdir($this->getDataFolder(). "/lang/");
        }
        if(!is_dir($this->getDataFolder()."/icons/")){
            @mkdir($this->getDataFolder(). "/icons/");
        }
        $this->saveResource("config.yml");
        $this->saveResource("lang/language.yml");
    }

    /**
     * @return void
     */
    public function initArray(): void{
        if(!file_exists($this->getDataFolder()."/ranks/ranks.yml")){
            new Config($this->getDataFolder()."/ranks/ranks.yml", Config::YAML);
            $this->ranks = yaml_parse(file_get_contents($this->getDataFolder()."/ranks/ranks.yml"));
        } else {
            $this->ranks = yaml_parse(file_get_contents($this->getDataFolder()."/ranks/ranks.yml"));
        }
        $this->lang = new Config($this->getDataFolder()."/lang/language.yml", Config::YAML);
    }

    /**
     * @return DBrank
     */
    public function getDatabase(): DBrank {
        return new DBrank($this);
    }

    /**
     * @return PurePerms
     */
    public function getPurePerms(): PurePerms{
        return $this->getServer()->getPluginManager()->getPlugin("PurePerms");
    }

    public function getEconomyS() : EconomyAPI {
        return $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
    }

    /**
     * @return RankMenu
     */
    public function getMenu(): RankMenu {
        return new RankMenu($this);
    }

    /**
     * @param string $message
     * @param array $keys
     * 
     * @return string
     */
    public function replace(string $message, array $keys): string{
        foreach($keys as $word => $value){
            $message = str_replace("{".strtolower($word)."}", $value, $message);
        }
        return $message;
    }

    /**
     * @param PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $params = explode(" ", $event->getMessage());
        switch($params[0]){
            case "!myrank":
            case "!statusrank":
            case "!statsrank":
                $event->cancel();
                if($this->getDatabase()->getRank($player) == $this->getConfig()->get("default-rank")) {$player->sendMessage($this->lang->get("you.dont.have.ranks")); break;}
                $time = $this->getDatabase()->getExpired($player);
                $player->sendMessage("§aYour rank is: ".$this->getDatabase()->getRank($player)."\n".
                "§aType: §f".$this->getType($player)."\n".
                "§7Expired: {$time['days']}d {$time['hours']}h {$time['minutes']}m {$time['seconds']}s");
                break;
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if(!isset($this->ranks['player'])) {return;}
        foreach(array_keys($this->ranks['player']) as $player_name){
            if($player_name == $player->getName()){
                if(!$this->getDatabase()->isRank($this->ranks['player'][$player_name]['rank'])){
                    $rank_name = $this->ranks['player'][$player_name]['rank'];
                    $player->sendMessage("§cRank §d{$rank_name}§c is deleted!");
                    unset($this->ranks['player'][$player_name]);
                    $group = $this->getPurePerms()->getGroup($this->getConfig()->get("default-rank"));
                    $this->getPurePerms()->getUserDataMgr()->setGroup($player, $group, null, -1);
                    break;
                } else {
                    if($this->getDatabase()->getRanks($player) != $this->ranks['player'][$player_name]['rank']){
                        $group = $this->getPurePerms()->getGroup($this->ranks['player'][$player_name]['rank']);
                        $this->getPurePerms()->getUserDataMgr()->setGroup($player, $group, null, -1);
                    }
                }
            }
        }
    }

    /**
     * @param Player $player
     * @return string
     */
    public function getType(Player $player): string{
        if($this->getDatabase()->typeRank($player)){
            return "Permanent";
        } else {
            return "Temporary";
        }
    }
}