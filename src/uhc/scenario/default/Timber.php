<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\block\Block;
use pocketmine\block\Wood;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\player\Player;
use uhc\scenario\Scenario;
use uhc\UHC;

final class Timber extends Scenario {
    
    public function __construct() {
        parent::__construct('Timber', 'Mining a log from a tree will mine the entire tree', self::PRIORITY_LOW);
    }
    
    public function handleBreak(BlockBreakEvent $event): void {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        
        if (!$event->isCancelled()) {
            if ($block instanceof Wood) {
                $this->executeBreak($player, $block);
            }
        }
    }
    
    private function executeBreak(Player $player, Block $block): void {
        foreach ($block->getAllSides() as $side) {
            if ($block->getId() === $side->getId()) {
                $player->getWorld()->useBreakOn($side->getPosition(), $item, null, true);
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $side): void {
                    $this->executeBreak($player, $side);
                }), 1);
            } else {
                foreach ($side->getAllSides() as $s) {
                    if ($block->getId() === $s->getId()) {
                        $player->getWorld()->useBreakOn($s->getPosition(), $item, null, true);
                        
                        UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $s): void {
                            $this->executeBreak($player, $s);
                        }), 1);
                    }
                }
            }
        }
    }
}