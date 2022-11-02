<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\game\GameStatus;
use uhc\scenario\default\CatEyes;
use uhc\scenario\ScenarioFactory;
use uhc\session\SessionFactory;
use uhc\team\TeamFactory;
use uhc\UHC;

final class LateJoinCommand extends Command {

    public function __construct() {
        parent::__construct('latejoin', 'Command for join in uhc');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            return;
        }
        $game = UHC::getInstance()->getGame();

        if ($game->getStatus() !== GameStatus::RUNNING) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t use the command because the game hasn\'t started'));
            return;
        }
        $session = SessionFactory::get($sender);

        if ($session === null) {
            return;
        }

        if ($game->getGlobalTime() > $game->getGraceTime()) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t use the command because grace period has ended'));
            return;
        }
        
        if ($session->isScattered()) {
            $sender->sendMessage(TextFormat::colorize('&cYou were already scattered'));
            return;
        }
        
        if (!$session->isSpectator()) {
            $sender->sendMessage(TextFormat::colorize('&cYou aren\'t a spectator'));
            return;
        }
        
        if ($session->isHost()) {
            $sender->sendMessage(TextFormat::colorize('&cYou\'re a host'));
            return;
        }

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
                }
            }
        }

        if ($game->getStatus() === GameStatus::RUNNING) {
            $catEyes = ScenarioFactory::get('CatEyes');

            if ($catEyes instanceof CatEyes && $catEyes->isEnabled()) {
                $catEyes->addEffect($sender);
            }
        }
        $sender->sendMessage(TextFormat::colorize('&aYou have been given the starting items'));
        $sender->sendMessage(TextFormat::colorize('&aYou have been respawned. Good luck!'));
    }
}