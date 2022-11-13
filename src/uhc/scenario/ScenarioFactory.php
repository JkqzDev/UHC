<?php

declare(strict_types=1);

namespace uhc\scenario;

use pocketmine\event\Event;
use uhc\scenario\default\BloodDiamond;
use uhc\scenario\default\CatEyes;
use uhc\scenario\default\Cutclean;
use uhc\scenario\default\DoubleOres;
use uhc\scenario\default\DoubleOrNothing;
use uhc\scenario\default\Fireless;
use uhc\scenario\default\HasteyBoys;
use uhc\scenario\default\NoFall;
use uhc\scenario\default\Timber;
use uhc\scenario\default\TimeBomb;

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
        self::sort();
    }
    
    static public function callEvent(string $method, Event $event): void {
        foreach (self::getAll() as $scenario) {
            if ($scenario->isEnabled()) {
                $scenario->$method($event);
            }
        }
    }
    
    static public function loadAll(): void {
        self::create(new BloodDiamond);
        self::create(new CatEyes);
        self::create(new Cutclean);
        self::create(new DoubleOres);
        self::create(new DoubleOrNothing);
        self::create(new Fireless);
        self::create(new HasteyBoys);
        self::create(new NoFall);
        self::create(new Timber);
        self::create(new TimeBomb);
    }
    
    static private function sort(): void {
        uasort(self::getAll(), function (Scenario $firstScenario, Scenario $secondScenario) {
            return $firstScenario->getPriority() <=> $secondScenario->getPriority();
        });
    }
}