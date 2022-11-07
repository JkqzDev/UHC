<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\block\tile\Chest;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Explosion;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use uhc\entity\DisconnectedMob;
use uhc\item\GoldenHead;
use uhc\scenario\Scenario;
use uhc\UHC;

final class TimeBomb extends Scenario {

    public function __construct() {
        parent::__construct('TimeBomb', 'Upon a player\'s death, a chest will spawn with the player\'s items along with a golden head', 1, true);
    }

    private function summonChest(Living $entity): void {
        $game = UHC::getInstance()->getGame();
        $armorContents = $entity->getArmorInventory()->getContents();

        if ($entity instanceof Player) {
            $inventoryContents = $entity->getInventory()->getContents();
            $name = $entity->getName();
        } elseif ($entity instanceof DisconnectedMob) {
            $inventoryContents = $entity->getDisconnected()->getInventory();
            $name = $entity->getDisconnected()->getSession()->getName();
        }
        $items = array_merge($armorContents, $inventoryContents);
        $items[] = GoldenHead::create();

        $firstPos = $entity->getPosition()->asVector3();
        $secondPos = $entity->getPosition()->subtract(($entity->getPosition()->getX() > 0 ? -1 : 1), 0, 0);
        
        $entity->getWorld()->setBlock($firstPos, VanillaBlocks::CHEST());
        $entity->getWorld()->setBlock($secondPos, VanillaBlocks::CHEST());

        $firstTile = $game->getWorld()->getTile($firstPos);
        $secondTile = $game->getWorld()->getTile($secondPos);

        if ($firstTile instanceof Chest && $secondTile instanceof Chest) {
            $firstTile->setName(TextFormat::colorize('&e' . $entity->getName() . ' Corpse'));
            $secondTile->setName(TextFormat::colorize('&e' . $entity->getName() . ' Corpse'));

            $firstTile->pairWith($secondTile);
            $secondTile->pairWith($firstTile);

            $firstTile->getInventory()->setContents($items);
            $position = $entity->getPosition();
            
            UHC::getInstance()->getScheduler()->scheduleRepeatingTask(new class($name, $position) extends Task {
                private string $name;
                private Position $position;
                private FloatingTextParticle $particle;
                
                private int $countdown = 30;
                
                public function __construct(string $name, Position $position) {
                    $this->name = $name;
                    $this->position = $position;
                    
                    $this->particle = new FloatingTextParticle(TextFormat::colorize('&b' . $this->countdown), TextFormat::colorize('&b' . $this->name . ' &fcorpse will explode in:'));
                }
                
                private function explode(): void {
                    $explosion = new Explosion($this->position, 5);
                    $explosion->explodeA();
                    $explosion->explodeB();
                }
    
                private function updateParticle(): void {
                    if ($this->particle === null) {
                        return;
                    }
                    $this->particle->setText(TextFormat::colorize('&b' . $this->countdown));
                    $this->position->getWorld()->addParticle($this->position->asVector3()->add(0.5, 1, 0.5), $this->particle);
                }
    
                private function removeParticle(): void {
                    if ($this->particle === null) {
                        return;
                    }
                    $this->particle->setInvisible();
                    $this->position->getWorld()->addParticle($this->position->asVector3()->add(0.5, 1, 0.5), $this->particle);
                }
    
                public function onRun(): void {
                    $this->countdown--;
        
                    if ($this->countdown <= 0) {
                        $this->explode();
                        $this->removeParticle();
            
                        Server::getInstance()->broadcastMessage(TextFormat::colorize('&7[&6Timebomb&7] &e' . $this->name . '\'s corpse has exploded!'));
                        $this->getHandler()->cancel();
                        return;
                    }
                    $this->updateParticle();
                }
            }, 20);
        }
    }

    public function handleEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();

        if ($entity instanceof DisconnectedMob && $entity->getDisconnected() !== null) {
            $this->summonChest($entity);
            $event->setDrops([]);
        }
    }

    public function handleDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        
        $this->summonChest($player);
        $event->setDrops([]);
    }
}