<?php

declare(strict_types=1);

namespace uhc\menu\config;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use uhc\menu\ConfigMenu;
use uhc\UHC;

final class HealTimeMenu {

    public function __construct(Player $player) {
        $game = UHC::getInstance()->getGame();

        $max = 60 * 60;
        $min = 60;

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
                        new ConfigMenu($player);
                    }
                }), 2);
                return $transaction->discard();
            }

            if ($custom_name === '-1' || $custom_name === '-5') {
                $rem = $custom_name === '-1' ? 60 : 60 * 5;
                $value = $game->getFinalHealTime() - $rem;

                if ($value >= $min) {
                    $game->setFinalHealTime($value);
                    $menu->getInventory()->setContents($this->getItems($max, $min));
                }
                return $transaction->discard();
            }

            if ($custom_name === '+1' || $custom_name === '+5') {
                $add = $custom_name === '+1' ? 60 : 60 * 5;
                $value = $game->getFinalHealTime() + $add;

                if ($value <= $max) {
                    $game->setFinalHealTime($value);
                    $menu->getInventory()->setContents($this->getItems($max, $min));
                }
                return $transaction->discard();
            }

            return $transaction->discard();
        });
        $menu->send($player, TextFormat::colorize('&cHeal Time Configuration'));
    }

    private function getItems(int $max, int $min): array {
        $game = UHC::getInstance()->getGame();

        $finalHeal = $game->getFinalHealTime();
        $text = [
            TextFormat::colorize('&r&7------------------------'),
            TextFormat::colorize('&r&eCurrent Value: &f' . ($finalHeal === 60 * 60 ? gmdate('H:i:s', $finalHeal) : gmdate('i:s', $finalHeal)))
        ];

        if ($finalHeal === $max) {
            $text[] = TextFormat::colorize('&r');
            $text[] = TextFormat::colorize('&c&r&cExceeded the maximum value');
        } elseif ($finalHeal === $min) {
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