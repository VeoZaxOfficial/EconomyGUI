<?php

/*
 * ╔══════════════════════════════════════════════════════════╗
 * ║          EconomyGUI v1.0.0 - by VeoZax                   ║
 * ║       Shop & Sell GUI for PocketMine 2.x                 ║
 * ║           Plugin Developed by VeoZax                     ║
 * ╚══════════════════════════════════════════════════════════╝
 */

namespace VeoZax\EconomyGUI\gui;

use VeoZax\EconomyGUI\EconomyGUI;
use pocketmine\Player;

class SellGUI extends BaseGUI {

    protected function getTitleKey() {
        return "sell-title";
    }

    protected function populateOnOpen(Player $who) {
        $this->plugin->fillSellCategories($this);
        $who->sendMessage($this->plugin->cfg()->getMessage("sell-opened"));
    }
}
