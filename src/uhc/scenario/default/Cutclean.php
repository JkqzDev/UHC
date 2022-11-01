<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;
use uhc\scenario\Scenario;

final class Cutclean extends Scenario {

    public function __construct() {
        parent::__construct('Cutclean', 'Iron and gold ore smelt automatically after mining', self::PRIORITY_LOW, true);
    }

    public function handleBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $drops = $event->getDrops();
        $xpDrop = $event->getXpDropAmount();
        
        if (!$event->isCancelled()) {
            switch ($block->getId()) {
                case BlockLegacyIds::IRON_ORE:
                    $drops = [VanillaItems::IRON_INGOT()];
                    $xpDrop = mt_rand(1, 3);
                    break;
                
                case BlockLegacyIds::GOLD_ORE:
                    $drops = [VanillaItems::GOLD_INGOT()];
                    $xpDrop = mt_rand(2, 4);
                    break;
            }
            $event->setDrops($drops);
            $event->setXpDropAmount($xpDrop);
        }
    }
}