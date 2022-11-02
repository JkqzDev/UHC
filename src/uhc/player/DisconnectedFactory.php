<?php

declare(strict_types=1);

namespace uhc\player;

use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use uhc\session\Session;
use uhc\UHC;

final class DisconnectedFactory {

    public const EXPIRATION_TIME = 10 * 60;
    static private array $disconnected = [];

    static public function getAll(): array {
        return self::$disconnected;
    }

    static public function get(string $xuid): ?Disconnected {
        return self::$disconnected[$xuid] ?? null;
    }

    static public function create(Session $session, Player $player): void {
        self::$disconnected[$player->getXuid()] = new Disconnected($session, time() + self::EXPIRATION_TIME, $player->getHealth(), $player->getArmorInventory()->getContents(), $player->getInventory()->getContents(), $player->getLocation());
    }

    static public function remove(string $xuid): void {
        if (self::get($xuid) === null) {
            return;
        }
        unset(self::$disconnected[$xuid]);
    }

    static public function task(): void {
        UHC::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach (self::getAll() as $disconnected) {
                $disconnected->check();
            }
        }), 20);
    }
}