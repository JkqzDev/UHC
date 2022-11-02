<?php

declare(strict_types=1);

namespace uhc\discord;

use CortexPE\DiscordWebhookAPI\Embed;
use CortexPE\DiscordWebhookAPI\Message;
use CortexPE\DiscordWebhookAPI\Webhook;
use DateTime;
use uhc\scenario\Scenario;
use uhc\scenario\ScenarioFactory;
use uhc\session\Session;
use uhc\session\SessionFactory;
use uhc\team\Team;
use uhc\team\TeamFactory;
use uhc\UHC;

final class DiscordFeed {
    
    static public function sendAnnounceMessage(int $waitingTime): void {
        $webhook = new Webhook(DiscordWebhook::CHANNEL_ANNOUNCEMENTS);
        $message = new Message();
        $embed = new Embed();
        
        $dateTime = new DateTime();
        $game = UHC::getInstance()->getGame();
        $scenarios = array_filter(ScenarioFactory::getAll(), function (Scenario $scenario): bool {
            return $scenario->isEnabled();
        });
        
        $embed->setTitle('Upcoming UHC');
        $embed->setColor(hexdec('00ffff'));
        $embed->addField('Type', !$game->getProperties()->isTeam() ? 'FFA' : 'TO' . TeamFactory::getProperties()->getMaxPlayers());
        $embed->addField('Host', $game->getProperties()->getHost() ?? 'None');
        $embed->addField('Scenarios', implode(PHP_EOL, array_map(function (Scenario $scenario) {
            return '• ' . $scenario->getName() . ' - ' . $scenario->getDescription();
        }, $scenarios)));
        $embed->addField('Starting in', $waitingTime . ' ' . ($waitingTime === 1 ? 'minute' : 'minutes'));
        $embed->setTimestamp($dateTime);
        
        $message->setContent('@everyone');
        $message->addEmbed($embed);
        $webhook->send($message);
    }
    
    static public function sendKillMessage(string $message): void {
        $game = UHC::getInstance()->getGame();
        
        $webhook = new Webhook(DiscordWebhook::CHANNEL_KILLS);
        $message = new Message();
        $embed = new Embed();
        
        $embed->setTitle('Game Kill');
        $embed->setColor(hexdec('00ffff'));
        $embed->addFiel('Minute ' . (intval) $game->getGlobalTime() / 60), $message);
        $message->addEmbed($embed);
        $webhook->send($message);
    }
    
    static public function sendWinMessage(): void {
        $game = UHC::getInstance()->getGame();
        
        $webhook = new Webhook(DiscordWebhook::CHANNEL_WINNERS);
        $message = new Message();
        $embed = new Embed();
        
        $embed->setTitle('Game Winner');
        $embed->setColor(hexdec('00ffff'));
        
        if ($game->getProperties()->isTeam()) {
            $teams = array_filter(TeamFactory::getAll(), function (Team $team): bool {
                return $team->isAlive() && $team->isScattered();
            });
            $team = array_values($teams)[0];
            
            $embed->addField('Time elapsed', gmdate('H:i:s', $game->getGlobalTime()));
            $embed->addField('Team winner', 'Team #' . $team->getId());
            $embed->addField('Team kills', implode(PHP_EOL, array_map(function (Session $session) {
                return '• ' . $member->getName() . ' - ' . $member->getKills() . ' kill(s)';
            }, $team->getMembers())));
        } else {
            $players = array_filter(SessionFactory::getAll(), function (Session $session): bool {
                return $session->isAlive() && $session->isScattered();
            });
            $player = array_values($players)[0];
            
            $embed->addField('Winner', $player->getName());
            $embed->addField('Kills', $player->getKills() . ' kill(s)');
        }
        $message->addEmbed($embed);
        $webhook->send($message);
    }
}