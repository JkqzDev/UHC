<?php

declare(strict_types=1);

namespace uhc;

use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\game\GameStatus;
use uhc\session\Session;
use uhc\session\SessionFactory;

final class EventHandler implements Listener {

    private array $lastHit = [];
    
    public function handleBreak(BlockBreakEvent $event): void
    {
        $block = $event->getBlock();
        $item = $event->getItem();
        $player = $event->getPlayer();
        $game = UHC::getInstance()->getGame();
        
        if ($game->getStatus() < GameStatus::RUNNING) {
            if ($player->hasPermission('build.permission')) {
                $event->cancel();
                return;
            }
        }

        if ($block->getId() === BlockLegacyIds::LEAVES || $block->getId() === BlockLegacyIds::LEAVES2) {
            $max = 100;

            if ($item->getId() === ItemIds::SHEARS) {
                $max /= 1.5;
            }
            $chance = mt_rand(0, intval($max));

            if ($chance <= $game->getProperties()->getAppleRate()) {
                $event->setDropsVariadic(VanillaItems::APPLE());
            }
        }
    }
    
    public function handlePlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $game = UHC::getInstance()->getGame();
        
        if ($game->getStatus() < GameStatus::RUNNING) {
            if ($player->hasPermission('build.permission')) {
                $event->cancel();
            }
        }
    }

    public function handleDamage(EntityDamageEvent $event): void {
        $cause = $event->getCause();
        $player = $event->getEntity();
        $game = UHC::getInstance()->getGame();

        if (!$player instanceof Player) {
            return;
        }
        $session = SessionFactory::get($player);

        if ($session === null) {
            return;
        }

        if ($cause === EntityDamageEvent::CAUSE_VOID) {
            $player->teleport($player->getWorld()->getSpawnLocation());
        }

        if ($game->getStatus() !== GameStatus::RUNNING) {
            $event->cancel();
            return;
        }

        if ($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK && $game->getGraceTime() > $game->getGlobalTime()) {
            $event->cancel();
            return;
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if (!$damager instanceof Player) {
                return;
            }
            $damager_session = SessionFactory::get($damager);

            if ($damager_session === null) {
                return;
            }

            if ($game->getProperties()->isTeam() && $damager_session->getTeam() !== null) {
                if ($damager_session->getTeam()->equals($session->getTeam())) {
                    $event->cancel();
                    return;
                }
            }
            $this->lastHit[$player->getXuid()] = [
                'damager' => $damager_session,
                'time' => time() + 15
            ];
        }
        $player->setScoreTag(TextFormat::colorize('&f' . round(($player->getHealth() + $player->getAbsorption()), 1) . '&c♥'));
    }
    
    public function handleRegainHealth(EntityRegainHealthEvent $event): void {
        $cause = $event->getRegainReason();
        $entity = $event->getEntity();

        if (!$entity instanceof Living) {
            return;
        }

        if ($cause === EntityRegainHealthEvent::CAUSE_SATURATION) {
            $event->cancel();
            return;
        }
        $entity->setScoreTag(TextFormat::colorize('&f' . round(($entity->getHealth() + $entity->getAbsorption()), 1) . '&c♥'));
    }

    public function handleChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        $game = UHC::getInstance()->getGame();

        if ($game->getProperties()->isGlobalMute() && !$player->hasPermission('globalmute.bypass')) {
            $event->cancel();
            return;
        }
    }

    public function handleDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);
        $game = UHC::getInstance()->getGame();

        if ($session === null || $game->getStatus() !== GameStatus::RUNNING) {
            return;
        }
        $message = '&c' . $player->getName() . ' &7[&f' . $session->getKills() . '&7] &edied'; 

        if (isset($this->lastHit[$player->getXuid()])) {
            $data = $this->lastHit[$player->getXuid()];
            
            if ($data['time'] > time()) {
                /** @var Session */
                $damager = $this->lastHit[$player->getXuid()];
                $damager->addKill();

                $message = '&c' . $player->getName() . ' &7[&f' . $session->getKills() . '&7] &ewas slain by &c' . $damager->getName() . ' &7[&f' . $damager->getKills() . '&7]';
            }
        }
        $session->setSpectator(true);
        $player->setSpawn($player->getPosition()->add(0, 3, 0));

        $game->getInventoryCache()->addInventory($player->getXuid(), $player->getArmorInventory()->getContents(), $player->getInventory()->getContents());
        $game->getPositionCache()->addPosition($player->getXuid(), $player->getPosition());
        $game->checkWinner();

        $event->setDeathMessage(TextFormat::colorize($message));
    }

    public function handleExhaust(PlayerExhaustEvent $event): void {
        $game = UHC::getInstance()->getGame();

        if ($game->getStatus() < GameStatus::RUNNING) {
            $event->cancel();
        }
    }
    
    public function handleJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        $session?->join();

        $event->setJoinMessage(TextFormat::colorize('&7[&a+&7] &a' . $player->getName()));
    }
    
    public function handleMove(PlayerMoveEvent $event): void {
        $from = $event->getFrom();
        $player = $event->getPlayer();
        $to = $event->getTo();
        $game = UHC::getInstance()->getGame();
        
        if ($game->getStatus() > GameStatus::STARTING) {
            if (!$from->equals($to)) {
                if (!$game->getBorder()->insideBorder($player)) {
                    $game->getBorder()->teleportInside($player);
                }
            }
        }
    }

    public function handleLogin(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        if ($session === null) {
            SessionFactory::create($player);
        } else {
            if ($session->getName() !== $player->getName()) {
                $session->setName($player->getName());
            }
        }
    }

    public function handleQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $session = SessionFactory::get($player);

        $session?->quit();

        $event->setQuitMessage(TextFormat::colorize('&7[&c-&7] &c' . $player->getName()));
    }
}