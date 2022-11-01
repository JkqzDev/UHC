<?php

declare(strict_types=1);

namespace uhc\game;

use pocketmine\scheduler\Task;
use uhc\session\Session;
use uhc\session\SessionFactory;
use uhc\team\Team;
use uhc\team\TeamFactory;
use uhc\UHC;

final class ScatteringTask extends Task {

    private function getQueues(): array {
        $game = UHC::getInstance()->getGame();

        if ($game->getProperties()->isTeam()) {
            return array_filter(TeamFactory::getAll(), function (Team $team): bool {
                return count($team->getOnlineMembers()) !== 0 && $team->isAlive() && !$team->isScattered();
            });
        }
        return array_filter(SessionFactory::getAll(), function (Session $session): bool {
            return $session->isOnline() && $session->isAlive() && !$session->isScattered();
        });
    }

    public function onRun(): void {
        $game = UHC::getInstance()->getGame();
        $queues = $this->getQueues();

        if (count($queues) > 0) {
            /** @var Session|Team */
            $queue = $queues[array_rand($queues)];

            $queue->scatter();
        } else {
            $game->setStatus(GameStatus::STARTING);
            $this->getHandler()->cancel();
        }
    }
}