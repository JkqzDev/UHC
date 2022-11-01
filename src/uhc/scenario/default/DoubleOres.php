<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use uhc\scenario\Scenario;

final class DoubleOres extends Scenario {

    public function __construct() {
        parent::__construct('Double Ores', 'All ores mined are multipled by 2', self::PRIORITY_HIGH);
    }

    public function handleBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $drops = $event->getDrops();

        if (!$event->isCancelled()) {
            if (in_array($block->getId(), [BlockLegacyIds::IRON_ORE, BlockLegacyIds::GOLD_ORE, BlockLegacyIds::DIAMOND_ORE, BlockLegacyIds::REDSTONE_ORE, BlockLegacyIds::COAL_ORE, BlockLegacyIds::LAPIS_ORE])) {
                foreach ($drops as $drop) {
                    $drop->setCount($drop->getCount() * 2);
                }
                $event->setDrops($drops);
            }
        }
    }
}