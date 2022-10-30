<?php

declare(strict_types=1);

namespace uhc\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use uhc\session\Session;
use uhc\session\SessionFactory;

final class TellCommand extends Command {

    public function __construct() {
        parent::__construct('tell', KnownTranslationFactory::pocketmine_command_tell_description(), KnownTranslationFactory::commands_message_usage(), ['w', 'msg']);
    }
    
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::colorize('&cUse /tell [player] [message]'));
            return;
        }
        $player = $sender->getServer()->getPlayerByPrefix(array_shift($args));
        
        if ($player === $sender) {
            $sender->sendMessage(TextFormat::colorize('&cYou can\'t send a message to yourself'));
            return;
        }
        
        if (!$player instanceof Player) {
            $sender->sendMessage(TextFormat::colorize('&cPlayer not found.'));
            return;
        }
        $message = implode(' ', $args);
        
        $sender->sendMessage(TextFormat::colorize('&eTo ' . $player->getDisplayName() . ': &f' . $message));
        $name = $sender instanceof Player ? $sender->getDisplayName() : $sender->getName();
        $player->sendMessage(TextFormat::colorize('&eFrom ' . $name . ': &f' . $message));

        $sessions = array_filter(SessionFactory::getAll(), function (Session $s) use ($sender, $player): bool {
            return $s->isOnline() && $s->getPlayer() !== $sender && $s->getPlayer() !== $player;
        });
        
        foreach ($sessions as $session) {
            $session->getPlayer()?->sendMessage(TextFormat::colorize('&7' . $name . ' to ' . $player->getDisplayName() . ': &f' . $message));
        }
    }
}