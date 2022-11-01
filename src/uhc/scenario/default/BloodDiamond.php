<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use uhc\scenario\Scenario;

final class BloodDiamond extends Scenario {

    public function __construct() {
        parent::__construct('Blood Diamond', 'Every time a player mines a diamond, the player takes half a heart of damage');
    }

    public function handleBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();

        if (!$event->isCancelled()) {
            if ($block->getId() === BlockLegacyIds::DIAMOND_ORE) {
                $player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_CUSTOM, 1));
            }
        }
    }
}