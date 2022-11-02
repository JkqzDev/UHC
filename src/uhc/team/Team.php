<?php

declare(strict_types=1);

namespace uhc\team;

use pocketmine\block\BlockLegacyIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
use uhc\game\GameStatus;
use uhc\session\Session;
use uhc\UHC;

final class Team {

    public function __construct(
        private int $id,
        private Session $owner,
        private bool $scattered = false,
        private array $members = [],
        private ?Position $position = null
    ) {
        $this->addMember($owner);
    }

    public function getId(): int {
        return $this->id;
    }

    public function getKills(): int {
        $kills = 0;

        foreach ($this->members as $member) {
            $kills += $member->getKills();
        }
        return $kills;
    }

    public function getMembers(): array {
        return $this->members;
    }

    public function getOnlineMembers(): array {
        return array_filter($this->members, function (Session $session): bool {
            return $session->getPlayer() !== null;
        });
    }

    public function isOwner(Session $session): bool {
        return $session->getXuid() === $this->owner->getXuid();
    }

    public function isMember(Session $session): bool {
        return isset($this->members[spl_object_hash($session)]);
    }

    public function isScattered(): bool {
        return $this->scattered;
    }

    public function isAlive(): bool {
        $members = array_filter($this->members, function (Session $session): bool {
            return $session->isAlive() && $session->isScattered();
        });

        return count($members) > 0;
    }

    public function equals(?Team $team): bool {
        return $team !== null && $this->id === $team->getId();
    }

    public function getPosition(): ?Position {
        return $this->position;
    }

    public function addMember(Session $session): void {
        $this->members[spl_object_hash($session)] = $session;
    }

    public function removeMember(Session $session): void {
        unset($this->members[spl_object_hash($session)]);
    }

    public function broadcast(string $message): void {
        foreach ($this->getOnlineMembers() as $member) {
            $member->getPlayer()?->sendMessage(TextFormat::colorize($message));
        }
    }

    public function chat(Player $player, string $message): void {
        $this->broadcast('&e[Party Chat] ' . $player->getName() . ': ' . $message);
    }

    public function scatter(): void {
        $game = UHC::getInstance()->getGame();
        $border = $game->getBorder()->getSize() - 1;
        $world = $game->getWorld();

        if ($world === null) {
            return;
        }
        UHC::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use ($game, $border, $world): void {
            $x = mt_rand(-$border, $border);
            $z = mt_rand(-$border, $border);

            if (!$world->isChunkLoaded($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)) {
                $world->loadChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
            }
            $y = $world->getHighestBlockAt($x, $z);
            $position = new Position($x, $y, $z, $world);

            if (in_array($world->getBlock($position->asVector3()->add(0, -1, 0))->getId(), [BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::LAVA, BlockLegacyIds::WATER, BlockLegacyIds::FLOWING_WATER])) {
                $this->scatter();
                return;
            }
            /** @var Session[] */
            $members = array_filter($this->members, function (Session $session): bool {
                return $session->isOnline() && $session->isAlive();
            });

            foreach ($members as $member) {
                $player = $member->getPlayer();
                $player->teleport(Position::fromObject($position->add(0, 1, 0), $world));

                if ($game->getStatus() !== GameStatus::RUNNING) {
                    $player->setImmobile();
                }
                $member->setSpectator(false);
                $member->setScattered(true);
            }
            $this->scattered = true;
            $this->position = $position;
        }));
    }

    public function disband(): void {
        /** @var Session[] */
        $members = $this->members;

        foreach ($members as $member) {
            $member->setTeam(null);
            $member->getPlayer()?->sendMessage(TextFormat::colorize('&cThe team was disbaned'));
        }
        TeamFactory::remove($this->id);
    }
}