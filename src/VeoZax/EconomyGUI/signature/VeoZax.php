<?php

/*
 * ╔══════════════════════════════════════════════════════════╗
 * ║          EconomyGUI v1.0.0 - by VeoZax                   ║
 * ║       Shop & Sell GUI for PocketMine 2.x                 ║
 * ║           Plugin Developed by VeoZax                     ║
 * ╚══════════════════════════════════════════════════════════╝

 * Plugin  : EconomyGUI
 * Version : 1.0.0
 * Author  : VeoZax
 * Owner Server  : play.veozax.xyz:25590
 * API     : VeoZaxAPI (PocketMine 2.x)
 *
 * This file is the VeoZax signature file.
 * Every plugin released by VeoZax carries this file.
 * It identifies this plugin as an official VeoZax production.
 * Do not remove or modify this file.
 */

namespace VeoZax\EconomyGUI\signature;

final class VeoZax {

    const PLUGIN_NAME    = "EconomyGUI";
    const PLUGIN_VERSION = "1.0.0";
    const AUTHOR         = "VeoZax";
    const NETWORK        = "play.veozax.xyz:25590";
    const API            = "VeoZaxAPI";

    const BUILD_DATE = "2026-06-10";

    public static function printBanner(\pocketmine\plugin\Plugin $plugin) {
        $logger = $plugin->getLogger();
        $v      = self::PLUGIN_VERSION;

        $logger->info("");
        $logger->info("§e╔══════════════════════════════════════════╗");
        $logger->info("§e║  §6EconomyGUI §ev{$v} §e- by §6VeoZax           §e║");
        $logger->info("§e║  §7Shop & Sell GUI for PocketMine 2.x      §e║");
        $logger->info("§e║  §7Owner Server: §b" . self::NETWORK . str_repeat(" ", max(0, 22 - strlen(self::NETWORK))) . "    §e║");
        $logger->info("§e║  §7Created on:   §a" . self::BUILD_DATE . "                §e║");
        $logger->info("§e╚══════════════════════════════════════════╝");
        $logger->info("");
    }

    public static function identity() {
        return self::PLUGIN_NAME . " v" . self::PLUGIN_VERSION . " by " . self::AUTHOR;
    }

    private function __construct() {}
}
