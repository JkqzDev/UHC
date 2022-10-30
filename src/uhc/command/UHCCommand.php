<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

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
        
        
    }
}