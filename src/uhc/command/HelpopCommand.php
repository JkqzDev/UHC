<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use uhc\session\Session;
use uhc\session\SessionFactory;

final class HelpopCommand extends Command {

    public function __construct() {
        parent::__construct('helpop', 'Command for helpop');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        /** @var Session[] */
        $sessions = array_filter(SessionFactory::getAll(), function (Session $target): bool {
            return $target->isOnline() && $target->isHost();
        });

        foreach ($sessions as $session) {
            $session->getPlayer()?->sendMessage(TextFormat::colorize('&b[Helpop] ' . $sender->getName() . ': &f' . implode(' ', $args)));
        }
    }
}