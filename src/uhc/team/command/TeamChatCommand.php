<?php

declare(strict_types=1);

namespace uhc\team\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\session\SessionFactory;
use uhc\UHC;

final class TeamChatCommand extends Command {

    public function __construct() {
        parent::__construct('teamchat', 'Command for team chat', null, ['tc']);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            return;
        }
        $game = UHC::getInstance()->getGame();
        $session = SessionFactory::get($sender);

        if ($session === null) {
            return;
        }

        if (!$game->getProperties()->isTeam()) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t use the command'));
            return;
        }

        if ($session->getTeam() === null) {
            $sender->sendMessage(TextFormat::colorize('&cYou don\'t have team'));
        }
        $session->getTeam()->chat($sender, implode(' ', $args));
    }
}