<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use uhc\game\GameStatus;
use uhc\menu\SetupMenu;
use uhc\UHC;
use uhc\world\WorldFactory;

final class UHCCommand extends Command {
    
    public function __construct() {
        parent::__construct('uhc', 'Command for uhc');
        $this->setPermission('uhc.command');
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            return;
        }
        
        if (!$this->testPermission($sender)) {
            return;
        }
        $game = UHC::getInstance()->getGame();
        
        if (!isset($args[0])) {
            return;
        }
        $subCommand = strtolower($args[0]);
        
        switch ($subCommand) {
            case 'setup':
                if ($game->getWorld() !== null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t setup again'));
                    return;
                }
                
                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cThe game has already started'));
                    return;
                }
                $worldData = WorldFactory::random();
                
                if ($worldData === null) {
                    $sender->sendMessage(TextFormat::colorize('&cThere are no worlds for the setup'));
                    return;
                }
                $worldName = WorldFactory::randomName(10);
                
                $worldData->copy(
                    $worldName,
                    $sender->getServer()->getDataPath() . 'worlds',
                    function (World $world) use ($sender, $game): void {
                        $game->setWorld($world);
                        
                        $game->getBorder()->setup($world);
                        $game->getProperties()->setHost($sender->getName());
                        
                        $sender->sendMessage(TextFormat::colorize('&aSetup finished!'));
                    }
                );
                break;
                
            case 'config':
                if ($game->getWorld() === null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou have to setup to use this command'));
                    return;
                }
                new SetupMenu($sender);
                break;
        }
    }
}