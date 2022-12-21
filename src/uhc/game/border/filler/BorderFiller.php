<?php

declare(strict_types=1);

namespace uhc\game\border\filler;

use pocketmine\block\BlockFactory;
use pocketmine\world\ChunkManager;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;
use Throwable;
use uhc\UHC;

final class BorderFiller {

    private SubChunkExplorer $chunkExplorer;
    private int $minX, $minZ;
    private int $maxX, $maxZ;
    private bool $error = false;

    public function __construct(ChunkManager $world) {
        $this->chunkExplorer = new SubChunkExplorer($world);
    }

    public function getHighestBlockAt(int $x, int $z, ?int &$y = null): bool {
        for ($y = 255; $y >= 0; --$y) {
            $this->getChunkExplorer()->moveTo($x, $y, $z);
            $id = $this->getChunkExplorer()->currentSubChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);

            if ($id >> 4 !== 0) {
                $block = BlockFactory::getInstance()->get($id >> 4, $id & 0xf);

                if ($block->isSolid()) {
                    $y++;
                    return true;
                }
                return false;
            }
        }
        return false;
    }

    public function moveTo(int $x, int $y, int $z): bool {
        $this->getChunkExplorer()->moveTo($x, $y, $z);

        if ($this->getChunkExplorer()->currentSubChunk === null) {
            try {
                $this->getChunkExplorer()->currentSubChunk = $this->getChunkExplorer()->currentChunk->getSubChunk($y >> 4);
            } catch (Throwable) {
                $this->error = true;
                return false;
            }
        }
        return true;
    }

    private function getChunkExplorer(): SubChunkExplorer {
        return $this->chunkExplorer;
    }

    public function setDimensions(int $minX, int $maxX, int $minZ, int $maxZ): void {
        $this->minX = $minX;
        $this->maxX = $maxX;
        $this->minZ = $minZ;
        $this->maxZ = $maxZ;
    }

    public function setBlockIdAt(int $x, int $y, int $z, int $id): void {
        if (!$this->moveTo($x, $y, $z)) {
            return;
        }
        $this->getChunkExplorer()->currentSubChunk->setFullBlock($x & 0xf, $y & 0xf, $z & 0xf, $id << 4);
    }

    public function loadChunks(World $world): void {
        $minX = $this->minX >> 4;
        $maxX = $this->maxX >> 4;
        $minZ = $this->minZ >> 4;
        $maxZ = $this->maxZ >> 4;

        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($z = $minZ; $z <= $maxZ; ++$z) {
                $chunk = $world->getChunk($x, $z);

                if ($chunk === null) {
                    $world->loadChunk($x, $z);
                }
            }
        }
    }

    public function reloadChunks(World $world): void {
        if ($this->error) {
            UHC::getInstance()->getLogger()->error('Some chunks weren\'t found');
        }
        $minX = $this->minX >> 4;
        $maxX = $this->maxX >> 4;
        $minZ = $this->minZ >> 4;
        $maxZ = $this->maxZ >> 4;

        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($z = $minZ; $z <= $maxZ; ++$z) {
                $chunk = $world->getChunk($x, $z);

                if ($chunk === null) {
                    continue;
                }
                $world->setChunk($x, $z, $chunk);

                foreach ($world->getChunkPlayers($x, $z) as $player) {
                    $player->doChunkRequests();
                }
            }
        }
    }

    public function close(): void {
        $this->getChunkExplorer()->invalidate();
    }
}