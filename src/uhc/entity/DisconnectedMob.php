<?php

declare(strict_types=1);

namespace uhc\entity;

use pocketmine\entity\Zombie;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use uhc\discord\DiscordFeed;
use uhc\game\GameStatus;
use uhc\player\Disconnected;
use uhc\session\Session;
use uhc\session\SessionFactory;
use uhc\UHC;

final class DisconnectedMob extends Zombie {

    private ?Disconnected $disconnected = null;
    private ?Session $lastHit = null;

    public function getDisconnected(): ?Disconnected {
        return $this->disconnected;
    }

    public function getRealName(): string {
        return $this->disconnected !== null ? $this->disconnected->getSession()->getName() : '';
    }

    public function getXpDropAmount(): int {
        if ($this->disconnected !== null) {
            return mt_rand(1, 3);
        }
        return 0;
    }

    public function getContents(): array {
        return $this->disconnected !== null ? $this->disconnected->getInventory() : [];
    }

    public function getDrops(): array {
        if ($this->disconnected !== null) {
            return array_merge($this->disconnected->getInventory(), $this->getArmorInventory()->getContents());
        }
        return [];
    }

    public function attack(EntityDamageEvent $source): void {
        $cause = $source->getCause();
        $disconnected = $this->disconnected;

        $game = UHC::getInstance()->getGame();

        if ($disconnected !== null) {
            $session = $disconnected->getSession();

            if ($cause === EntityDamageEvent::CAUSE_VOID) {
                $this->teleport($this->getWorld()->getSpawnLocation());
            }
            
            if ($game->getStatus() !== GameStatus::RUNNING) {
                $source->cancel();
                return;
            }

            if ($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
                if ($game->getGraceTime() > $game->getGlobalTime()) {
                    $source->cancel();
                    return;
                }
            }

            if ($source instanceof EntityDamageByEntityEvent) {
                $damager = $source->getDamager();

                if (!$damager instanceof Player) {
                    return;
                }
                $damager_session = SessionFactory::get($damager);

                if ($damager_session === null) {
                    return;
                }

                if ($game->getProperties()->isTeam() && $damager_session->getTeam() !== null) {
                    if ($damager_session->getTeam()->equals($session->getTeam())) {
                        $source->cancel();
                        return;
                    }
                }
                $this->lastHit = $damager_session;
            }
        }
        parent::attack($source);
    }

    protected function onDeath(): void {
        parent::onDeath();
        $disconnected = $this->disconnected;

        if ($disconnected === null) {
            return;
        }
        $session = $disconnected->getSession();
        $game = UHC::getInstance()->getGame();

        if ($session === null || $game->getStatus() !== GameStatus::RUNNING) {
            return;
        }
        $message = '&c(AFK) ' . $session->getName() . ' &7[&f' . $session->getKills() . '&7] &edied'; 

        if ($this->lastHit !== null) {
            /** @var Session */
            $damager = $this->lastHit;
            $damager->addKill();

            $message = '&c(AFK) ' . $session->getName() . ' &7[&f' . $session->getKills() . '&7] &ewas slain by &c' . $damager->getName() . ' &7[&f' . $damager->getKills() . '&7]';
        }
        $session->setSpectator(true);

        $game->getInventoryCache()->addInventory($session->getXuid(), $disconnected->getArmorInventory(), $disconnected->getInventory());
        $game->getPositionCache()->addPosition($session->getXuid(), $this->getPosition());
        $game->checkWinner();
        
        $message = TextFormat::colorize($message);
        
        Server::getInstance()->broadcastMessage($message);
        DiscordFeed::sendKillMessage(TextFormat::clean($message));
    }

    public function setDisconnected(Disconnected $disconnected): void {
        $this->disconnected = $disconnected;
    }
}