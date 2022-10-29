<?php

declare(strict_types=1);

namespace uhc\game;

use pocketmine\world\World;

final class Game {

    private GameProperties $properties;

    public function __construct(
        private int $globalTime = 0,
        private int $startingTime = 15,
        private int $graceTime = 20 * 60,
        private int $finalhealTime = 10 * 60,
        private int $globalmuteTime = 15 * 60,
        private ?World $word = null
    ) {
        $this->properties = new GameProperties;
    }

    public function getProperties(): GameProperties {
        return $this->properties;
    }
}