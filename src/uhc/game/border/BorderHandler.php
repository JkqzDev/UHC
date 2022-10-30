<?php

declare(strict_types=1);

namespace uhc\game\border;

use pocketmine\entity\Living;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use uhc\game\border\filler\BorderFiller;
use uhc\game\border\filler\BorderFillerTask;
use uhc\game\GameStatus;
use uhc\UHC;

final class BorderHandler {

    public function __construct(
        private int $size = 1000,
        private int $index = 0,
        private int $nextSize = -1,
        private int $nextTime = -1,
        private bool $shrink = true,
        private array $borders = [],
        private array $broadcasters = [],
        private ?BorderFiller $filler = null
    ) {
        $this->borders = [
            35 => 750,
            45 => 500,
            50 => 250,
            55 => 100,
            60 => 50,
            65 => 25
        ];
        $this->broadcasters = [60, 30, 10, 5, 4, 3, 2, 1];
        $this->update();
    }

    public function getSize(): int {
        return $this->size;
    }
    
    public function getIndex(): int {
        return $this->index;
    }
    
    public function getTime(int $index): int {
        $keys = array_keys($this->borders);
        return $keys[$index] ?? -1;
    }
    
    public function getNextSize(bool $cache = true): int {
        if ($cache) {
            return $this->nextSize;
        }
        $key = $this->getNextTime();
        
        if ($key !== -1) {
            $borders = $this->borders;
            return $borders[$key] ?? -1;
        }
        return -1;
    }
    
    public function getNextTime(bool $cache = true): int {
        if ($cache) {
            return $this->nextTime;
        }
        
        if ($this->canShrink()) {
            return $this->getTime($this->getIndex());
        }
        return -1;
    }
    
    public function canShrink(bool $cache = true): bool {
        if ($cache) {
            return $this->shrink;
        }
        return $this->getIndex() <= (count($this->borders) - 1);
    }
    
    public function insideBorder(Living $player): bool {
        [$position, $worldX, $worldZ] = [
            $player->getPosition(),
            UHC::getInstance()->getGame()->getWorld()->getSafeSpawn()->getFloorX(),
            UHC::getInstance()->getGame()->getWorld()->getSafeSpawn()->getFloorZ()
        ];
        
        if ($position->getFloorX() > ($worldX + $this->size) || $position->getFloorX() < ($worldX - $this->size) ||
            $position->getFloorZ() > ($worldZ + $this->size) || $position->getFloorZ() < ($worldZ - $this->size)) {
            return false;
        }
        return true;
    }
    
    protected function createArena(int $size): bool {
        $session = $this->filler;
        
        if ($session === null) {
            return false;
        }
        $session->setDimensions(-$size, $size, -$size, $size);
        $session->loadChunks(UHC::getInstance()->getGame()->getWorld());
        
        for ($x = -$size; $x <= $size; $x++) {
            for ($z = -$size; $z <= $size; $z++) {
                $session->setBlockIdAt($x, 100, $z, 7);
                $session->setBlockIdAt($x, 101, $z, 2);
            }
        }
        $session->reloadChunks(UHC::getInstance()->getGame()->getWorld());
        return true;
    }
    
    protected function createLayer(int $firstX, int $secondX, int $firstZ, int $secondZ): bool {
        $minX = min($firstX, $secondX);
        $maxX = max($firstX, $secondX);
        
        $minZ = min($firstZ, $secondZ);
        $maxZ = max($firstZ, $secondZ);
        
        $session = $this->filler;
        
        if ($session === null) {
            return false;
        }
        $session->setDimensions($minX, $maxX, $minZ, $maxZ);
        $session->loadChunks(UHC::getInstance()->getGame()->getWorld());
        
        for ($x = $minX; $x <= $maxX; $x++) {
            for ($z = $minZ; $z <= $maxZ; $z++) {
                $session->getHighestBlockAt($x, $z, $y);
                $session->setBlockIdAt($x, $y, $z, 7);
            }
        }
        $session->reloadChunks(UHC::getInstance()->getGame()->getWorld());
        return true;
    }
    
