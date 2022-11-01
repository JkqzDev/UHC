<?php

declare(strict_types=1);

namespace uhc\game;

use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use uhc\event\GameStartEvent;
use uhc\event\GameStopEvent;
use uhc\game\border\BorderHandler;
use uhc\game\cache\InventoryCache;
use uhc\game\cache\PositionCache;
use uhc\session\Session;
use uhc\session\SessionFactory;
use uhc\team\Team;
use uhc\team\TeamFactory;
use uhc\UHC;

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

        UHC::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            $this->running();
            $this->border->running();
        }), 20);
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
            $teams = array_filter(TeamFactory::getAll(), function (Team $team): bool {
                return $team->isAlive() && $team->isScattered();
            });

            if (count($teams) === 0) {
                /** @var Team */
                $team = array_values($teams)[0];

                Server::getInstance()->broadcastMessage(TextFormat::colorize('&aTeam #' . $team->getId() . ' has won the game!'));
                $this->stopGame();
            }
            return;
        }
        $players = array_filter(SessionFactory::getAll(), function (Session $session): bool {
            return $session->isAlive() && $session->isScattered();
        });

        if (count($players) === 1) {
            /** @var Session */
            $player = array_values($players)[0];

            Server::getInstance()->broadcastMessage(TextFormat::colorize($player->getName() . ' has won the game!'));
            $this->stopGame();
        }
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
        UHC::getInstance()->getScheduler()->scheduleRepeatingTask(new ScatteringTask, 15);
    }
    
    public function startGame(): void {
        $event = new GameStartEvent($this);
        $event->call();

        $this->status = GameStatus::RUNNING;
    }
    
    public function stopGame(): void {
        $event = new GameStopEvent($this);
        $event->call();

        $this->status = GameStatus::RESTARTING;
    }
    
    public function running(): void {
        switch ($this->status) {
            case GameStatus::STARTING:
                if ($this->startingTime <= 0) {
                    $this->startGame();

                    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                        if ($player->isImmobile()) {
                            $player->setImmobile(false);
                        }
                    }
                    return;
                }
                $this->startingTime--;
                break;
            
            case GameStatus::RUNNING:
                if ($this->finalhealTime === $this->globalTime) {
                    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                        $player->setHealth($player->getMaxHealth());
                        $player->sendMessage(TextFormat::colorize('&aYour health has been regenerated'));
                    }
                }

                if ($this->globalmuteTime === $this->globalTime) {
                    $this->properties->setGlobalMute(false);
                }

                if ($this->graceTime === $this->globalTime) {
                    Server::getInstance()->broadcastMessage(TextFormat::colorize('&eThe grace period has ended. Good luck!'));
                }
                $this->globalTime++;
                break;
        }
    }
}