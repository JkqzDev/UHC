<?php

declare(strict_types=1);

namespace uhc\menu;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use uhc\menu\setup\AnnouncementMenu;
use uhc\menu\setup\AppleRateMenu;
use uhc\menu\setup\GlobalmuteTimeMenu;
use uhc\menu\setup\GraceTimeMenu;
use uhc\menu\setup\HealTimeMenu;
use uhc\menu\setup\ScenariosMenu;
use uhc\menu\setup\TeamMenu;
use uhc\UHC;

final class SetupMenu {
    
    public function __construct(Player $player) {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        
        // Heal time
        $heal_time = ItemFactory::getInstance()->get(ItemIds::STEAK);
        $heal_time->setCustomName(TextFormat::colorize('&r&cHeal Time'));
        // Globalmute time
        $globalmute_time = ItemFactory::getInstance()->get(ItemIds::COMPASS);
        $globalmute_time->setCustomName(TextFormat::colorize('&r&gGlobalmute Time'));
        // Grace time
        $grace_time = ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD);
        $grace_time->setCustomName(TextFormat::colorize('&r&4Grace Period Time'));
        // Scenarios
        $scenarios = ItemFactory::getInstance()->get(ItemIds::BOW);
        $scenarios->setCustomName(TextFormat::colorize('&r&eScenarios'));
        // Apple rate
        $apple_rate = ItemFactory::getInstance()->get(ItemIds::APPLE);
        $apple_rate->setCustomName(TextFormat::colorize('&r&9Apple Rate'));
        // Teams
        $teams = ItemFactory::getInstance()->get(ItemIds::DIAMOND_CHESTPLATE);
        $teams->setCustomName(TextFormat::colorize('&r&dTeam'));
        // Announcement
        $announcement = ItemFactory::getInstance()->get(ItemIds::PAPER);
        $announcement->setCustomName(TextFormat::colorize('&r&9Announcement'));
        
        $menu->getInventory()->addItem(
            $heal_time,
            $globalmute_time,
            $grace_time,
            $scenarios,
            $apple_rate,
            $teams,
            $announcement
        );
        
        $menu->setListener(function (InvMenuTransaction $transaction) use ($heal_time, $globalmute_time, $grace_time, $scenarios, $apple_rate, $teams, $announcement): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $item = $transaction->getItemClicked();
            
            if ($item->equals($heal_time)) {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new HealTimeMenu($player);
                    }
                }), 2);
            } elseif ($item->equals($globalmute_time)) {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new GlobalmuteTimeMenu($player);
                    }
                }), 2);
            } elseif ($item->equals($grace_time)) {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new GraceTimeMenu($player);
                    }
                }), 2);
            } elseif ($item->equals($scenarios)) {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new ScenariosMenu($player);
                    }
                }), 2);
            } elseif ($item->equals($apple_rate)) {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new AppleRateMenu($player);
                    }
                }), 2);
            } elseif ($item->equals($teams)) {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new TeamMenu($player);
                    }
                }), 2);
            } elseif ($item->equals($announcement)) {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new AnnouncementMenu($player);
                    }
                }), 2);
            }
            return $transaction->discard();
        });
        
        $menu->send($player, TextFormat::colorize('&bUHC Configuration'));
    }
}