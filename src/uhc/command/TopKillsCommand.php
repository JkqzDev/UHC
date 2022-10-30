<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use uhc\game\GameStatus;
use uhc\session\SessionFactory;
use uhc\UHC;

final class TopKillsCommand extends Command {

    public function __construct() {
        parent::__construct('topkills', 'Command for top kills', null, ['kt']);
    }

    private function getPlayers(): array {
        $players = [];

        foreach (SessionFactory::getAll() as $session) {
            $players[$session->getName()] = $session->getKills();
        }
        return $players;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        $game = UHC::getInstance()->getGame();

        if ($game->getStatus() !== GameStatus::RUNNING) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t use the command'));
            return;
        }
        $players = $this->getPlayers();
        arsort($players);

        $sender->sendMessage(TextFormat::colorize('&fTOP &b10 &fKILLS'));

        for ($i = 0; $i < 10; $i++) {
            $pos = $i + 1;
            $name = array_keys($players);
            $kill = array_values($players);

            if (isset($name[$i])) {
                $sender->sendMessage(TextFormat::colorize('&b' . $pos . '. &f' . $name[$i] . ' &7- &3' . $kill[$i] . ' kill(s)'));
            }
        }
    }
}