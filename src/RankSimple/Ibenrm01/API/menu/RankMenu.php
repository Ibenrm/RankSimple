<?php

namespace RankSimple\Ibenrm01\API\menu;

use pocketmine\player\Player;

use RankSimple\Ibenrm01\{
    Main, 
    library\jojoe77777\FormAPI\SimpleForm,
    library\jojoe77777\FormAPI\CustomForm,
    library\jojoe77777\FormAPI\ModalForm,
};

class RankMenu {

    /** @var array[] $database */
    private $database;

    /**
     * RankMenu constructor.
     * @param Main $plugin
     */
    public function __construct(private Main $plugin) {
    }

    /**
     * @param Player $player
     * 
     * @return void
     */
    public function shopMenu(Player $player) : void {
        if(!isset($this->plugin->ranks['rank'])){$player->sendMessage("§eRankSimple > §cRank not exists!"); return;}
        $ranklist[$player->getName()] = $this->plugin->ranks['rank'];
        $this->database[$player->getName()]['rank-1'] = $this->getRanks($player, $ranklist);
        $form = new SimpleForm(function(Player $player, int $data = null) {
            if($data === null or $data === 0){
                $player->sendMessage("§aThanks for open menu!");
                return;
            }
            $this->shopMenuACC($player, array_keys($this->database[$player->getName()]['rank-1'])[$data - 1], $this->plugin->ranks['rank'][array_keys($this->database[$player->getName()]['rank-1'])[$data - 1]]);
        });
        $form->setTitle($this->plugin->getConfig()->getAll()['forms.shop']['title']);
        $form->setContent($this->plugin->replace($this->plugin->getConfig()->getAll()['forms.shop']['content'], [
            "username" => $player->getName(),
            "money" => $this->plugin->getEconomyS()->myMoney($player)
        ]));
        $form->addButton($this->plugin->getConfig()->getAll()['forms.shop']['button-close']);
        foreach($this->database[$player->getName()]['rank-1'] as $key => $value){
            $form->addButton($this->plugin->replace($this->plugin->getConfig()->getAll()['forms.shop']['button'], [
                "rank" => $value['rank'],
                "price" => $value['price'],
            ]), 0, $value['icon']);
        }
        $form->sendToPlayer($player);
    }

    /**
     * @param Player $player
     * @param array $ranklist
     * 
     * @return array
     */
    private function getRanks(Player $player, array $ranklist) : array {
        $database = [];
        if(!empty($this->plugin->ranks['rank'])){
            foreach(array_keys($ranklist[$player->getName()]) as $rank){
                if($rank != $this->plugin->getPurePerms()->getUserDataMgr()->getGroup($player)){
                    if($this->antiDowngrade($player, $rank)){
                        $database[$rank] = ["rank" => $rank, "icon" => $this->plugin->ranks['rank'][$rank]['icon'], "price" => $this->plugin->ranks['rank'][$rank]['price'] - $this->upRank($player, $rank)];
                    }
                }
            }
        }
        return $database;
    }

    /**
     * @param Player $player
     * @param string $ranks_name
     * @param array $ranks
     * 
     * @return void
     */
    public function shopMenuACC(Player $player, string $ranks_name, array $ranks): void {
        $form = new ModalForm(function(Player $player, $data) use ($ranks_name, $ranks) {
            if($data === null){
                $player->sendMessage("§aThanks for open menu!");
                return;
            }
            switch($data){
                case 1:
                    $jumlah = $ranks['price'] - $this->upRank($player);
                    if($this->plugin->getEconomyS()->myMoney($player) >= $jumlah){
                        $this->plugin->getEconomyS()->reduceMoney($player, $jumlah);
                        $this->plugin->getDatabase()->setupRank($player, $ranks_name, $ranks['type'], $ranks['cooldown']);
                    }else{
                        $player->sendMessage($this->plugin->lang->get("not.enough.money"));
                    }
                    break;
                case 2:
                    $player->sendMessage("§aThanks for open menu!");
                    break;
            }
        });
        $form->setTitle($this->plugin->replace($this->plugin->getConfig()->getAll()['forms.shop.acc']['title'], [
            "rank" => $ranks_name
        ]));
        $form->setContent($this->plugin->replace($this->plugin->getConfig()->getAll()['forms.shop.acc']['content'], [
            "username" => $player->getName(),
            "money" => $this->plugin->getEconomyS()->myMoney($player),
            "rank" => $ranks_name,
            "type" => $ranks['type'],
            "price" => $ranks['price'] - $this->upRank($player),
            "days" => $ranks['cooldown']['days'],
            "hours" => $ranks['cooldown']['hours'],
            "minutes" => $ranks['cooldown']['minutes'],
            "seconds" => $ranks['cooldown']['seconds']
        ]));
        $form->setButton1("§aBuyying");
        $form->setButton2("§cCancel");
        $form->sendToPlayer($player);
    }

