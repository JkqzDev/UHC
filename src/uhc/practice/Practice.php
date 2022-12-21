<?php

declare(strict_types=1);

namespace uhc\practice;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use uhc\session\data\KitData;
use uhc\session\Session;

final class Practice {

    public function __construct(
        Config         $config,
        private ?World $world = null,
        private array  $kills = []
    ) {
        if ($config->get('world-name', '') === '') {
            return;
        }
        $worldName = $config->get('world-name');

        if (!Server::getInstance()->getWorldManager()->isWorldGenerated($worldName)) {
            return;
        }

        if (!Server::getInstance()->getWorldManager()->isWorldLoaded($worldName)) {
            Server::getInstance()->getWorldManager()->loadWorld($worldName);
        }
        $this->world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
    }

    public function getKills(Player $player): int {
        if (!isset($this->kills[$player->getXuid()])) {
            return 0;
        }
        return $this->kills[$player->getXuid()];
    }

    public function addKill(Player $player): void {
        $this->kills[$player->getXuid()] += 1;
    }

    public function join(Session $session): void {
        $player = $session->getPlayer();

        if ($player === null) {
            return;
        }

        if ($this->world === null) {
            $player->sendMessage(TextFormat::colorize('&cPractice is not enabled.'));
            return;
        }
        $session->setInPractice(true);
        $session->clear();

        $player->teleport($this->world->getSpawnLocation());
        $this->kills[$player->getXuid()] = 0;
    }

    public function quit(Session $session): void {
        $player = $session->getPlayer();

        if ($player === null) {
            return;
        }
        $session->setInPractice(false);
        $session->clear();

        $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        KitData::lobby($player);

        if (isset($this->kills[$player->getXuid()])) {
            unset($this->kills[$player->getXuid()]);
        }
    }
}