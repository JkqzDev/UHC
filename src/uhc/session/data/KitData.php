<?php

declare(strict_types=1);

namespace uhc\session\data;

use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use uhc\item\menu\MenuScenariosItem;
use uhc\UHC;

final class KitData {

    static public function default(Player $player): void {
        $game = UHC::getInstance()->getGame();
        $player->setGamemode(GameMode::SURVIVAL());

        $player->getInventory()->setContents([
            VanillaItems::STEAK()->setCount($game->getProperties()->getLeatherCount()),
            VanillaItems::LEATHER()->setCount(10)
        ]);
    }

    static public function lobby(Player $player): void {
        $player->setGamemode(GameMode::ADVENTURE());

        $player->getInventory()->setContents([
            0 => new MenuScenariosItem,
        ]);
    }

    static public function spectator(Player $player): void {
        $player->setGamemode(GameMode::SPECTATOR());

        $player->setAllowFlight(true);
        $player->setFlying(true);
    }
}