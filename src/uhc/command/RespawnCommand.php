<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\game\GameStatus;
use uhc\scenario\default\CatEyes;
use uhc\scenario\ScenarioFactory;
use uhc\session\SessionFactory;
use uhc\team\TeamFactory;
use uhc\UHC;

final class RespawnCommand extends Command {

    public function __construct() {
        parent::__construct('respawn', 'Command for respawn');
        $this->setPermission('respawn.command');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            return;
        }
        
        if (!$this->testPermission($sender)) {
            return;
        }
        $game = UHC::getInstance()->getGame();

        if ($game->getStatus() === GameStatus::WAITING || $game->getStatus() === GameStatus::SCATTERING) {
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage(TextFormat::colorize('&cUse /respawn [player]'));
            return;
        }
        $player = $sender->getServer()->getPlayerByPrefix($args[0]);

        if ($player === null) {
            $sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
            return;
        }
        $session = SessionFactory::get($player);

        if ($session === null) {
            $sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
            return;
        }

        if ($session->isHost()) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t respawn a hoster'));
            return;
        }

        if (!$session->isSpectator()) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t respawn a spectator'));
            return;
        }

        if ($session->isScattered()) {
            $inventory = $game->getInventoryCache()->getInventory($player->getXuid());
            $position = $game->getPositionCache()->getPosition($player->getXuid());

            $session->clear();

            $player->teleport($position);
            $player->setGamemode(GameMode::SURVIVAL());
            $player->getArmorInventory()->setContents(array_map(function (array $item) {
                return Item::jsonDeserialize($item);
            }, $inventory['armorContents']));
            $player->getInventory()->setContents(array_map(function (array $item) {}, $inventory['contents']));
            $player->sendMessage(TextFormat::colorize('&aYou have received your last known items'));
        } else {
            if (!$game->getProperties()->isTeam()) {
                $session->scatter();
            } else {
                if ($session->getTeam() === null) {
                    TeamFactory::create($session);
                    $team = $session->getTeam();

                    $team->scatter();
                } else {
                    $team = $session->getTeam();

                    if (!$team->isScattered()) {
                        $team->scatter();
                    } else {
                        $session->setScattered(true);
                        $session->setSpectator(false);
                        $session->clear();

                        $sender->teleport($team->getPosition());
                        // Give kit
                    }
                }
            }
            $player->sendMessage(TextFormat::colorize('&aYou have been given the starting items'));
        }

        if ($game->getStatus() === GameStatus::RUNNING) {
            $catEyes = ScenarioFactory::get('CatEyes');

            if ($catEyes instanceof CatEyes && $catEyes->isEnabled()) {
                $catEyes->addEffect($player);
            }
        }
        $player->sendMessage(TextFormat::colorize('&aYou have been respawned. Good luck!'));
        $sender->sendMessage(TextFormat::colorize('&aYou have revived player ' . $player->getName() . ' successfully!'));
    }
}