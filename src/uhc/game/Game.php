<?php

declare(strict_types=1);

namespace uhc\game;

use pocketmine\world\World;
use uhc\game\border\BorderHandler;
use uhc\game\cache\InventoryCache;
use uhc\game\cache\PositionCache;
use uhc\session\Session;
use uhc\session\SessionFactory;
use uhc\team\TeamFactory;

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
        private ?World $world = null
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
    
    public function setStartingTime(int $time): void {
        $this->startingTime = $time;
    }
    
    public function setGraceTime(int $time): void {
        $this->graceTime = $time;
    }
    
    public function setFinalHealTime(int $time): void {
        $this->finalhealTime = $time;
    }
    
    public function setGlobalmuteTime(int $time): void {
        $this->globalmuteTime = $time;
    }
    
    public function setWorld(World $word): void {
        $this->world = $word;
    }
    
    public function checkWinner(): void {
        if ($this->properties->isTeam()) {
            return;
        }
        $players = array_filter(SessionFactory::getAll(), function (Session $session): bool {
            return $session->isAlive() && $session->isScattered();
        });
    }
    
    public function startScattering(): void {
        $this->status = GameStatus::SCATTERING;
        
        if ($this->properties->isTeam()) {
            $sessions = array_filter(SessionFactory::getAll(), function (Session $session): bool {
                return $session->isOnline() && $session->getTeam() === null;
            });
            
            foreach ($sessions as $session) {
                TeamFactory::create($session);
            }
        }
        $this->properties->setGlobalMute(true);
    }
    
    public function startGame(): void {
        $this->status = GameStatus::STARTING;
        
        
    }
    
    public function stopGame(): void {
    }
    
    public function running(): void {
    }
}