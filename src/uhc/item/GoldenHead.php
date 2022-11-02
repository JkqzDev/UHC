<?php

declare(strict_types=1);

namespace uhc\item;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\GoldenApple;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\utils\TextFormat;

final class GoldenHead extends GoldenApple {

    static public function create(int $count = 1): Item {
        $item = ItemFactory::getInstance()->get(ItemIds::GOLDEN_APPLE, 10);
        $item->setCount($count);
        $item->setCustomName(TextFormat::colorize('&r&gGolden Head'));

        return $item;
    }

    public function __construct() {
        parent::__construct(new ItemIdentifier(ItemIds::GOLDEN_APPLE, 10), 'Golden Head');
    }

    public function getAdditionalEffects(): array {
        return [
            new EffectInstance(VanillaEffects::REGENERATION(), 20 * 9, 1),
            new EffectInstance(VanillaEffects::ABSORPTION(), 2400)
        ];
    }

    public function getVanillaName(): string {
        return 'Golden Head';
    }
}