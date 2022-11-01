<?php

declare(strict_types=1);

namespace uhc\menu\setup;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use uhc\menu\SetupMenu;
use uhc\scenario\ScenarioFactory;
use uhc\UHC;

final class ScenariosMenu {
    
    public function __construct(Player $player) {
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        
        foreach (ScenarioFactory::getAll() as $scenario) {
            $item = ItemFactory::getInstance()->get(ItemIds::BOOK);
            $item->setCustomName(TextFormat::colorize('&r' . $scenario->getName()));
            $item->setLore([
                TextFormat::colorize('&r&7------------------------'),
                TextFormat::colorize('&r' . ($scenario->isActive() ? '&aScenario enabled' : '&cScenario disabled')),
                TextFormat::colorize('&r&7------------------------')
            ]);
            $menu->getInventory()->addItem($item);
        }
        $back = ItemFactory::getInstance()->get(ItemIds::ARROW);
        $back->setCustomName(TextFormat::colorize('&r&cBack'));
        
        $menu->getInventory()->setItem(26, $back);
        
        $menu->setListener(function (InvMenuTransaction $transaction) use ($menu): InvMenuTransactionResult {
            $action = $transaction->getAction();
            $item = $transaction->getItemClicked();
            $player = $transaction->getPlayer();
            $custom_name = TextFormat::clean($item->getCustomName());
            
            if ($custom_name === 'Back') {
                $player->removeCurrentWindow();
                
                UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
                    if ($player->isOnline()) {
                        new SetupMenu($player);
                    }
                }), 2);
            } else {
                $scenario = ScenarioFactory::get($custom_name);
                
                if ($scenario !== null) {
                    if ($scenario->isEnabled()) {
                        $scenario->setEnabled(false);
                    } else {
                        $scenario->setEnabled(true);
                    }
                    $item->setCustomName(TextFormat::colorize('&r' . $scenario->getName()));
                    $item->setLore([
                        TextFormat::colorize('&r&7------------------------'),
                        TextFormat::colorize('&r' . ($scenario->isEnabled() ? '&aScenario enabled' : '&cScenario disabled')),
                        TextFormat::colorize('&r&7------------------------')
                    ]);
                    $menu->getInventory()->setItem($action->getSlot(), $item);
                }
            }
            return $transaction->discard();
        });
        $menu->send($player, TextFormat::colorize('&eScenarios Configuration'));
    }
}