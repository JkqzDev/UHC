<?php

declare(strict_types=1);

namespace uhc\menu;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use uhc\menu\config\AnnouncementMenu;
use uhc\menu\config\AppleRateMenu;
use uhc\menu\config\GlobalmuteTimeMenu;
use uhc\menu\config\GraceTimeMenu;
use uhc\menu\config\HealTimeMenu;
use uhc\menu\config\LeatherCountMenu;
use uhc\menu\config\ScenariosMenu;
use uhc\menu\config\TeamMenu;
use uhc\UHC;

final class ConfigMenu {

    public function __construct(Player $player) {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);

        // Heal time
        $heal_time = VanillaItems::STEAK();
        $heal_time->setCustomName(TextFormat::colorize('&r&cHeal Time'));
        // Globalmute time
        $globalmute_time = VanillaItems::COMPASS();
        $globalmute_time->setCustomName(TextFormat::colorize('&r&gGlobalmute Time'));
        // Grace time
        $grace_time = VanillaItems::DIAMOND_SWORD();
        $grace_time->setCustomName(TextFormat::colorize('&r&4Grace Period Time'));
        // Scenarios
        $scenarios = VanillaItems::BOW();
        $scenarios->setCustomName(TextFormat::colorize('&r&eScenarios'));
        // Apple rate
        $apple_rate = VanillaItems::APPLE();
        $apple_rate->setCustomName(TextFormat::colorize('&r&9Apple Rate'));
        // Leather Count
        $leather_count = VanillaItems::LEATHER();
        $leather_count->setCustomName(TextFormat::colorize('&r&2Leather Count'));
        // Teams
        $teams = VanillaItems::DIAMOND_CHESTPLATE();
        $teams->setCustomName(TextFormat::colorize('&r&dTeam'));
        // Announcement
        $announcement = VanillaItems::PAPER();
        $announcement->setCustomName(TextFormat::colorize('&r&9Announcement'));

        $menu->getInventory()->addItem(
            $heal_time,
            $globalmute_time,
            $grace_time,
            $scenarios,
            $apple_rate,
            $leather_count,
            $teams,
            $announcement
        );

        $menu->setListener(function (InvMenuTransaction $transaction) use ($leather_count, $heal_time, $globalmute_time, $grace_time, $scenarios, $apple_rate, $teams, $announcement): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $item = $transaction->getItemClicked();

            if ($item->equals($leather_count)) {
                $player->removeCurrentWindow();

                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new LeatherCountMenu($player);
                    }
                }), 2);
            } elseif ($item->equals($heal_time)) {
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