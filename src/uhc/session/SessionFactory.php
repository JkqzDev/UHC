<?php

declare(strict_types=1);

namespace uhc\session;

use pocketmine\player\Player;

final class SessionFactory {

    static private array $sessions = [];

    static public function get(Player|string $player): ?Session {
        $xuid = $player instanceof Player ? $player->getXuid() : $player;

        return self::$sessions[$xuid] ?? null;
    }

    static public function create(Player $player): void {
        self::$sessions[$player->getXuid()] = new Session($player->getUniqueId()->getBytes(), $player->getName());
    }

    static public function task(): void {

    }
}