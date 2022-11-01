<?php

declare(strict_types=1);

namespace uhc\session;

use pocketmine\block\BlockLegacyIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
use uhc\game\GameStatus;
use uhc\session\scoreboard\ScoreboardBuilder;
use uhc\session\scoreboard\ScoreboardTrait;
use uhc\team\Team;
use uhc\UHC;

final class Session {
    use ScoreboardTrait;

    public function __construct(
        private string $uuid,
        private string $xuid,
        private string $name,
        private int $kills = 0,
        private bool $host = false,
        private bool $spectator = false,
        private bool $scattered = false,
        private ?Team $team = null
    ) {
        $this->setScoreboard(new ScoreboardBuilder($this, '&l&3Cloud UHC&r'));
    }

    public function getXuid(): string {
        return $this->xuid;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getKills(): int {
        return $this->kills;
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

    public function isAlive(): bool {
        return !$this->spectator && !$this->host;
    }
    
    public function isOnline(): bool {
        return $this->getPlayer() !== null;
    }

    public function getTeam(): ?Team {
        return $this->team;
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

    public function setTeam(?Team $team): void {
        $this->team = $team;
    }

    public function addKill(): void {
        $this->kills++;
    }

    public function update(): void {
        $this->scoreboard?->update();
    }

    public function scatter(): void {
        $player = $this->getPlayer();

        if ($player === null) {
            return;
        }
        $game = UHC::getInstance()->getGame();
        $border = $game->getBorder()->getSize() - 1;
        $world = $game->getWorld();

        if ($world === null) {
            return;
        }

        UHC::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use ($player, $game, $border, $world): void {
            $x = mt_rand(-$border, $border);
            $z = mt_rand(-$border, $border);

            if (!$world->isChunkLoaded($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)) {
                $world->loadChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
            }
            $y = $world->getHighestBlockAt($x, $z);
            $position = new Position($x, $y, $z, $world);
            /*$x = mt_rand(-$border, $border);
            $y = World::Y_MAX;
            $z = mt_rand(-$border, $border);

            $position = new Position($x, $y, $z, $world);
            $player->teleport($position);

            $y = $world->getHighestBlockAt($x, $z);
            $position->y = $y;*/

            if (in_array($world->getBlock($position->asVector3()->add(0, -1, 0))->getId(), [BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::LAVA, BlockLegacyIds::WATER, BlockLegacyIds::FLOWING_WATER])) {
                $this->scatter();
                return;
            }

            if ($game->getStatus() !== GameStatus::RUNNING) {
                $player->setImmobile();
            }
            $player->teleport(Position::fromObject($position->add(0, 1, 0), $world));

            $this->spectator = false;
            $this->scattered = true;
        }));
    }

    public function join(): void {
        $this->scoreboard?->spawn();
    }

    public function quit(): void {
        
    }
}