<?php

declare(strict_types=1);

namespace uhc\session\data;

use uhc\UHC;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

final class KitData {

    static public function default(Player $player): void {
        $game = UHC::getInstance()->getGame();
        $player->setGamemode(GameMode::SURVIVAL());

        $player->getInventory()->setContents([
            VanillaItems::STEAK()->setCount($game->getProperties()->getLeatherCount()),
            VanillaItems::LEATHER()->setCount(10)
        ]);
    }

    static public function spectator(Player $player): void {
        $player->setGamemode(GameMode::SPECTATOR());

        $player->setAllowFlight(true);
        $player->setFlying(true);
    }
}