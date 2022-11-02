<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Limits;
use uhc\event\GameStartEvent;
use uhc\scenario\Scenario;

final class CatEyes extends Scenario {

    public function __construct() {
        parent::__construct('Cat Eyes', 'All players receive night vision when the game begins', self::PRIORITY_LOW, true);
    }

    public function addEffect(Player $player): void
    {
        $effects = $player->getEffects();
        $effects->add(new EffectInstance(VanillaEffects::NIGHT_VISION(), Limits::INT32_MAX));
    }

    public function handleStart(GameStartEvent $event): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $this->addEffect($player);
        }
    }
}