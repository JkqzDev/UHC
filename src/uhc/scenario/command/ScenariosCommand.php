<?php

declare(strict_types=1);

namespace uhc\scenario\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use uhc\scenario\Scenario;
use uhc\scenario\ScenarioFactory;

final class ScenariosCommand extends Command {

    public function __construct() {
        parent::__construct('scenarios', 'Command for scenarios');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        /** @var Scenario[] */
        $scenarios = array_filter(ScenarioFactory::getAll(), function (Scenario $scenario): bool {
            return $scenario->isEnabled();
        });
        $sender->sendMessage(TextFormat::colorize('&bScenarios Enabled'));
        
        foreach ($scenarios as $scenario) {
            $sender->sendMessage(TextFormat::colorize('&b' . $scenario->getName() . ' &7- &3' . $scenario->getDescription()));
        }
    }
}