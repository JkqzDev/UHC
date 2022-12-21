<?php

declare(strict_types=1);

namespace uhc\team\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\game\GameStatus;
use uhc\session\data\DeviceData;
use uhc\session\SessionFactory;
use uhc\team\Team;
use uhc\team\TeamFactory;
use uhc\UHC;

final class TeamCommand extends Command {

    private array $invites = [];

    public function __construct() {
        parent::__construct('team', 'Command for team');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            return;
        }
        $game = UHC::getInstance()->getGame();
        $session = SessionFactory::get($sender);

        if ($session === null) {
            return;
        }

        if (!$game->getProperties()->isTeam()) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t use the command'));
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage(TextFormat::colorize('&cUse /team help'));
            return;
        }

        switch (strtolower($args[0])) {
            case 'help':
                $lines = [
                    '&eTeam Commands',
                    '&e/team create &7- Use the command to create a team',
                    '&e/team invite [player] &7- Use the command to invite a player to the team (only owners can invite)',
                    '&e/team accept [player] &7- Use the command to accept a player\'s invite',
                    '&e/team leave &7- Use the command to leave the team',
                    '&e/team disband &7- Use the command to remove the team (only owners can use them)'
                ];

                $sender->sendMessage(implode(PHP_EOL, array_map(function ($string) {
                    return TextFormat::colorize($string);
                }, $lines)));
                break;

            case 'create':
                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if (!$session->isAlive()) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if ($session->getTeam() !== null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou already have a team'));
                    return;
                }
                TeamFactory::create($session);
                $team = $session->getTeam();

                $sender->getServer()->broadcastMessage(TextFormat::colorize('&6' . $sender->getName() . ' has created Team #' . $team->getId()));
                $sender->sendMessage(TextFormat::colorize('&eYou have created your team. Now invite the players'));
                break;

            case 'invite':
                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if (!$session->isAlive()) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if ($session->getTeam() === null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou don\'t have a team'));
                    return;
                }
                $team = $session->getTeam();

                if (!$team->isOwner($session)) {
                    $sender->sendMessage(TextFormat::colorize('&cYou don\'t have permissions to invite players to the team'));
                    return;
                }

                if (!isset($args[1])) {
                    $sender->sendMessage(TextFormat::colorize('&cUse /team invite [player]'));
                    return;
                }
                $player = $sender->getServer()->getPlayerByPrefix($args[1]);

                if (!$player instanceof Player) {
                    $sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
                    return;
                }
                $target = SessionFactory::get($player);

                if ($target === null) {
                    $sender->sendMessage(TextFormat::colorize('&cPlayer offline.'));
                    return;
                }

                if ($target->getTeam() !== null) {
                    $sender->sendMessage(TextFormat::colorize('&cThe player already has a team'));
                    return;
                }

                if (count($team->getMembers()) === TeamFactory::getProperties()->getMaxPlayers()) {
                    $sender->sendMessage(TextFormat::colorize('&cTeam is full'));
                    return;
                }

                if ($target->getInputId() === DeviceData::KEYBOARD) {
                    if (count($team->getKeyboardMembers()) === TeamFactory::getProperties()->getMaxKeyboards()) {
                        unset($this->invites[$session->getXuid()][$args[1]]);
                        $sender->sendMessage(TextFormat::colorize('&cYou can\'t invite any keyboard players because you\'re already at the limit'));
                        return;
                    }
                }

                $this->invites[$target->getXuid()][$sender->getName()] = $team;

                $sender->sendMessage(TextFormat::colorize('&eYou have invited the player to your team'));
                $player->sendMessage(TextFormat::colorize('&eYou received an invitation from ' . $sender->getName() . ' to join the team'));
                break;

            case 'accept':
                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if (!$session->isAlive()) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if ($session->getTeam() !== null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou already have a team'));
                    return;
                }

                if (!isset($args[1])) {
                    $sender->sendMessage(TextFormat::colorize('&cUse /team accept [player]'));
                    return;
                }

                if (!isset($this->invites[$session->getXuid()])) {
                    $sender->sendMessage(TextFormat::colorize('&cYou have no invites from any player'));
                    return;
                }
                $invites = $this->invites[$session->getXuid()];

                if (!isset($invites[$args[1]])) {
                    $sender->sendMessage(TextFormat::colorize('&cYou don\'t have any invites from this player'));
                    return;
                }
                /** @var Team */
                $team = $invites[$args[1]];

                if (TeamFactory::get($team->getId()) === null) {
                    unset($this->invites[$session->getXuid()][$args[1]]);
                    return;
                }

                if (count($team->getMembers()) === TeamFactory::getProperties()->getMaxPlayers()) {
                    unset($this->invites[$session->getXuid()][$args[1]]);
                    $sender->sendMessage(TextFormat::colorize('&cTeam is full'));
                    return;
                }

                if ($session->getInputId() === DeviceData::KEYBOARD) {
                    if (count($team->getKeyboardMembers()) === TeamFactory::getProperties()->getMaxKeyboards()) {
                        unset($this->invites[$session->getXuid()][$args[1]]);
                        $sender->sendMessage(TextFormat::colorize('&cThe team already has the spaces occupied for keyboards'));
                        return;
                    }
                }
                unset($this->invites[$session->getXuid()]);

                $session->setTeam($team);
                $team->addMember($session);
                $team->broadcast('&e' . $sender->getName() . ' has joined the team');
                break;

            case 'leave':
                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if (!$session->isAlive()) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if ($session->getTeam() === null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou don\'t have a team'));
                    return;
                }
                $team = $session->getTeam();

                if ($team->isOwner($session)) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t leave the team, you must disband'));
                    return;
                }
                $session->setTeam(null);

                $sender->sendMessage(TextFormat::colorize('&cYou have left the team'));
                $team->removeMember($session);
                $team->broadcast('&c' . $sender->getName() . ' has left the team');
                break;

            case 'disband':
                if ($game->getStatus() !== GameStatus::WAITING) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if (!$session->isAlive()) {
                    $sender->sendMessage(TextFormat::colorize('&cYou can\'t use this command'));
                    return;
                }

                if ($session->getTeam() === null) {
                    $sender->sendMessage(TextFormat::colorize('&cYou don\'t have a team'));
                    return;
                }
                $team = $session->getTeam();

                if (!$team->isOwner($session)) {
                    $sender->sendMessage(TextFormat::colorize('&cYou don\'t have permission for disband'));
                    return;
                }
                $team->disband();
                break;

            default:
                $sender->sendMessage(TextFormat::colorize('&cUse /team help'));
                break;
        }
    }
}