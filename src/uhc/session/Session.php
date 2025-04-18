<?php

declare(strict_types=1);

namespace uhc\session;

use pocketmine\block\BlockLegacyIds;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use uhc\game\GameStatus;
use uhc\player\DisconnectedFactory;
use uhc\session\data\DeviceData;
use uhc\session\data\KitData;
use uhc\session\scoreboard\ScoreboardBuilder;
use uhc\session\scoreboard\ScoreboardTrait;
use uhc\team\Team;
use uhc\team\TeamFactory;
use uhc\UHC;

final class Session {
    use ScoreboardTrait;

    public function __construct(
        private string $uuid,
        private string $xuid,
        private string $name,
        private int    $kills = 0,
        private int    $deviceId = 0,
        private int    $inputId = 0,
        private bool   $host = false,
        private bool   $spectator = false,
        private bool   $scattered = false,
        private bool   $inPractice = false,
        private ?Team  $team = null
    ) {
        $this->setScoreboard(new ScoreboardBuilder($this, '&l&bUHC&r'));
    }

    public function getXuid(): string {
        return $this->xuid;
    }

    public function getKills(): int {
        return $this->kills;
    }

    public function getInputId(): int {
        return $this->inputId;
    }

    public function isHost(): bool {
        return $this->host;
    }

    public function isOnline(): bool {
        return $this->getPlayer() !== null;
    }

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerByRawUUID($this->uuid);
    }

    public function getTeam(): ?Team {
        return $this->team;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function setSpectator(bool $spectator): void {
        $this->spectator = $spectator;
    }

    public function setHost(bool $host): void {
        $this->host = $host;
    }

    public function setScattered(bool $scattered): void {
        $this->scattered = $scattered;
    }

    public function setInPractice(bool $inPractice): void {
        $this->inPractice = $inPractice;
    }

    public function setTeam(?Team $team): void {
        $this->team = $team;
    }

    public function addKill(): void {
        $this->kills++;
    }

    public function update(): void {
        $this->scoreboard?->update();
    }

    public function scatter(): void {
        $player = $this->getPlayer();

        if ($player === null) {
            return;
        }
        $game = UHC::getInstance()->getGame();
        $border = $game->getBorder()->getSize() - 1;
        $world = $game->getWorld();

        if ($world === null) {
            return;
        }

        UHC::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function () use ($player, $game, $border, $world): void {
            $x = mt_rand(-$border, $border);
            $z = mt_rand(-$border, $border);

            if (!$world->isChunkLoaded($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE)) {
                $world->loadChunk($x >> Chunk::COORD_BIT_SIZE, $z >> Chunk::COORD_BIT_SIZE);
            }
            $y = $world->getHighestBlockAt($x, $z);
            $position = new Position($x, $y, $z, $world);

            if (in_array($world->getBlock($position->asVector3()->add(0, -1, 0))->getId(), [BlockLegacyIds::FLOWING_LAVA, BlockLegacyIds::LAVA, BlockLegacyIds::WATER, BlockLegacyIds::FLOWING_WATER])) {
                $this->scatter();
                return;
            }

            if ($game->getStatus() !== GameStatus::RUNNING) {
                $player->setImmobile();
            }
            KitData::default($player);
            $player->teleport(Position::fromObject($position->add(0, 1, 0), $world));

            $this->spectator = false;
            $this->scattered = true;
        }));
    }

    public function join(): void {
        $player = $this->getPlayer();

        if ($player === null) {
            return;
        }
        $game = UHC::getInstance()->getGame();

        $this->deviceId = DeviceData::getOSInt($player);
        $this->inputId = DeviceData::getInputInt($player);

        $this->scoreboard?->spawn();
        $pk = GameRulesChangedPacket::create([
            'showCoordinates' => new BoolGameRule(true, false)
        ]);
        $player->getNetworkSession()->sendDataPacket($pk);

        switch ($game->getStatus()) {
            case GameStatus::WAITING:
                $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                $this->clear();

                KitData::lobby($player);
                break;

            case GameStatus::SCATTERING:
                if ($game->getProperties()->isTeam()) {
                    $team = $this->team;

                    if ($team !== null) {
                        if (!$team->isScattered()) {
                            $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                            $player->setGamemode(GameMode::ADVENTURE());
                            $this->clear();
                        } else {
                            if (!$this->scattered) {
                                $player->teleport($game->getWorld()->getSpawnLocation());

                                $this->spectator = true;
                                $this->clear();
                                KitData::spectator($player);
                            } else {
                                DisconnectedFactory::get($this->xuid)?->join($player);
                            }
                        }
                    } else {
                        $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                        $player->setGamemode(GameMode::ADVENTURE());
                        $this->clear();

                        TeamFactory::create($this);
                    }
                } else {
                    if (!$this->scattered) {
                        $player->teleport($game->getWorld()->getSpawnLocation());
                        $player->setGamemode(GameMode::ADVENTURE());

                        $this->clear();
                    } else {
                        DisconnectedFactory::get($this->xuid)?->join($player);
                    }
                }
                break;

            case GameStatus::STARTING:
            case GameStatus::RUNNING:
            case GameStatus::RESTARTING:
                if ($this->isAlive()) {
                    if (!$this->scattered) {
                        $player->teleport($game->getWorld()->getSpawnLocation());

                        $this->spectator = true;
                        $this->clear();
                        KitData::spectator($player);
                    } else {
                        DisconnectedFactory::get($this->xuid)?->join($player);
                    }
                } elseif ($this->isSpectator() && $player->getGamemode() === GameMode::SURVIVAL()) {
                    $this->clear();
                    KitData::spectator($player);
                }
                break;
        }
        $player->setNameTag(TextFormat::colorize('&7' . $player->getName() . ' &b[' . DeviceData::getOS($player) . ']'));
        $player->setScoreTag(TextFormat::colorize('&f' . round(($player->getHealth() + $player->getAbsorption()), 1) . ' &c♥'));
    }

    public function clear(): void {
        $player = $this->getPlayer();

        if ($player === null) {
            return;
        }
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
        $player->getCraftingGrid()->clearAll();

        $player->getEffects()->clear();

        $player->setHealth($player->getMaxHealth());
        $player->getHungerManager()->setFood($player->getHungerManager()->getMaxFood());

        $player->getXpManager()->setXpAndProgress(0, 0.0);

        $player->extinguish();
    }

    public function isScattered(): bool {
        return $this->scattered;
    }

    public function isAlive(): bool {
        return !$this->spectator && !$this->host;
    }

    public function isSpectator(): bool {
        return $this->spectator;
    }

    public function getName(): string {
        return $this->name;
    }

    public function quit(): void {
        $player = $this->getPlayer();

        if ($player === null) {
            return;
        }

        if ($this->isInPractice()) {

            return;
        }
        $game = UHC::getInstance()->getGame();

        if ($game->getStatus() !== GameStatus::WAITING || $game->getStatus() !== GameStatus::RESTARTING) {
            if ($this->isAlive() && $this->isScattered()) {
                DisconnectedFactory::create($this, $player);
            }
        }
    }

    public function isInPractice(): bool {
        return $this->inPractice;
    }
}