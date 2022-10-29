<?php

declare(strict_types=1);

namespace uhc;

use pocketmine\plugin\PluginBase;
use uhc\game\Game;
use uhc\session\SessionFactory;
use uhc\world\WorldFactory;

final class UHC extends PluginBase {

    static private UHC $instance;
    private Game $game;

    static public function getInstance(): UHC {
        return self::$instance;
    }

    protected function onLoad(): void
    {
        self::$instance = $this;    
    }

    protected function onEnable(): void
    {
        WorldFactory::loadAll();
        SessionFactory::task();

        $this->registerGame();
        $this->registerHandlers();
    }

    private function registerGame(): void {
        $this->game = new Game;
    }

    private function registerHandlers(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler, $this);
    }

    public function getGame(): Game {
        return $this->game;
    }
}