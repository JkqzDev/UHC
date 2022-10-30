<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use uhc\UHC;

final class GlobalMuteCommand extends Command {

    public function __construct() {
        parent::__construct('globalmute', 'Command for globalmute');
        $this->setPermission('globalmute.command');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$this->testPermission($sender)) {
            return;
        }
        $properties = UHC::getInstance()->getGame()->getProperties();

        if ($properties->isGlobalMute()) {
            $properties->setGlobalMute(false);
            $sender->getServer()->broadcastMessage(TextFormat::colorize('&aGlobalMute has been disabled!'));
            return;
        }
        $properties->setGlobalMute(true);
        $sender->getServer()->broadcastMessage(TextFormat::colorize('&cGlobalMute has been enabled!'));
    }
}