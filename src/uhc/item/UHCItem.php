<?php

declare(strict_types=1);

namespace uhc\item\default;

use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\utils\TextFormat;

abstract class UHCItem extends Item {

    public function __construct(string $name, int $id, int $meta = 0) {
        parent::__construct(new ItemIdentifier($id, $meta), TextFormat::clean($name));
        $this->setCustomName(TextFormat::colorize($name));
        $this->getNamedTag()->setString('uhc_item', TextFormat::clean($name));
    }
}