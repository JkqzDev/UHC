<?php

declare(strict_types=1);

namespace uhc\session;

use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use uhc\UHC;

final class SessionFactory {

    static private array $sessions = [];

    static public function getAll(): array {
        return self::$sessions;
    }

    static public function get(Player|string $player): ?Session {
        $xuid = $player instanceof Player ? $player->getXuid() : $player;

        return self::$sessions[$xuid] ?? null;
    }

    static public function create(Player $player): void {
        self::$sessions[$player->getXuid()] = new Session($player->getUniqueId()->getBytes(), $player->getXuid(), $player->getName());
    }

    static public function task(): void {
        UHC::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function (): void {
            foreach (self::getAll() as $session) {
                $session->update();
            }
        }), 20);
    }
}