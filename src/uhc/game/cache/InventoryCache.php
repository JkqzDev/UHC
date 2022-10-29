<?php

declare(strict_types=1);

namespace uhc\game\cache;

use pocketmine\item\Item;

final class InventoryCache {

    public function __construct(
        private array $inventories = []
    ) {}
    
    public function getInventory(string $player): ?array {
        return $this->inventories[$player] ?? null;
    }
    
    public function addInventory(string $player, array $armor, array $content): void {
        $this->inventories[$player] = [
            'armorContents' => array_map(function (Item $item) { return $item->jsonSerialize(); }, $armor),
            'contents' => array_map(function (Item $item) { return $item->jsonSerialize(); }, $content)
        ];
    }
}