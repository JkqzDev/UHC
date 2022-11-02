<?php

declare(strict_types=1);

namespace uhc\session\data;

use pocketmine\player\Player;

final class DeviceData {

    public const UNKNOWN = 0;
    public const ANDROID = 1;
    public const IOS = 2;
    public const OSX = 3;
    public const FIREOS = 4;
    public const VRGEAR = 5;
    public const VRHOLOLENS = 6;
    public const WINDOWS_10 = 7;
    public const WINDOWS_32 = 8;
    public const DEDICATED = 9;
    public const TVOS = 10;
    public const PS4 = 11;
    public const SWITCH = 12;
    public const XBOX = 13;
    public const LINUX = 20;
    
    public const KEYBOARD = 1;
    public const TOUCH = 2;
    public const CONTROLLER = 3;
    public const MOTION_CONTROLLER = 4;
    
    static private array $deviceOSVals = [
        self::ANDROID => 'Android',
        self::IOS => 'iOS',
        self::OSX => 'OSX',
        self::FIREOS => 'FireOS',
        self::VRGEAR => 'VRGear',
        self::VRHOLOLENS => 'VRHololens',
        self::WINDOWS_10 => 'Windows 10',
        self::WINDOWS_32 => 'Windows 32',
        self::DEDICATED => 'Dedicated',
        self::TVOS => 'TVOS',
        self::PS4 => 'Play Station',
        self::SWITCH => 'Nintendo Switch',
        self::XBOX => 'Xbox',
        self::LINUX => 'Linux'
    ];

    static private array $inputVals = [
        self::KEYBOARD => 'Keyboard',
        self::TOUCH => 'Touch',
        self::CONTROLLER => 'Controller',
        self::MOTION_CONTROLLER => 'Motion-Controller'
    ];
    
    static public function getOS(Player $player): string {
        $extraData = $player->getNetworkSession()->getPlayerInfo()->getExtraData();   
        return self::$deviceOSVals[$extraData['DeviceOS']] ?? 'Unknown';
    }
    
    static public function getInput(Player $player): string {
        $extraData = $player->getNetworkSession()->getPlayerInfo()->getExtraData();
        return self::$inputVals[$extraData['CurrentInputMode']] ?? 'Unknown';
    }
    
    static public function getOSInt(Player $player): int {
        $extraData = $player->getNetworkSession()->getPlayerInfo()->getExtraData();
        return $extraData['DeviceOS'];
    }
    
    static public function getInputInt(Player $player): int {
        $extraData = $player->getNetworkSession()->getPlayerInfo()->getExtraData();
        return $extraData['CurrentInputMode'];
    }
}