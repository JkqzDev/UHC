<?php

declare(strict_types=1);

namespace uhc\team;

final class TeamProperties {

    public function __construct(
        private int $maxPlayers = 2,
        private int $maxKeyboards = 1
    ) {}

    public function getMaxPlayers(): int {
        return $this->maxPlayers;
    }

    public function getMaxKeyboards(): int {
        return $this->maxKeyboards;
    }

    public function setMaxPlayers(int $maxPlayers): void {
        $this->maxPlayers = $maxPlayers;
    }

    public function setMaxKeyboards(int $maxKeyboards): void {
        $this->maxKeyboards = $maxKeyboards;
    }
}