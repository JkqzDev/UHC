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
use uhc\discord\DiscordFeed;
use uhc\menu\SetupMenu;
use uhc\twitter\TwitterFeed;
use uhc\UHC;

final class AnnouncementMenu {
    
    public function __construct(
        Player $player,
        private int $waiting_time = 5 * 60
    ) {
        $max = 30 * 60;
        $min = 60;

        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->getInventory()->setContents($this->getItems($max, $min));

        $menu->setListener(function (InvMenuTransaction $transaction) use ($menu, $max, $min): InvMenuTransactionResult {
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
            
            if ($custom_name === 'Announce') {
                DiscordFeed::sendAnnounceMessage($this->waiting_time / 60);
                TwitterFeed::sendAnnounceMessage($this->waiting_time / 60);
                // Announce message
                return $transaction->discard();
            }
            
            if ($custom_name === '-1' || $custom_name === '-5') {
                $rem = $custom_name === '-1' ? 60 : 5 * 60;
                $value = $this->waiting_time - $rem;
                
                if ($value >= $min) {
                    $this->waiting_time = $value;
                    $menu->getInventory()->setContents($this->getItems($max, $min));
                }
                return $transaction->discard();
            }
            
            if ($custom_name === '+1' || $custom_name === '+5') {
                $add = $custom_name === '+1' ? 1 * 60 : 5 * 60;
                $value = $this->waiting_time + $add;
                
                if ($value <= $max) {
                    $this->waiting_time = $value;
                    $menu->getInventory()->setContents($this->getItems($max, $min));
                }
                return $transaction->discard();
            }
            
            return $transaction->discard();
        });

        $menu->send($player, TextFormat::colorize('&9Announcement Configuration'));
    }

    private function getItems(int $max, int $min): array {
        $game = UHC::getInstance()->getGame();
        
        $time = $this->waiting_time;
        $text = [
            TextFormat::colorize('&r&7------------------------'),
            TextFormat::colorize('&r&eCurrent Value: &f' . ($time / 60) . 'm')
        ];
        
        if ($time === $max) {
            $text[] = TextFormat::colorize('&r');
            $text[] = TextFormat::colorize('&c&r&cExceeded the maximum value');
        } elseif ($time === $min) {
            $text[] = TextFormat::colorize('&r');
            $text[] = TextFormat::colorize('&c&r&cExceeded the minimum value');
        }
        $text[] = TextFormat::colorize('&r&7------------------------');
        
        $back = ItemFactory::getInstance()->get(ItemIds::ARROW);
        $back->setCustomName(TextFormat::colorize('&r&cBack'));

        $announce = ItemFactory::getInstance()->get(ItemIds::DYE, 10);
        $announce->setCustomName(TextFormat::colorize('&r&aAnnounce'));
        
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
            4 => $announce,
            9 => $rem_minutes,
            11 => $rem_minute,
            15 => $add_minute,
            17 => $add_minutes,
            22 => $back
        ];
    }
}