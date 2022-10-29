<?php

declare(strict_types=1);

namespace uhc\session;

use pocketmine\player\Player;
use pocketmine\Server;
use uhc\session\scoreboard\ScoreboardBuilder;
use uhc\session\scoreboard\ScoreboardTrait;

final class Session {
    use ScoreboardTrait;

    public function __construct(
        private string $uuid,
        private string $xuid,
        private string $name,
    ) {
        $this->setScoreboard(new ScoreboardBuilder($this, '&l&bUHC&r'));
    }

    public function getXuid(): string {
        return $this->xuid;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerByRawUUID($this->uuid);
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function update(): void {
        $this->scoreboard?->update();
    }

    public function join(): void {
        $this->scoreboard?->spawn();
    }

    public function quit(): void {
        
    }
}