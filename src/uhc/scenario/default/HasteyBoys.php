<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\item\Axe;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Pickaxe;
use pocketmine\item\Shovel;
use pocketmine\scheduler\ClosureTask;
use uhc\scenario\Scenario;
use uhc\UHC;

final class HasteyBoys extends Scenario {

    public function __construct() {
        parent::__construct('Hastey Boys', 'All tools crafted will be enchanted with efficiency III and unbreaking III', self::PRIORITY_LOW);
    }

    public function handleItem(CraftItemEvent $event): void {
        $player = $event->getPlayer();
        $outputs = $event->getOutputs();

        if (!$event->isCancelled()) {
            if (count($outputs) === 1) {
                $oldItem = $outputs[0];
                $newItem = clone $outputs[0];
                $newItem->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3));
                $newItem->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));

                if ($oldItem instanceof Axe || $oldItem instanceof Pickaxe || $oldItem instanceof Shovel) {
                    UHC::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use ($player, $oldItem, $newItem): void {
                        $slotIndex = 0;
                        $inventory = $player->getCursorInventory();
                        $result = $inventory->getItem($slotIndex)->equals($oldItem);

                        if (!$result) {
                            foreach ($player->getInventory()->getContents() as $slot => $it) {
                                if ($it->equals($oldItem)) {
                                    $slotIndex = $slot;
                                    $inventory = $player->getInventory();
                                    $result = true;
                                    break;
                                }
                            }
                        }

                        if ($result) {
                            $inventory->setItem($slotIndex, $newItem);
                        }
                    }));
                }
            }
        }
    }
}