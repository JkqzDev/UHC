<?php

declare(strict_types=1);

namespace uhc\item\practice;

use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use uhc\item\default\UHCItem;
use uhc\session\SessionFactory;

final class PracticeItem extends UHCItem {

    public function __construct() {
        parent::__construct('&bPractice', ItemIds::DIAMOND_SWORD);
    }

    public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult {
        $session = SessionFactory::get($player);

        return ItemUseResult::SUCCESS();
    }
}