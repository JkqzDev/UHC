<?php

declare(strict_types=1);

namespace uhc\world;

use uhc\UHC;

final class WorldFactory {

    static private array $worlds = [];

    static public function getAll(): array {
        return self::$worlds;
    }

    static public function randomName(int $length): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    static public function random(): ?World {
        $worlds = self::getAll();

        if (count($worlds) === 0) {
            return null;
        }
        return $worlds[array_rand($worlds)];
    }

    static public function create(string $worldName): void {
        self::$worlds[$worldName] = new World($worldName);
    }

    static public function loadAll(): void {
        if (!is_dir(UHC::getInstance()->getDataFolder() . 'worlds')) {
            @mkdir(UHC::getInstance()->getDataFolder() . 'worlds');
        }
        
        foreach (scandir(UHC::getInstance()->getDataFolder() . 'worlds') as $worldName) {
            if ($worldName === '.' || $worldName === '..') {
                continue;
            }

            if (!is_dir(UHC::getInstance()->getDataFolder() . 'worlds/' . $worldName)) {
                continue;
            }
            self::create($worldName);
        }
    }
}