    /**
     * @param Player $player
     * 
     * @return void
     */
    public function createRank(Player $player): void {
        $ranks[$player->getName()] = $this->getAllRanks();
        $icons[$player->getName()] = $this->getAllIcon();
        $type[$player->getName()] = ["Temporary", "Permanent"];
        $this->database[$player->getName()]['rank'] = $ranks[$player->getName()];
        $this->database[$player->getName()]['icon'] = $icons[$player->getName()];
        $this->database[$player->getName()]['type'] = $type[$player->getName()];
        $form = new CustomForm(function(Player $player, array $data = null) {
            if($data === null){
                $player->sendMessage("§aThanks for open menu!");
                return;
            }
            if($data[3] === null){
                $player->sendMessage("§aThanks for open menu!");
                return;
            }
            if(!is_numeric($data[3])){
                $player->sendMessage("§aThanks for open menu!");
                return;
            }
            $database = [
                "icon" => isset($this->database[$player->getName()]['icon'][$data[8]]) ? $this->database[$player->getName()]['icon'][$data[8]] : "",
                "price" => $data[3],
                "days" => $data[4],
                "hours" => $data[5],
                "minutes" => $data[6],
                "seconds" => $data[7]
            ];
            $player->sendMessage($this->plugin->getDatabase()->createRank($this->database[$player->getName()]['rank'][$data[1]], $this->database[$player->getName()]['type'][$data[2]], $database));

        });
        $form->setTitle($this->plugin->getConfig()->getAll()['forms.create']['title']);
        $form->addLabel($this->plugin->getConfig()->getAll()['forms.create']['label']);
        $form->addDropdown($this->plugin->getConfig()->getAll()['forms.create']['dropdown-rank'], $ranks[$player->getName()]);
        $form->addDropdown($this->plugin->getConfig()->getAll()['forms.create']['dropdown-type'], $type[$player->getName()]);
        $form->addInput($this->plugin->getConfig()->getAll()['forms.create']['input-price'], "§7Amount Price");
        $form->addSlider($this->plugin->getConfig()->getAll()['forms.create']['slider-days'], 0, 32, 1);
        $form->addSlider($this->plugin->getConfig()->getAll()['forms.create']['slider-hours'], 0, 24, 1);
        $form->addSlider($this->plugin->getConfig()->getAll()['forms.create']['slider-minutes'], 1, 60, 1);
        $form->addSlider($this->plugin->getConfig()->getAll()['forms.create']['slider-seconds'], 0, 60, 1);
        $form->addDropdown($this->plugin->getConfig()->getAll()['forms.create']['dropdown-icon'], $icons[$player->getName()]);
        $form->sendToPlayer($player);
    }

    /**
     * @return array
     */
    public function getAllIcon(): array {
        $icons = [];
        foreach(glob($this->plugin->getDataFolder() . "/icons/*.{png, jpeg, jpg}") as $icon){
            $icons[] = basename($icon);
            /*$icons[] = str_replace($this->plugin->getDataFolder() . "/icons/", "", $icon);*/
        }
        return $icons;
    }

    /**
     * @return array
     */
    public function getAllRanks(): array {
        $ranks = [];
        foreach($this->plugin->getPurePerms()->getGroups() as $group){
            if($group->getName() != $this->plugin->getConfig()->get("default-rank")){
                $ranks[] = $group->getName();
            }
        }
        return $ranks;
    }

    /**
     * @param Player $player
     * @return int
     */
    public function upRank(Player $player): int {
        if($this->plugin->getDatabase()->getRank($player) == $this->plugin->getConfig()->get("default-rank")){
            return (int)0;
        }
        $rank = $this->plugin->getDatabase()->getRank($player);
        $time = $this->plugin->getDatabase()->getExpired($player);
        $rankprice = $this->plugin->ranks['rank'][$rank]['price'];
        $hasil = $rankprice * $time['days'] / 32 - ($rankprice / 100);
        $discount = $hasil + ($rankprice * 1 / 100);
        return (int)$discount;
    }

    /**
     * @param Player $player
     * @param string $rank_name
     * @return bool
     */
    public function antiDowngrade(Player $player, string $rank_name): bool {
        if($this->plugin->getDatabase()->getRank($player) == $this->plugin->getConfig()->get("default-rank")){
            return true;
        }
        $price_2 = $this->plugin->ranks['rank'][$rank_name]['price'] - $this->upRank($player);
        if($price_2 <= 0){
            return false;
        }
        return true;
    }
}