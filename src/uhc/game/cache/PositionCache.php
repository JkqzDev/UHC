<?php

declare(strict_types=1);

namespace uhc\game\cache;

use pocketmine\world\Position;

final class PositionCache {

    public function __construct(
        private array $positions = []
    ) {}
    
    public function getPosition(string $identificator): ?Position {
        return $this->positions[$identificator] ?? null;
    }
    
    public function addPosition(string $identificator, Position $position): void {
        $this->positions[$identificator] = $position;
    }
    
    public function removePosition(string $identificator): void {
        unset($this->positions[$identificator]);
    }
}