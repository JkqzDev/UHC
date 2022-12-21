<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\event\entity\EntityDamageEvent;
use uhc\scenario\Scenario;

final class Fireless extends Scenario {

    public function __construct() {
        parent::__construct('Fire Less', 'All types of fire damage are nullified', self::PRIORITY_LOW);
    }

    public function handleDamage(EntityDamageEvent $event): void {
        $player = $event->getEntity();
        $cause = $event->getCause();

        if (!$event->isCancelled()) {
            if (in_array($cause, [EntityDamageEvent::CAUSE_FIRE, EntityDamageEvent::CAUSE_FIRE_TICK, EntityDamageEvent::CAUSE_LAVA])) {
                $event->cancel();
                $player->extinguish();
            }
        }
    }
}