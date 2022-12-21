<?php

declare(strict_types=1);

namespace uhc\game\border\filler;

use pocketmine\scheduler\Task;
use uhc\UHC;

final class BorderFillerTask extends Task {

    public function __construct(
        private int $wallIndex = 0
    ) {}

    public function onRun(): void {
        $size = UHC::getInstance()->getGame()->getBorder()->getSize() + 1;

        switch ($this->wallIndex) {
            case 0:
                UHC::getInstance()->getGame()->getBorder()->createWall(-$size, $size, $size, $size);
                break;

            case 1:
                UHC::getInstance()->getGame()->getBorder()->createWall(-$size, $size, -$size, -$size);
                break;

            case 2:
                UHC::getInstance()->getGame()->getBorder()->createWall(-$size, -$size, -$size, $size);
                break;

            case 3:
                UHC::getInstance()->getGame()->getBorder()->createWall($size, $size, -$size, $size);
                $this->getHandler()->cancel();
                return;
        }
        $this->wallIndex++;
    }
}