<?php

declare(strict_types=1);

namespace uhc\scenario\defaults;

use uhc\scenario\Scenario;
use pocketmine\event\entity\EntityDamageEvent;

final class NoFall extends Scenario {
    
    public function __construct() {
        parent::__construct('NoFall', 'All types of fall damage are nullified', self::PRIORITY_LOW);
    }
    
    public function handleDamage(EntityDamageEvent $event): void {
        $player = $event->getEntity();
        $cause = $event->getCause();
        
        if (!$event->isCancelled()) {
            if ($cause === EntityDamageEvent::CAUSE_FALL) {
                $event->cancel();
            }
        }
    }
}