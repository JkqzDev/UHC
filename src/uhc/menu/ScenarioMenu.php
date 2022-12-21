<?php

declare(strict_types=1);

namespace uhc\menu;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\scenario\Scenario;
use uhc\scenario\ScenarioFactory;

final class ScenarioMenu {

    public function __construct(Player $player) {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $scenarios = array_filter(ScenarioFactory::getAll(), fn(Scenario $scenario) => $scenario->isEnabled());

        $menu->getInventory()->setContents(array_map(fn(Scenario $scenario) => VanillaItems::BOOK()->setCustomName($scenario->getName()), $scenarios));
        $menu->setListener(InvMenu::readonly());
        $menu->send($player, TextFormat::colorize('&eScenarios'));
    }
}