<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use staffmode\session\SessionFactory as SessionSessionFactory;
use uhc\game\GameStatus;
use uhc\menu\ConfigMenu;
use uhc\session\SessionFactory;
use uhc\UHC;
use uhc\world\WorldFactory;

final class UHCCommand extends Command {

    public function __construct() {
        parent::__construct('uhc', 'Command for uhc');
        $this->setPermission('uhc.command');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            return;
        }

        if (!$this->testPermission($sender)) {
            return;
        }
        $session = SessionFactory::get($sender);

        if ($session === null) {
            return;
        }
        $game = UHC::getInstance()->getGame();

        if (!isset($args[0])) {
            $sender->sendMessage(TextFormat::colorize('&cUse /uhc help'));
            return;
        }
        $subCommand = strtolower($args[0]);

        switch ($subCommand) {
            case 'help':
                $commands = [
                    '&e/uhc setup &7- Use command to setup UHC world',
                    '&e/uhc config &7- Use command to configure the UHC.',
                    '&e/uhc start &7- Use command to start the UHC.',
                    '&e/uhc host &7- Use command to add new host.'
                ];

                $sender->sendMessage(TextFormat::colorize('&eUHC Commands' . PHP_EOL . implode(PHP_EOL, $commands)));
                break;

            case 'start':
                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cThe game has already started'));
                    return;
                }

                if ($game->getWorld() === null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou have to config to use this command'));
                    return;
                }

                foreach (SessionFactory::getAll() as $session) {
                    $session->clear();
                    $session->setInPractice(false);
                    $session->getPlayer()?->setGamemode(GameMode::ADVENTURE());
                    $session->getPlayer()?->teleport($session->getPlayer()->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                }
                $game->startScattering();
                $sender->sendMessage(TextFormat::colorize('&aThe game has starting'));
                break;

            case 'setup':
                if ($game->getWorld() !== null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t config again'));
                    return;
                }

                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cThe game has already started'));
                    return;
                }
                $worldData = WorldFactory::random();

                if ($worldData === null) {
                    $sender->sendMessage(TextFormat::colorize('&cThere are no worlds for the config'));
                    return;
                }
                $worldName = WorldFactory::randomName(10);

                $sender->sendMessage(TextFormat::colorize('&eWaiting 3-4 minutes until the setup is finished.'));
                $worldData->copy(
                    $worldName,
                    $sender->getServer()->getDataPath() . 'worlds',
                    function (World $world) use ($sender, $session, $game): void {
                        $game->setWorld($world);

                        $game->getBorder()->setup($world);
                        $game->getProperties()->setHost($sender->getName());

                        $sender->sendMessage(TextFormat::colorize('&eSetup finished!'));
                    }
                );
                break;

            case 'config':
                new ConfigMenu($sender);
                break;

            case 'host':
                if (!isset($args[1])) {
                    $sender->sendMessage(TextFormat::colorize('&cUse /uhc host [player]'));
                    return;
                }
                $player = $sender->getServer()->getPlayerByPrefix($args[1]);

                if ($player === null) {
                    $sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
                    return;
                }
                $target = SessionFactory::get($player);

                if ($target === null) {
                    return;
                }

                if (!$target->isHost()) {
                    $target->setHost(true);
                    $target->setSpectator(false);

                    $player->sendMessage(TextFormat::colorize('&aYou were added as another host of the game'));
                    $sender->sendMessage(TextFormat::colorize('&aYou added player ' . $player->getName() . ' as another host of the game'));

                    if ($game->getStatus() !== GameStatus::WAITING) {
                        $target->clear();
                        $player->setGamemode(GameMode::CREATIVE());
                        $player->teleport($game->getWorld()->getSpawnLocation());

                        $staffModeSession = SessionSessionFactory::get($player);
                        $staffModeSession?->giveItems($player);
                    }
                    return;
                }
                $target->setHost(false);

                $player->sendMessage(TextFormat::colorize('&cYou were removed as the game host'));
                $sender->sendMessage(TextFormat::colorize('&cYou have removed the player ' . $player->getName() . ' as host of the game'));
                break;

            case 'time':
                if (!isset($args[1])) {
                    $minutes = 0;
                } else {
                    $minutes = (int) $args[1] * 60;
                }

                if (!isset($args[2])) {
                    $seconds = 0;
                } else {
                    $seconds = (int) $args[2];
                }
                $time = $minutes + $seconds;
                $game->setGlobalTime($time);
                break;

            default:
                $sender->sendMessage(TextFormat::colorize('&cCommand not found. Please, use /uhc help'));
                break;
        }
    }
}