<?php

declare(strict_types=1);

namespace uhc;

use pocketmine\plugin\PluginBase;
use uhc\session\SessionFactory;

final class UHC extends PluginBase {

    static private UHC $instance;

    static public function getInstance(): UHC {
        return self::$instance;
    }

    protected function onLoad(): void
    {
        self::$instance = $this;    
    }

    protected function onEnable(): void
    {
        SessionFactory::task();

        $this->registerHandlers();
    }

    private function registerHandlers(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler, $this);
    }
}