<?php

declare(strict_types=1);

namespace uhc\twitter;

use juqn\twitter\Twitter;
use uhc\scenario\Scenario;
use uhc\scenario\ScenarioFactory;
use uhc\session\Session;
use uhc\session\SessionFactory;
use uhc\team\Team;
use uhc\team\TeamFactory;
use uhc\UHC;

final class TwitterFeed {
    
    static public function sendAnnounceMessage(int $waitingTime): void {
        $twitter = new Twitter(TwitterData::CONSUMER_KEY, TwitterData::CONSUMER_SECRET, TwitterData::ACCESS_TOKEN, TwitterData::ACCESS_SECRET);
        $game = UHC::getInstance()->getGame();
        $scenarios = array_filter(ScenarioFactory::getAll(), function (Scenario $scenario): bool {
            return $scenario->isEnabled();
        });
        
        $gameType = !$game->getProperties()->isTeam() ? 'FFA' : 'TO' . TeamFactory::getProperties()->getMaxPlayers();
        $scenarioList = implode(', ', array_map(function (Scenario $scenario) {
            return $scenario->getName();
        }, $scenarios));
        $host = $game->getProperties()->getHost() ?? 'None';
        $message = 'Cloud UHC' . PHP_EOL . PHP_EOL . 'Type: ' . $gameType . ' | ' . $scenarioList . PHP_EOL . 'Host: ' . $host . PHP_EOL . 'Starting in ' . $waitingTime . ' ' . ($waitingTime === 1 ? 'minute' : 'minutes') . PHP_EOL . PHP_EOL . 'IP: na.clouduhc.lol - 25600';
        
        $twitter->send($message);
    }
    
    static public function sendWinMessage(): void {
        $twitter = new Twitter(TwitterData::CONSUMER_KEY, TwitterData::CONSUMER_SECRET, TwitterData::ACCESS_TOKEN, TwitterData::ACCESS_SECRET);
        $game = UHC::getInstance()->getGame();
        
        if ($game->getProperties()->isTeam()) {
            $teams = array_filter(TeamFactory::getAll(), function (Team $team): bool {
                return $team->isAlive() && $team->isScattered();
            });
            $team = array_values($teams)[0];
            
            $message = 'Cloud UHC' . PHP_EOL . PHP_EOL . 'Congratulations to team #' . $team->getId() . ' for winning the UHC TO' . TeamFactory::getProperties()->getMaxPlayers() . ' with ' . $team->getKills() . ' team kills.' . PHP_EOL . PHP_EOL . 'Thanks for playing everyone!';
       } else {
           $players = array_filter(SessionFactory::getAll(), function (Session $session): bool {
                return $session->isAlive() && $session->isScattered();
            });
            $player = array_values($players)[0];
            
            $message = 'Cloud UHC' . PHP_EOL . PHP_EOL . 'Congratulations to the player ' . $player->getName() . ' for winning the UHC FFA with ' . $player->getKills() . ' kills.' . PHP_EOL . PHP_EOL . 'Thanks for playing everyone!';
       }
       $twitter->send($message);
    }
}