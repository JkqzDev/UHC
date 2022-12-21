<?php

declare(strict_types=1);

namespace uhc\game;

final class GameProperties {

    public function __construct(
        private int     $appleRate = 20,
        private int     $leatherCount = 10,
        private bool    $team = false,
        private bool    $globalMute = false,
        private ?string $host = null
    ) {}

    public function getAppleRate(): int {
        return $this->appleRate;
    }

    public function getLeatherCount(): int {
        return $this->leatherCount;
    }

    public function isTeam(): bool {
        return $this->team;
    }

    public function isGlobalMute(): bool {
        return $this->globalMute;
    }

    public function getHost(): ?string {
        return $this->host;
    }

    public function setAppleRate(int $rate): void {
        $this->appleRate = $rate;
    }

    public function setLeatherCount(int $leatherCount): void {
        $this->leatherCount = $leatherCount;
    }

    public function setTeam(bool $team): void {
        $this->team = $team;
    }

    public function setGlobalMute(bool $globalMute): void {
        $this->globalMute = $globalMute;
    }

    public function setHost(?string $host): void {
        $this->host = $host;
    }
}