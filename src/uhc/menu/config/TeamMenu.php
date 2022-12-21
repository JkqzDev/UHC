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
use uhc\team\TeamFactory;
use uhc\UHC;

final class TeamMenu {

    public function __construct(Player $player) {
        $game = UHC::getInstance()->getGame();

        $max_players = 10;
        $min_players = 2;

        $max_keyboards = 10;
        $min_keyboards = 1;

        $menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $menu->getInventory()->setContents($this->getItems($max_players, $min_players, $max_keyboards, $min_keyboards));

        $menu->setListener(function (InvMenuTransaction $transaction) use ($menu, $game, $max_players, $min_players, $max_keyboards, $min_keyboards): InvMenuTransactionResult {
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

            if ($custom_name === 'Enabled' || $custom_name === 'Disabled') {
                $value = $custom_name === 'Enabled' ? false : true;
                $game->getProperties()->setTeam($value);
                $menu->getInventory()->setContents($this->getItems($max_players, $min_players, $max_keyboards, $min_keyboards));
                return $transaction->discard();
            }

            if ($custom_name === '-1' || $custom_name === '+1') {
                if ($action->getSlot() === 9 || $action->getSlot() === 11) {
                    $value = $custom_name === '+1' ? true : false;

                    if ($value) {
                        $v_value = TeamFactory::getProperties()->getMaxPlayers() + 1;

                        if ($v_value <= $max_players) {
                            TeamFactory::getProperties()->setMaxPlayers($v_value);
                            $menu->getInventory()->setContents($this->getItems($max_players, $min_players, $max_keyboards, $min_keyboards));
                        }
                    } else {
                        $v_value = TeamFactory::getProperties()->getMaxPlayers() - 1;

                        if ($v_value >= $min_players) {
                            TeamFactory::getProperties()->setMaxPlayers($v_value);
                            $menu->getInventory()->setContents($this->getItems($max_players, $min_players, $max_keyboards, $min_keyboards));
                        }
                    }
                    return $transaction->discard();
                } elseif ($action->getSlot() === 15 || $action->getSlot() === 17) {
                    $value = $custom_name === '+1' ? true : false;

                    if ($value) {
                        $v_value = TeamFactory::getProperties()->getMaxKeyboards() + 1;

                        if ($v_value <= $max_keyboards) {
                            TeamFactory::getProperties()->setMaxKeyboards($v_value);
                            $menu->getInventory()->setContents($this->getItems($max_players, $min_players, $max_keyboards, $min_keyboards));
                        }
                    } else {
                        $v_value = TeamFactory::getProperties()->getMaxKeyboards() - 1;

                        if ($v_value >= $min_keyboards) {
                            TeamFactory::getProperties()->setMaxKeyboards($v_value);
                            $menu->getInventory()->setContents($this->getItems($max_players, $min_players, $max_keyboards, $min_keyboards));
                        }
                    }
                }
            }
            return $transaction->discard();
        });

        $menu->send($player, TextFormat::colorize('&dTeam Configuration'));
    }

    private function getItems(int $max_players, int $min_players, int $max_keyboards, int $min_keyboards): array {
        $game = UHC::getInstance()->getGame();

        $players = TeamFactory::getProperties()->getMaxPlayers();
        $keyboards = TeamFactory::getProperties()->getMaxKeyboards();

        $text_players = [
            TextFormat::colorize('&r&7------------------------'),
            TextFormat::colorize('&r&eCurrent Value: &f' . $players)
        ];

        $text_keyboards = [
            TextFormat::colorize('&r&7------------------------'),
            TextFormat::colorize('&r&eCurrent Value: &f' . $keyboards)
        ];

        if ($players === $max_players) {
            $text_players[] = TextFormat::colorize('&r');
            $text_players[] = TextFormat::colorize('&c&r&cExceeded the maximum value');
        } elseif ($players === $min_players) {
            $text_players[] = TextFormat::colorize('&r');
            $text_players[] = TextFormat::colorize('&c&r&cExceeded the minimum value');
        }
        $text_players[] = TextFormat::colorize('&r&7------------------------');

        if ($keyboards === $max_keyboards) {
            $text_keyboards[] = TextFormat::colorize('&r');
            $text_keyboards[] = TextFormat::colorize('&c&r&cExceeded the maximum value');
        } elseif ($keyboards === $min_keyboards) {
            $text_keyboards[] = TextFormat::colorize('&r');
            $text_keyboards[] = TextFormat::colorize('&c&r&cExceeded the minimum value');
        }
        $text_keyboards[] = TextFormat::colorize('&r&7------------------------');

        $toggle = ItemFactory::getInstance()->get(351, 10);
        $toggle->setCustomName(TextFormat::colorize('&r&aEnabled'));

        if (!$game->getProperties()->isTeam()) {
            $toggle = ItemFactory::getInstance()->get(351, 8);
            $toggle->setCustomName(TextFormat::colorize('&r&7Disabled'));
        }

        $back = ItemFactory::getInstance()->get(ItemIds::ARROW);
        $back->setCustomName(TextFormat::colorize('&r&cBack'));

        $player = ItemFactory::getInstance()->get(ItemIds::PAPER);
        $player->setCustomName(TextFormat::colorize('&r&fPlayer Count'));
        $player->setLore($text_players);

        $keyboard = ItemFactory::getInstance()->get(ItemIds::PAPER);
        $keyboard->setCustomName(TextFormat::colorize('&r&fKeyboard Count'));
        $keyboard->setLore($text_keyboards);

        $add_player = ItemFactory::getInstance()->get(ItemIds::DYE);
        $add_player->setCustomName(TextFormat::colorize('&r&a+1'));
        $rem_player = ItemFactory::getInstance()->get(ItemIds::DYE);
        $rem_player->setCustomName(TextFormat::colorize('&r&c-1'));

        $add_keyboard = ItemFactory::getInstance()->get(ItemIds::DYE);
        $add_keyboard->setCustomName(TextFormat::colorize('&r&a+1'));
        $rem_keyboard = ItemFactory::getInstance()->get(ItemIds::DYE);
        $rem_keyboard->setCustomName(TextFormat::colorize('&r&c-1'));

        return [
            4 => $toggle,
            9 => $add_player,
            10 => $player,
            11 => $rem_player,
            15 => $rem_keyboard,
            16 => $keyboard,
            17 => $add_keyboard,
            22 => $back
        ];
    }
}