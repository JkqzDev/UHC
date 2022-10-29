<?php

declare(strict_types=1);

namespace uhc\game;

use pocketmine\world\World;
use uhc\game\border\BorderHandler;
use uhc\game\cache\InventoryCache;
use uhc\game\cache\PositionCache;

final class Game {

    private GameProperties $properties;
    private BorderHandler $border;

    private InventoryCache $inventoryCache;
    private PositionCache $positionCache;

    public function __construct(
        private int $status = GameStatus::WAITING,
        private int $globalTime = 0,
        private int $startingTime = 15,
        private int $graceTime = 20 * 60,
        private int $finalhealTime = 10 * 60,
        private int $globalmuteTime = 15 * 60,
        private ?World $word = null
    ) {
        $this->properties = new GameProperties;
        $this->border = new BorderHandler;

        $this->inventoryCache = new InventoryCache;
        $this->positionCache = new PositionCache;
    }

    public function getProperties(): GameProperties {
        return $this->properties;
    }

    public function getBorder(): BorderHandler {
        return $this->border;
    }

    public function getInventoryCache(): InventoryCache {
        return $this->inventoryCache;
    }

    public function getPositionCache(): PositionCache {
        return $this->positionCache;
    }

    public function getStatus(): int {
        return $this->status;
    }

    public function getGlobalTime(): int {
        return $this->globalTime;
    }

    public function getStartingTime(): int {
        return $this->startingTime;
    }

    public function getGraceTime(): int {
        return $this->graceTime;
    }

    public function getFinalHealTime(): int {
        return $this->finalhealTime;
    }

    public function getGlobalmuteTime(): int {
        return $this->globalmuteTime;
    }

    public function getWorld(): ?World {
        return $this->world;
    }

    public function setStatus(int $status): void {
        $this->status = $status;
    }

    public function setWorld(World $word): void {
        $this->world = $word;
    }
}