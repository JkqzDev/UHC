<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use uhc\player\entity\DisconnectedMob;
use uhc\scenario\Scenario;
use uhc\session\SessionFactory;
use uhc\UHC;

final class NoClean extends Scenario {

    public function __construct(
        private array $lastHit = [],
        private array $closures = []
    ) {
        parent::__construct('No Clean', 'When you kill a player, you will receive 15 seconds where players can\'t hurt you');
    }

    public function handleDamage(EntityDamageEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
        $player = $event->getEntity();

        if ($player instanceof Player) {
            $session = SessionFactory::get($player);

            if ($session === null) {
                return;
            }
        } elseif ($player instanceof DisconnectedMob) {
            $disconnected = $player->getDisconnected();

            if ($disconnected === null) {
                return;
            }
            $session = $disconnected->getSession();
        } else {
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            if (isset($this->closures[$session->getXuid()])) {
                $event->cancel();
                return;
            }
            $damager = $event->getDamager();

            if (!$damager instanceof Player) {
                return;
            }

            if (isset($this->closures[$damager->getXuid()])) {
                $closure = $this->closures[$damager->getXuid()];
                $closure->cancel();
                unset($this->closures[$damager->getXuid()]);

                $damager->sendMessage(TextFormat::colorize('&cYou have lost your invulnerability'));
            }

            $this->lastHit[$session->getXuid()] = [
                'damager' => $damager,
                'time' => time() + 15
            ];
        }
    }

    public function handleEntityDeath(EntityDeathEvent $event): void {
        $player = $event->getEntity();

        if (!$player instanceof DisconnectedMob) {
            return;
        }
        $disconnected = $player->getDisconnected();

        if ($disconnected === null) {
            return;
        }
        $session = $disconnected->getSession();

        if (isset($this->lastHit[$session->getXuid()])) {
            $data = $this->lastHit[$session->getXuid()];

            if ($data['time'] > time()) {
                /** @var Player $damager */
                $damager = $data['damager'];

                $this->closures[$damager->getXuid()] = UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($damager): void {
                    unset($this->closures[$damager->getXuid()]);

                    if ($damager->isOnline()) {
                        $damager->sendMessage(TextFormat::colorize('&cYour invulnerability has worn off'));
                    }
                }), 15 * 20);

                if ($damager->isOnline()) {
                    $damager->sendMessage(TextFormat::colorize('&aYou have No Clean for 15 seconds!'));
                }
            }
        }
    }

    public function handleDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();

        if (isset($this->lastHit[$player->getXuid()])) {
            $data = $this->lastHit[$player->getXuid()];

            if ($data['time'] > time()) {
                /** @var Player $damager */
                $damager = $data['damager'];

                $this->closures[$damager->getXuid()] = UHC::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(static function () use ($damager): void {
                    unset($this->closures[$damager->getXuid()]);

                    if ($damager->isOnline()) {
                        $damager->sendMessage(TextFormat::colorize('&cYour invulnerability has worn off'));
                    }
                }), 15 * 20);

                if ($damager->isOnline()) {
                    $damager->sendMessage(TextFormat::colorize('&aYou have No Clean for 15 seconds!'));
                }
            }
        }
    }
}