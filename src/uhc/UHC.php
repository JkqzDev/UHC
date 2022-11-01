<?php

declare(strict_types=1);

namespace uhc;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use uhc\command\GlobalMuteCommand;
use uhc\command\HelpopCommand;
use uhc\command\PingCommand;
use uhc\command\TellCommand;
use uhc\command\TopKillsCommand;
use uhc\command\UHCCommand;
use uhc\game\Game;
use uhc\scenario\command\ScenariosCommand;
use uhc\scenario\ScenarioFactory;
use uhc\scenario\ScenarioHandler;
use uhc\session\SessionFactory;
use uhc\team\command\TeamChatCommand;
use uhc\team\TeamFactory;
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
        ScenarioFactory::loadAll();
        TeamFactory::loadAll();
        WorldFactory::loadAll();

        SessionFactory::task();

        $this->unregisterCommands();

        $this->registerGame();
        $this->registerLibraries();
        $this->registerHandlers();
        $this->registerCommands();
    }

    private function registerGame(): void {
        $this->game = new Game;
    }

    private function registerLibraries(): void {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
    }

    private function registerHandlers(): void {
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler, $this);
        $this->getServer()->getPluginManager()->registerEvents(new ScenarioHandler, $this);
    }

    private function unregisterCommands(): void {
        $labels = [
            'clear',
            'version',
            'kill',
            'suicide',
            'me',
            'tell'
        ];

        foreach ($labels as $label) {
            $command = $this->getServer()->getCommandMap()->getCommand($label);

            if ($command !== null) {
                $this->getServer()->getCommandMap()->unregister($command);
            }
        }
    }

    private function registerCommands(): void {
        $commands = [
            // Global
            new GlobalMuteCommand,
            new HelpopCommand,
            new PingCommand,
            new TellCommand,
            new TopKillsCommand,
            new UHCCommand,
            // Scenarios
            new ScenariosCommand,
            // Team
            new TeamChatCommand
        ];

        foreach ($commands as $command) {
            $this->getServer()->getCommandMap()->register('UHC', $command);
        }
    }

    public function getGame(): Game {
        return $this->game;
    }
}