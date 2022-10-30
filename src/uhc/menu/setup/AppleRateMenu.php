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
use uhc\UHC;

final class AppleRateMenu {
    
    public function __construct(Player $player) {
        $game = UHC::getInstance()->getGame();
        
        $max = 60;
        $min = 0;
        
        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->getInventory()->setContents($this->getItems($max, $min));
        
        $menu->setListener(function (InvMenuTransaction $transaction) use ($menu, $game, $max, $min): InvMenuTransactionResult {
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
                return $transaction->discard();
            }
            
            if ($custom_name === '-1' || $custom_name === '-5') {
                $rem = $custom_name === '-1' ? 1 : 5;
                $value = $game->getProperties()->getAppleRate() - $rem;
                
                if ($value >= $min) {
                    $game->getProperties()->setAppleRate($value);
                    $menu->getInventory()->setContents($this->getItems($max, $min));
                }
                return $transaction->discard();
            }
            
            if ($custom_name === '+1' || $custom_name === '+5') {
                $add = $custom_name === '+1' ? 1 : 5;
                $value = $game->getProperties()->getAppleRate() + $add;
                
                if ($value <= $max) {
                    $game->getProperties()->setAppleRate($value);
                    $menu->getInventory()->setContents($this->getItems($max, $min));
                }
                return $transaction->discard();
            }
            
            return $transaction->discard();
        });
        $menu->send($player, TextFormat::colorize('&9Apple Rate Configuration'));
    }
    
    /**
     * @param int $max
     * @param int $min
     * @return array
     */
    private function getItems(int $max, int $min): array {
        $game = UHC::getInstance()->getGame();
        
        $apple_rate = $game->getProperties()->getAppleRate();
        $text = [
            TextFormat::colorize('&r&7------------------------'),
            TextFormat::colorize('&r&eCurrent Value: &f' . $apple_rate)
        ];
        
        if ($apple_rate === $max) {
            $text[] = TextFormat::colorize('&r');
            $text[] = TextFormat::colorize('&c&r&cExceeded the maximum value');
        } elseif ($apple_rate === $min) {
            $text[] = TextFormat::colorize('&r');
            $text[] = TextFormat::colorize('&c&r&cExceeded the minimum value');
        }
        $text[] = TextFormat::colorize('&r&7------------------------');
        
        $back = ItemFactory::getInstance()->get(ItemIds::ARROW);
        $back->setCustomName(TextFormat::colorize('&r&cBack'));
        
        $add_minute = ItemFactory::getInstance()->get(ItemIds::DYE);
        $add_minute->setCustomName(TextFormat::colorize('&r&a+1'));
        $add_minute->setLore($text);
        $add_minutes = ItemFactory::getInstance()->get(ItemIds::DYE);
        $add_minutes->setCustomName(TextFormat::colorize('&r&a+5'));
        $add_minutes->setLore($text);
        
        $rem_minute = ItemFactory::getInstance()->get(ItemIds::DYE);
        $rem_minute->setCustomName(TextFormat::colorize('&r&c-1'));
        $rem_minute->setLore($text);
        $rem_minutes = ItemFactory::getInstance()->get(ItemIds::DYE);
        $rem_minutes->setCustomName(TextFormat::colorize('&r&c-5'));
        $rem_minutes->setLore($text);
        
        return [
            9 => $rem_minutes,
            11 => $rem_minute,
            15 => $add_minute,
            17 => $add_minutes,
            22 => $back
        ];
    }
}