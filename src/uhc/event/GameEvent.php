<?php

declare(strict_types=1);

namespace uhc\event;

use pocketmine\event\Event;
use uhc\game\Game;

abstract class GameEvent extends Event {

    public function __construct(
        protected Game $game
    ) {}

    public function getGame(): Game {
        return $this->game;
    }
}