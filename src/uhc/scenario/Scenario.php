<?php

declare(strict_types=1);

namespace uhc\scenario;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use uhc\event\GameStartEvent;
use uhc\event\GameStopEvent;

abstract class Scenario {
    
    const PRIORITY_LOW = 0;
    const PRIORITY_MEDIUM = 1;
    const PRIORITY_HIGH = 2;
    
    public function __construct(
        private string $name,
        private string $description,
        private int $priority = self::PRIORITY_MEDIUM,
        private bool $enabled = false
    ) {}
    
    public function getName(): string {
        return $this->name;
    }
    
    public function getDescription(): string {
        return $this->description;
    }
    
    public function getPriority(): int {
        return $this->priority;
    }
    
    public function isEnabled(): bool {
        return $this->enabled;
    }
    
    public function setEnabled(bool $value): void {
        $this->enabled = $value;
    }
    
    public function handleBreak(BlockBreakEvent $event): void {
    }
    
    public function handleDamage(EntityDamageEvent $event): void {
    }
    
    public function handleEntityDeath(EntityDeathEvent $event): void {
    }
    
    public function handleItem(CraftItemEvent $event): void {
    }
    
    public function handleDeath(PlayerDeathEvent $event): void {
    }
    
    public function handleItemUse(PlayerItemUseEvent $event): void {
    }

    public function handleStart(GameStartEvent $event): void {
    }

    public function handleStop(GameStopEvent $event): void {
    }
}