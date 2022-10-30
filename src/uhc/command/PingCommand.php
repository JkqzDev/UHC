<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class PingCommand extends Command {

    public function __construct() {
        parent::__construct('ping', 'Command for ping');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (isset($args[0])) {
            $player = $sender->getServer()->getPlayerByPrefix($args[0]);
            
            if (!$player instanceof Player) {
                $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
                return;
            }
            $sender->sendMessage(TextFormat::colorize('&b' . $player->getName() . '\'s ping: &f' . $player->getNetworkSession()->getPing() . 'ms'));
            return;
        }
        
        if (!$sender instanceof Player) {
            return;
        }
        $sender->sendMessage(TextFormat::colorize('&bYour ping: &f' . $sender->getNetworkSession()->getPing() . 'ms'));
    }
}