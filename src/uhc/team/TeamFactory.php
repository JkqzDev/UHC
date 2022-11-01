<?php

declare(strict_types=1);

namespace uhc\team;

use uhc\session\Session;

final class TeamFactory {

    static private TeamProperties $properties;
    static private array $teams = [];

    static public function getProperties(): TeamProperties {
        return self::$properties;
    }

    static public function getAll(): array {
        return self::$teams;
    }

    static public function get(int $id): ?Team {
        return self::$teams[$id] ?? null;
    }

    static public function create(Session $owner): void {
        $id = 0;

        while (self::get($id) !== null) {
            $id++;
        }
        self::$teams[$id] = $team = new Team($id, $owner);

        $owner->setTeam($team);
    }

    static public function remove(int $id): void {
        if (self::get($id) === null) {
            return;
        }
        unset(self::$teams[$id]);
    }

    static public function loadAll(): void {
        self::$properties = new TeamProperties;
    }
}