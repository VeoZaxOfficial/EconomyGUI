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

class ShopGUI extends BaseGUI {

    protected function getTitleKey() {
        return "shop-title";
    }

    protected function populateOnOpen(Player $who) {
        $this->plugin->fillShopCategories($this);
        $who->sendMessage($this->plugin->cfg()->getMessage("shop-opened"));
    }
}
