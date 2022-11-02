<?php

declare(strict_types=1);

namespace uhc\session\data;

use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

final class KitData {

    static public function default(Player $player): void {
        $player->setGamemode(GameMode::SURVIVAL());

        $player->getInventory()->setContents([
            VanillaItems::STEAK()->setCount(32),
            VanillaItems::LEATHER()->setCount(10)
        ]);
    }

    static public function spectator(Player $player): void {
        $player->setGamemode(GameMode::SPECTATOR());

        $player->setAllowFlight(true);
        $player->setFlying(true);
    }
}