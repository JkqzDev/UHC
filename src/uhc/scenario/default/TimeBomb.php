<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Chest;
use pocketmine\entity\Living;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\scenario\Scenario;

final class TimeBomb extends Scenario {

    public function __construct() {
        parent::__construct('TimeBomb', 'Upon a player\'s death, a chest will spawn with the player\'s items along with a golden head');
    }

    private function summonChest(Living $entity): void {
        $armorContents = $entity->getArmorInventory()->getContents();

        if ($entity instanceof Player) {
            $inventoryContents = $entity->getInventory()->getContents();
        }
        $items = array_merge($armorContents, $inventoryContents);
        $block = BlockFactory::getInstance()->get(BlockLegacyIds::CHEST);

        $firstPos = $entity->getPosition()->floor();
        $secondPos = $entity->getPosition()->floor()->subtract(0, 0, ($entity->getPosition()->getZ() > 0 ? -1 : 1));
        $entity->getWorld()->setBlock($firstPos, $block);
        $entity->getWorld()->setBlock($secondPos, $block);

        $firstTile = $entity->getWorld()->getTile($firstPos);
        $secondTile = $entity->getWorld()->getTile($secondPos);

        if ($firstTile instanceof Chest && $secondTile instanceof Chest) {
            $firstTile->setName(TextFormat::colorize('&e' . $entity->getName() . ' Corpse'));
            $secondTile->setName(TextFormat::colorize('&e' . $entity->getName() . ' Corpse'));

            $firstTile->pairWith($secondTile);
            $secondTile->pairWith($firstTile);

            $firstTile->getInventory()->setContents($items);
        }
    }

    public function handleDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        
        $this->summonChest($player);
        $event->setDrops([]);
    }
}