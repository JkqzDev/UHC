<?php

declare(strict_types=1);

namespace uhc\game;

final class GameStatus {

    const WAITING = 0;
    const SCATTERING = 1;
    const STARTING = 2;
    const RUNNING = 3;
    const RESTARTING = 4;
}