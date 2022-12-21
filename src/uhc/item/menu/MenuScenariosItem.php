<?php

declare(strict_types=1);

namespace uhc\item\menu;

use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use uhc\item\default\UHCItem;
use uhc\menu\ScenarioMenu;

final class MenuScenariosItem extends UHCItem {

    public function __construct() {
        parent::__construct('&eScenarios', ItemIds::BOOK);
    }

    public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult {
        new ScenarioMenu($player);
        return ItemUseResult::SUCCESS();
    }
}