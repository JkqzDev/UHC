<?php

declare(strict_types=1);

namespace uhc\scenario\default;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use uhc\entity\DisconnectedMob;
use uhc\scenario\Scenario;

final class DoNotDisturb extends Scenario {

    public function __construct(
        private array $players = []
    ) {
        parent::__construct('Do Not Disturb', 'When you hit someone no one else can hit you or the player you are fighting for 15 seconds', self::PRIORITY_HIGH);
    }

    final protected function isPlayer(string $guidPlayer): bool {
        return isset($this->players[$guidPlayer]);
    }

    final protected function addPlayer(string $guidPlayer, string $guidOpponent): void {
        $this->players[$guidPlayer] = [
            'opponent' => $guidOpponent,
            'time' => time() + 15
        ];
    }

    final public function removePlayer(string $guid): void {
        if (isset($this->players[$guid])) {
            unset($this->players[$guid]);
        }
    }

    public function handleDamage(EntityDamageEvent $event): void {
        if ($event->isCancelled()) {
            return;
        }
        $player = $event->getEntity();

        if ($player instanceof Player) {
            $guidPlayer = $player->getXuid();
        } elseif ($player instanceof DisconnectedMob) {
            $disconnected = $player->getDisconnected();

            if ($disconnected === null) {
                return;
            }
            $guidPlayer = $disconnected->getSession()->getXuid();
        } else {
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if (!$damager instanceof Player) {
                return;
            }
            $guidOpponent = $damager->getXuid();

            if ($this->isPlayer($guidPlayer)) {
                $data = $this->players[$guidPlayer];

                if ($data['time'] > time() && $data['opponent'] !== $guidOpponent) {
                    $event->cancel();
                    return;
                }
            }

            if ($this->isPlayer($guidOpponent)) {
                $data = $this->players[$guidOpponent];

                if ($data['time'] > time() && $data['opponent'] !== $guidPlayer) {
                    $event->cancel();
                }
            }
            $this->addPlayer($guidPlayer, $guidOpponent);
            $this->addPlayer($guidPlayer, $guidPlayer);
        }
    }
}