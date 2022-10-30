<?php

declare(strict_types=1);

namespace uhc\scenario;

use uhc\game\GameStatus;
use uhc\UHC;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;

final class ScenarioHandler implements Listener {
    
    public function handleBreak(BlockBreakEvent $event): void {
        $plugin = UHC::getInstance();
        
        if ($plugin->getGame()->getStatus() !== GameStatus::STARTING) {
            return;
        }
        ScenarioFactory::callEvent(__FUNCTION__, $event);
    }
    
    public function handleDamage(EntityDamageEvent $event): void {
        if ($plugin->getGame()->getStatus() !== GameStatus::STARTING) {
            return;
        }
        ScenarioFactory::callEvent(__FUNCTION__, $event);
    }
    
    public function handleEntityDeath(EntityDeathEvent $event): void {
        if ($plugin->getGame()->getStatus() !== GameStatus::STARTING) {
            return;
        }
        ScenarioFactory::callEvent(__FUNCTION__, $event);
    }
    
    public function handleItem(CraftItemEvent $event): void {
        if ($plugin->getGame()->getStatus() !== GameStatus::STARTING) {
            return;
        }
        ScenarioFactory::callEvent(__FUNCTION__, $event);
    }
    
    public function handleDeath(PlayerDeathEvent $event): void {
        if ($plugin->getGame()->getStatus() !== GameStatus::STARTING) {
            return;
        }
        ScenarioFactory::callEvent(__FUNCTION__, $event);
    }
    
    public function handleItemUse(PlayerItemUseEvent $event): void {
        if ($plugin->getGame()->getStatus() !== GameStatus::STARTING) {
            return;
        }
        ScenarioFactory::callEvent(__FUNCTION__, $event);
    }
}