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
        private bool $host = false,
        private bool $spectator = false,
        private bool $scattered = false
    ) {
        $this->setScoreboard(new ScoreboardBuilder($this, '&l&3Cloud UHC&r'));
    }

    public function getXuid(): string {
        return $this->xuid;
    }

    public function getName(): string {
        return $this->name;
    }
    
    public function isHost(): bool {
        return $this->host;
    }
    
    public function isSpectator(): bool {
        return $this->spectator;
    }
    
    public function isScattered(): bool {
        return $this->scattered;
    }
    
    public function isOnline(): bool {
        return $this->getPlayer() !== null;
    }

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerByRawUUID($this->uuid);
    }

    public function setName(string $name): void {
        $this->name = $name;
    }
    
    public function setSpectator(bool $spectator): void {
        $this->spectator = $spectator;
    }
    
    public function setHost(bool $host): void {
        $this->host = $host;
    }
    
    public function setScattered(bool $scattered): void {
        $this->scattered = $scattered;
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