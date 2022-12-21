<?php

declare(strict_types=1);

namespace uhc\game\cache;

use pocketmine\world\Position;

final class PositionCache {

    public function __construct(
        private array $positions = []
    ) {}

    public function getPosition(string $player): ?Position {
        return $this->positions[$player] ?? null;
    }

    public function addPosition(string $player, Position $position): void {
        $this->positions[$player] = $position;
    }

    public function removePosition(string $player): void {
        unset($this->positions[$player]);
    }
}