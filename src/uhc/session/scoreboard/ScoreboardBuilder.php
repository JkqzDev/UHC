<?php

declare(strict_types=1);

namespace uhc\session\scoreboard;

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\utils\TextFormat;
use uhc\game\GameStatus;
use uhc\session\SessionFactory;
use uhc\session\Session;
use uhc\scenario\ScenarioFactory;
use uhc\scenario\Scenario;
use uhc\UHC;

final class ScoreboardBuilder {

    public function __construct(
        private Session $session,
        private string $title = '',
        private array $lines = []
    ) {}

    public function spawn(): void {
        $packet = SetDisplayObjectivePacket::create(
            SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR,
            $this->session->getPlayer()?->getName(),
            TextFormat::colorize($this->title),
            'dummy',
            SetDisplayObjectivePacket::SORT_ORDER_ASCENDING
        );
        $this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
    }

    public function despawn(): void {
        $pk = RemoveObjectivePacket::create(
            $this->session->getPlayer()?->getName()
        );
        $this->session->getPlayer()?->getNetworkSession()->sendDataPacket($pk);
    }

    public function clear(): void {
        $packet = new SetScorePacket;
        $packet->entries = $this->lines;
        $packet->type = SetScorePacket::TYPE_REMOVE;
        $this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
        $this->lines = [];
    }

    public function addLine(string $line, ?int $id = null): void {
        $id = $id ?? count($this->lines);

        $entry = new ScorePacketEntry;
        $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;

        if (isset($this->lines[$id])) {
            $pk = new SetScorePacket;
            $pk->entries[] = $this->lines[$id];
            $pk->type = SetScorePacket::TYPE_REMOVE;
            $this->session->getPlayer()?->getNetworkSession()->sendDataPacket($pk);
            unset($this->lines[$id]);
        }
        $entry->scoreboardId = $id;
        $entry->objectiveName = $this->session->getPlayer()?->getName();
        $entry->score = $id;
        $entry->actorUniqueId = $this->session->getPlayer()?->getId();
        $entry->customName = $line;
        $this->lines[$id] = $entry;

        $packet = new SetScorePacket;
        $packet->entries[] = $entry;
        $packet->type = SetScorePacket::TYPE_CHANGE;
        $this->session->getPlayer()?->getNetworkSession()->sendDataPacket($packet);
    }
    
    public function update(): void {
        $session = $this->session;
        $player = $session->getPlayer();
        
        $game = UHC::getInstance()->getGame();

        if ($player === null || !$player->isOnline()) {
            return;
        }
        $lines = [
            '&7'
        ];
        
        switch ($game->getStatus()) {
            case GameStatus::WAITING:
                $players = array_filter(SessionFactory::getAll(), function (Session $target): bool {
                    return $target->isOnline() && !$target->isHost();
                });
                $scenarios = array_values(array_filter(ScenarioFactory::getAll(), function (Scenario $scenario): bool {
                    return $scenario->isEnabled();
                }));
                
                $lines[] = ' &fPlayers: &b' . count($players);
                $lines[] = ' &fMode: &b' . (!$game->getProperties()->isTeam() ? 'FFA' : 'TO');
                $lines[] = ' &fHost: &b' . ($game->getProperties()->getHost() ?? 'None');
                $lines[] = '&r';
                $lines[] = ' &fScenarios:';
                
                if (count($scenarios) === 0) {
                    $lines[] = ' &4No scenarios';
                } else {
                    for ($i = 0; $i < 3; $i++) {
                        if (isset($scenarios[$i])) {
                            $lines[] = ' &7•&b ' . $scenarios[$i]->getName();
                        }
                    }
                    
                    if (count($scenarios) > 3) {
                        $lines[] = '  &band ' . (count($scenarios) - 3) . ' more..';
                    }
                }
                break;
        }
        $lines[] = '&7&r';
        $this->clear();

        foreach ($lines as $line) {
            $this->addLine(TextFormat::colorize($line));
        }
    }
}