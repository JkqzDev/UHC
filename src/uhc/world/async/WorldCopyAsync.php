<?php

declare(strict_types=1);

namespace uhc\world\async;

use Closure;
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

final class WorldCopyAsync extends AsyncTask {

    public function __construct(
        private string $world,
        private string $directory,
        private string $newName,
        private string $newDirectory,
        private ?Closure $callback
    ) {}

    public function onRun(): void {
        $directory = $this->directory;
        $world = $this->world;

        $newDirectory = $this->newDirectory;
        $newName = $this->newName;

        $path = $directory . DIRECTORY_SEPARATOR . $world;
        $newPath = $newDirectory . DIRECTORY_SEPARATOR . $newName;

        $this->copySource($path, $newPath);
    }

    private function copySource(string $source, string $target): void {
        if (!is_dir($source)) {
            @copy($source, $target);
            return;
        }
        @mkdir($target);
        $dir = dir($source);

        while (($entry = $dir->read()) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $Entry = $source . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($Entry)) {
                $this->copySource($Entry, $target . DIRECTORY_SEPARATOR . $entry);
                continue;
            }
            @copy($Entry, $target . DIRECTORY_SEPARATOR . $entry);
        }
        $dir->close();
    }

    public function onCompletion(): void {
        $worldName = $this->newName;
        $callback = $this->callback;

        if ($callback !== null) {
            Server::getInstance()->getWorldManager()->loadWorld($worldName);
            $callback(Server::getInstance()->getWorldManager()->getWorldByName($worldName));
        }
    }
}