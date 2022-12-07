<?php

declare(strict_types=1);

namespace uhc\player;

use Exception;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use uhc\discord\DiscordFeed;
use uhc\entity\DisconnectedMob;
use uhc\session\Session;
use uhc\UHC;

final class Disconnected {

    public function __construct(
        private Session $session,
        private int $expiration,
        private float $health,
        private array $armorInventory,
        private array $inventory,
        private Location $location,
        private ?DisconnectedMob $disconnectedMob = null
    ) {
        $this->spawn();
    }

    public function spawn(): void {
        $mob = new DisconnectedMob($this->location);
        $mob->setCanSaveWithChunk(false);
        $mob->setDisconnected($this);
        $mob->setHealth($this->health);

        $mob->setNameTagVisible();
        $mob->setNameTagAlwaysVisible();

        $mob->setNameTag(TextFormat::colorize('&r' . $this->session->getName() . ' &e[AFK]'));
        $mob->getArmorInventory()->setContents($this->armorInventory);
        $mob->spawnToAll();

        $this->disconnectedMob = $mob;
    }

    public function getSession(): Session {
        return $this->session;
    }

    public function getHealth(): float {
        return $this->health;
    }

    public function getArmorInventory(): array {
        return $this->armorInventory;
    }

    public function getInventory(): array {
        return $this->inventory;
    }

    public function getLocation(): Location {
        return $this->location;
    }

    public function join(Player $player): void {
        $mob = $this->disconnectedMob;

        if ($mob !== null && !$mob->isClosed() && !$mob->isFlaggedForDespawn()) {
            $player->teleport($mob->getPosition());
            $player->setHealth($mob->getHealth());

            $mob->flagForDespawn();
        } else {
            $player->teleport($this->location->asPosition());
        }
        DisconnectedFactory::remove($this->session->getXuid());
    }

    /**
     * @throws Exception
     */
    public function check(): void {
        $game = UHC::getInstance()->getGame();

        if ($this->expiration <= time()) {
            $mob = $this->disconnectedMob;
            $position = $this->location->asPosition();

            if ($mob !== null && !$mob->isClosed() && !$mob->isFlaggedForDespawn()) {
                $position = $mob->getPosition();
                $mob->flagForDespawn();
            }
            $session = $this->session;

            $session->setSpectator(true);

            $game->getInventoryCache()->addInventory($session->getXuid(), $this->armorInventory, $this->inventory);
            $game->getPositionCache()->addPosition($session->getXuid(), $position);
            $game->checkWinner();

            $message = TextFormat::colorize('&c(AFK) ' . $session->getName() . ' &7[&f' . $session->getKills() . '&7] &edied');
            Server::getInstance()->broadcastMessage($message);
            DiscordFeed::sendKillMessage(TextFormat::clean($message));

            DisconnectedFactory::remove($session->getXuid());
        }
    }
}