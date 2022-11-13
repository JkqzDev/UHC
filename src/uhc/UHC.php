<?php

declare(strict_types=1);

namespace uhc;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\item\ItemFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use uhc\command\GlobalMuteCommand;
use uhc\command\HelpopCommand;
use uhc\command\LateJoinCommand;
use uhc\command\PingCommand;
use uhc\command\RespawnCommand;
use uhc\command\TellCommand;
use uhc\command\TopKillsCommand;
use uhc\command\UHCCommand;
use uhc\entity\DisconnectedMob;
use uhc\game\Game;
use uhc\item\GoldenHead;
use uhc\player\DisconnectedFactory;
use uhc\scenario\command\ScenariosCommand;
use uhc\scenario\ScenarioFactory;
use uhc\scenario\ScenarioHandler;
use uhc\session\SessionFactory;
use uhc\team\command\TeamChatCommand;
use uhc\team\command\TeamCommand;
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

        DisconnectedFactory::task();
        SessionFactory::task();

        $this->unregisterCommands();

        $this->registerGame();
        $this->registerLibraries();
        $this->registerHandlers();
        $this->registerCommands();
        $this->registerEntities();
        $this->registerItems();
    }

    protected function onDisable(): void {
        $this->game?->delete();        
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
            'me'
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
            new LateJoinCommand,
            new PingCommand,
            new RespawnCommand,
            //new TellCommand,
            new TopKillsCommand,
            new UHCCommand,
            // Scenarios
            new ScenariosCommand,
            // Team
            new TeamChatCommand,
            new TeamCommand
        ];

        foreach ($commands as $command) {
            $this->getServer()->getCommandMap()->register('UHC', $command);
        }
    }

    private function registerEntities(): void {
        EntityFactory::getInstance()->register(DisconnectedMob::class, function(World $world, CompoundTag $nbt): DisconnectedMob {
            return new DisconnectedMob(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['DisconnectedMob', 'uhc:disconnectedmob'], EntityLegacyIds::ZOMBIE);
    }

    private function registerItems(): void {
        ItemFactory::getInstance()->register(new GoldenHead, true);
    }

    public function getGame(): Game {
        return $this->game;
    }
}