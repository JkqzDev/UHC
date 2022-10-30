<?php

declare(strict_types=1);

namespace uhc\scenario;

use uhc\scenario\Fireless;
use uhc\scenario\HasteyBoys;
use uhc\scenario\NoFall;
use uhc\scenario\Timber;
use pocketmine\event\Event;

final class ScenarioFactory {
    
    static private array $scenarios = [];
    
    static public function getAll(): array {
        return self::$scenarios;
    }
    
    static public function get(string $name): ?Scenario {
        return self::$scenarios[$name] ?? null;
    }
    
    static public function create(Scenario $scenario): void {
        self::$scenarios[$scenario->getName()] = $scenario;
    }
    
    static public function callEvent(string $method, Event $event): void {
        foreach (self::getAll() as $scenario) {
            if ($scenario->isEnabled()) {
                $scenario->$method($event);
            }
        }
    }
    
    static public function loadAll(): void {
        self::create(new Fireless);
        self::create(new HasteyBoys);
        self::create(new NoFall);
        self::create(new Timber);
    }
    
    static private function sort(): void {
        uasort(self::getAll(), function (Scenario $firstScenario, Scenario $secondScenario) {
            return $firstScenario->getPriority() <=> $secondScenario->getPriority();
        });
    }
}