    public function createWall(int $firstX, int $secondX, int $firstZ, int $secondZ): void {
        for ($height = 0; $height < 4; $height++) {
            UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use($firstX, $secondX, $firstZ, $secondZ): void {
                $this->createLayer($firstX, $secondX, $firstZ, $secondZ);
            }), 4 * 2);
        }
    }
    
    public function createBorder(): void {
        UHC::getInstance()->getScheduler()->scheduleRepeatingTask(new BorderFillerTask, 10);
    }
    
    public function teleportInside(Living $player): void {
        [$position, $worldX, $worldZ] = [
            $player->getPosition(),
            UHC::getInstance()->getGame()->getWorld()->getSafeSpawn()->getFloorX(),
            UHC::getInstance()->getGame()->getWorld()->getSafeSpawn()->getFloorZ()
        ];
        
        $outsideX = ($position->getFloorX() < $worldX ? $position->getFloorX() <= ($worldX - $this->size) : $position->getFloorX() >= ($worldX + $this->size));
        $outsideZ = ($position->getFloorZ() < $worldZ ? $position->getFloorZ() <= ($worldZ - $this->size) : $position->getFloorZ() >= ($worldZ + $this->size));
        $teleportDistance = 1.6;
        
        $newPosition = $position;
        $newPosition->x = $outsideX ? ($position->getFloorX() < $worldX ? ($worldX - $this->size + $teleportDistance) : ($worldX + $this->size - $teleportDistance)) : $position->x;
        $newPosition->z = $outsideZ ? ($position->getFloorZ() < $worldZ ? ($worldZ - $this->size + $teleportDistance) : ($worldZ + $this->size - $teleportDistance)) : $position->z;
        $newPosition->y = UHC::getInstance()->getGame()->getWorld()->getHighestBlockAt($newPosition->getFloorX(), $newPosition->getFloorZ());
        
        $player->teleport($newPosition->add(0, 1, 0));
    }
    
    public function setup(World $world): void {
        $this->filler = new BorderFiller($world);
        $this->createBorder();
    }
    
    public function teleportPlayers(): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($player->isOnline()) {
                if (!$this->insideBorder($player)) {
                    $this->teleportInside($player);
                } else {
                    if (!$this->canShrink()) {
                        $this->teleportInside($player);
                    }
                }
            }
        }
    }
    
    public function update(): void {
        $this->shrink = $this->canShrink(false);
        $this->nextSize = $this->getNextSize(false);
        $this->nextTime = $this->getNextTime(false);
    }
    
    public function shrink(): void {
        $this->size = $this->borders[array_keys($this->borders)[$this->index]];
        $this->index++;
        $this->update();
        
        if (!$this->canShrink()) {
            $this->createArena($this->size + 1);
            Server::getInstance()->broadcastMessage(TextFormat::colorize('&d[+] Final Arena'));
        }
        $this->createBorder();
        Server::getInstance()->broadcastMessage(TextFormat::colorize('&3[+] &bThe border has been shrank to ' . $this->getSize() . 'x' . $this->getSize()));
        $this->teleportPlayers();
    }
    
    public function schedule(): void {
        $game = UHC::getInstance()->getGame();
        
        if ($game->getStatus() === GameStatus::RUNNING) {
            if ($this->canShrink()) {
                $borderTime = $this->getNextTime();
                $borderSize = $this->getNextSize(false);
                $next = ($borderTime * 60) - $game->getGlobalTime();
                $broadcastMatches = array_filter($this->broadcasters, function (int $broadcastTime) use ($next): bool {
                    return $next === $broadcastTime;
                });

                if (count($broadcastMatches) > 0) {
                    $broadcastTime = $broadcastMatches[array_key_first($broadcastMatches)];
                    Server::getInstance()->broadcastMessage(TextFormat::colorize('&3[+] &bThe border will shrink to ' . $borderSize . 'x' . $borderSize . ' in ' . $broadcastTime . ' second(s)'));
                }

                if ($game->getGlobalTime() === ($borderTime * 60)) {
                    $this->shrink();
                }
            }
        }
    }
}