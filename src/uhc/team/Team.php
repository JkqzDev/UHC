<?php

declare(strict_types=1);

namespace uhc\team;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\session\Session;

final class Team {

    public function __construct(
        private int $id,
        private Session $owner,
        private array $members = []
    ) {
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

    public function disband(): void {
        TeamFactory::remove($this->id);
    }
}