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
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use uhc\command\GlobalMuteCommand;
use uhc\command\HelpopCommand;
use uhc\command\LateJoinCommand;
use uhc\command\PingCommand;
use uhc\command\RespawnCommand;
use uhc\command\TopKillsCommand;
use uhc\command\UHCCommand;
use uhc\game\Game;
use uhc\item\GoldenHeadItem;
use uhc\player\DisconnectedFactory;
use uhc\player\entity\DisconnectedMob;
use uhc\practice\Practice;
use uhc\scenario\command\ScenariosCommand;
use uhc\scenario\ScenarioFactory;
use uhc\scenario\ScenarioHandler;
use uhc\session\SessionFactory;
use uhc\team\command\TeamChatCommand;
use uhc\team\command\TeamCommand;
use uhc\team\TeamFactory;
use uhc\world\WorldFactory;

final class UHC extends PluginBase {
    use SingletonTrait;

    private Game $game;
    private Practice $practice;

    public function getGame(): Game {
        return $this->game;
    }

    public function getPractice(): Practice {
        return $this->practice;
    }

    protected function onLoad(): void {
        self::setInstance($this);

        $this->saveResource('practice.yml');
    }

    protected function onEnable(): void {
        ScenarioFactory::loadAll();
        TeamFactory::loadAll();
        WorldFactory::loadAll();

        DisconnectedFactory::task();
        SessionFactory::task();

        $this->unregisterCommands();

        $this->registerGame();
        $this->registerPractice();
        $this->registerLibraries();
        $this->registerHandlers();
        $this->registerCommands();
        $this->registerEntities();
        $this->registerItems();
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

    private function registerGame(): void {
        $this->game = new Game;
    }

    private function registerPractice(): void {
        $this->practice = new Practice(new Config($this->getDataFolder() . 'practice.yml', Config::YAML));
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
        EntityFactory::getInstance()->register(DisconnectedMob::class, function (World $world, CompoundTag $nbt): DisconnectedMob {
            $entity = new DisconnectedMob(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            $entity->flagForDespawn();

            return $entity;
        }, ['DisconnectedMob', 'uhc:disconnectedmob'], EntityLegacyIds::ZOMBIE);
    }

    private function registerItems(): void {
        ItemFactory::getInstance()->register(new GoldenHeadItem, true);
    }

    protected function onDisable(): void {
        $this->game->delete();
    }
}