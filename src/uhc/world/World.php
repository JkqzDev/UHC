<?php

declare(strict_types=1);

namespace uhc\world;

use Closure;
use pocketmine\Server;
use uhc\UHC;
use uhc\world\async\WorldCopyAsync;

final class World {

    public function __construct(
        private string $name
    ) {}

    public function copy(string $newName, string $newDirectory, ?Closure $callback = null): void {
        Server::getInstance()->getAsyncPool()->submitTask(new WorldCopyAsync(
            $this->name,
            UHC::getInstance()->getDataFolder() . 'worlds',
            $newName,
            $newDirectory,
            $callback
        ));
    }
}