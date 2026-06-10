<?php

/*
 * ╔══════════════════════════════════════════════════════════╗
 * ║          EconomyGUI v1.0.0 - by VeoZax                   ║
 * ║       Shop & Sell GUI for PocketMine 2.x                 ║
 * ║           Plugin Developed by VeoZax                     ║
 * ╚══════════════════════════════════════════════════════════╝
 */

namespace VeoZax\EconomyGUI;

use VeoZax\EconomyGUI\gui\ShopGUI;
use VeoZax\EconomyGUI\gui\SellGUI;
use VeoZax\EconomyGUI\gui\BaseGUI;
use VeoZax\EconomyGUI\manager\ConfigManager;
use VeoZax\EconomyGUI\signature\VeoZax;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\InventoryTransaction;
use pocketmine\item\Item;
use pocketmine\Player;

class EconomyGUI extends PluginBase implements Listener {

    public $activeGUI = [];

    private $guiType = [];

    private $configManager;

    private $eco;

    private $cooldowns = [];

    public function onEnable() {
        @mkdir($this->getDataFolder());

        foreach (array("config.yml", "shop.yml", "sell.yml") as $file) {
            if (!file_exists($this->getDataFolder() . $file)) {
                $this->saveResource($file);
            }
        }

        $this->configManager = new ConfigManager($this->getDataFolder());

        $this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        if ($this->eco === null) {
            $this->getLogger()->warning("EconomyAPI not found! Please Install it first");
        }

        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        VeoZax::printBanner($this);
        $this->getLogger()->info("§aEconomyGUI Plugin is now Activated - " . VeoZax::identity());
    }

    public function cfg() {
        return $this->configManager;
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
        switch ($cmd->getName()) {

            case "shop":
                if (!($sender instanceof Player)) { $this->console($sender); return true; }
                if (!$sender->hasPermission("economygui.shop")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                $this->openShop($sender);
                return true;

            case "sell":
                if (!($sender instanceof Player)) { $this->console($sender); return true; }
                if (!$sender->hasPermission("economygui.sell")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                $this->openSell($sender);
                return true;

            case "shopreload":
                if (!$sender->hasPermission("economygui.reload")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                $this->configManager->reload();
                $sender->sendMessage($this->configManager->getMessage("shop-reloaded"));
                return true;

            case "shopbalance":
                if (!($sender instanceof Player)) { $this->console($sender); return true; }
                if ($this->eco === null) { $sender->sendMessage("§cEconomyAPI not found."); return true; }
                $bal = $this->eco->myMoney($sender);
                $sender->sendMessage($this->configManager->getMessage("balance", array(
                    "symbol"  => $this->configManager->getSymbol(),
                    "balance" => number_format($bal),
                )));
                return true;

            case "shopgui-add":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdAdd($sender, $args);

            case "shopgui-remove":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdRemove($sender, $args);

            case "shopgui-addcat":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdAddCat($sender, $args, "shop");

            case "shopgui-removecat":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdRemoveCat($sender, $args, "shop");

            case "shopgui-listcat":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdListCat($sender, "shop");

            case "sellgui-add":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdSellAdd($sender, $args);

            case "sellgui-remove":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdSellRemove($sender, $args);

            case "sellgui-addcat":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdAddCat($sender, $args, "sell");

            case "sellgui-removecat":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdRemoveCat($sender, $args, "sell");

            case "sellgui-listcat":
                if (!$sender->hasPermission("economygui.admin")) {
                    $sender->sendMessage($this->configManager->getMessage("no-permission")); return true;
                }
                return $this->cmdListCat($sender, "sell");
        }
        return false;
    }

    private function cmdAdd(CommandSender $sender, array $args) {
        if (count($args) < 3) {
            $sender->sendMessage($this->configManager->getMessage("usage-add"));
            return true;
        }
        if (!preg_match('/^(\d+):(\d+)$/', $args[0], $m)) {
            $sender->sendMessage($this->configManager->getMessage("invalid-id"));
            return true;
        }
        $id        = (int)$m[1];
        $meta      = (int)$m[2];
        $price     = (int)$args[1];
        $amount    = (int)$args[2];
        $category  = isset($args[3]) ? strtolower($args[3]) : (string)$this->configManager->get("default-category", "general");
        $sellPrice = isset($args[4]) ? (int)$args[4] : null;

        if ($price <= 0 || $amount <= 0) {
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §cPrice and amount must be positive numbers.");
            return true;
        }

        $itemObj = Item::get($id, $meta, 1);
        $name    = "§r§e" . $itemObj->getName();
        $this->configManager->addShopItem($id, $meta, $name, $price, $amount, $category, $sellPrice);

        $sender->sendMessage($this->configManager->getMessage("item-added", array(
            "item"     => $itemObj->getName(),
            "id"       => $id,
            "meta"     => $meta,
            "category" => $category,
            "symbol"   => $this->configManager->getSymbol(),
            "price"    => number_format($price),
            "amount"   => $amount,
        )));
        return true;
    }

    private function cmdRemove(CommandSender $sender, array $args) {
        if (count($args) < 1) {
            $sender->sendMessage($this->configManager->getMessage("usage-remove"));
            return true;
        }
        if (!preg_match('/^(\d+):(\d+)$/', $args[0], $m)) {
            $sender->sendMessage($this->configManager->getMessage("invalid-id"));
            return true;
        }
        $id       = (int)$m[1];
        $meta     = (int)$m[2];
        $category = isset($args[1]) ? strtolower($args[1]) : null;

        $count = $this->configManager->removeShopItem($id, $meta, $category);
        if ($count > 0) {
            $sender->sendMessage($this->configManager->getMessage("item-removed", array("id" => $id, "meta" => $meta)));
        } else {
            $sender->sendMessage($this->configManager->getMessage("item-not-found", array("id" => $id, "meta" => $meta)));
        }
        return true;
    }

    private function cmdSellRemove(CommandSender $sender, array $args) {
        if (count($args) < 1) {
            $sender->sendMessage($this->configManager->getMessage("usage-sell-remove"));
            return true;
        }
        if (!preg_match('/^(\d+):(\d+)$/', $args[0], $m)) {
            $sender->sendMessage($this->configManager->getMessage("invalid-id"));
            return true;
        }
        $id       = (int)$m[1];
        $meta     = (int)$m[2];
        $category = isset($args[1]) ? strtolower($args[1]) : null;

        $count = $this->configManager->removeSellItem($id, $meta, $category);
        if ($count > 0) {
            $sender->sendMessage($this->configManager->getMessage("sell-item-removed", array("id" => $id, "meta" => $meta)));
        } else {
            $sender->sendMessage($this->configManager->getMessage("sell-item-not-found", array("id" => $id, "meta" => $meta)));
        }
        return true;
    }

    private function cmdAddCat(CommandSender $sender, array $args, $type) {
        $prefix = $type === "shop" ? "shop" : "sell";
        if (count($args) < 3) {
            $sender->sendMessage("§eUsage: /{$prefix}gui-addcat <key> <iconId> <displayName>");
            return true;
        }
        $key     = strtolower($args[0]);
        $iconId  = (int)$args[1];
        $display = implode(" ", array_slice($args, 2));

        if ($type === "shop") {
            $exists = $this->configManager->shopCategoryExists($key);
            $ok     = $exists ? false : $this->configManager->addShopCategory($key, $iconId, $display);
        } else {
            $exists = $this->configManager->sellCategoryExists($key);
            $ok     = $exists ? false : $this->configManager->addSellCategory($key, $iconId, $display);
        }

        if ($exists) {
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §cCategory §e{$key} §calready exists in the {$prefix}!");
        } else {
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §aCategory §e{$key} §acreated in §e{$prefix} §awith icon §e{$iconId} §aand name §r{$display}§a.");
        }
        return true;
    }

    private function cmdRemoveCat(CommandSender $sender, array $args, $type) {
        $prefix = $type === "shop" ? "shop" : "sell";
        if (count($args) < 1) {
            $sender->sendMessage("§eUsage: /{$prefix}gui-removecat <key>");
            return true;
        }
        $key = strtolower($args[0]);

        if ($type === "shop") {
            $ok = $this->configManager->removeShopCategory($key);
        } else {
            $ok = $this->configManager->removeSellCategory($key);
        }

        if ($ok) {
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §aCategory §e{$key} §aand all its items removed from §e{$prefix}§a.");
        } else {
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §cCategory §e{$key} §cnot found in {$prefix}.");
        }
        return true;
    }

    private function cmdListCat(CommandSender $sender, $type) {
        if ($type === "shop") {
            $cats = $this->configManager->getShopCategories();
        } else {
            $cats = $this->configManager->getSellCategories();
        }

        $prefix = $type === "shop" ? "Shop" : "Sell";
        if (empty($cats)) {
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §eNo categories in {$prefix} yet.");
            return true;
        }

        $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §a- {$prefix} Categories -");
        foreach ($cats as $key => $cat) {
            $name      = isset($cat["name"]) ? $cat["name"] : $key;
            $icon      = isset($cat["icon"]) ? (int)$cat["icon"] : 0;
            $itemCount = isset($cat["items"]) ? count($cat["items"]) : 0;
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §7  §e{$key} §7| §rName: {$name} §7| §7Icon: §e{$icon} §7| §7Items: §e{$itemCount}");
        }
        return true;
    }

    private function cmdSellAdd(CommandSender $sender, array $args) {
        if (count($args) < 3) {
            $sender->sendMessage($this->configManager->getMessage("usage-sell-add"));
            return true;
        }
        if (!preg_match('/^(\d+):(\d+)$/', $args[0], $m)) {
            $sender->sendMessage($this->configManager->getMessage("invalid-id"));
            return true;
        }
        $id       = (int)$m[1];
        $meta     = (int)$m[2];
        $price    = (int)$args[1];
        $amount   = (int)$args[2];
        $category = isset($args[3]) ? strtolower($args[3]) : (string)$this->configManager->get("default-category", "general");

        if ($price <= 0 || $amount <= 0) {
            $sender->sendMessage("§l§8[§cEconomy§eGUI§8]§r §cPrice and amount must be positive numbers.");
            return true;
        }

        $itemObj = Item::get($id, $meta, 1);
        $name    = "§r§e" . $itemObj->getName();
        $this->configManager->addSellItem($id, $meta, $name, $price, $amount, $category);

        $sender->sendMessage($this->configManager->getMessage("sell-item-added", array(
            "item"     => $itemObj->getName(),
            "id"       => $id,
            "meta"     => $meta,
            "category" => $category,
            "symbol"   => $this->configManager->getSymbol(),
            "price"    => number_format($price),
            "amount"   => $amount,
        )));
        return true;
    }


    private function console(CommandSender $s) {
        $s->sendMessage("§l§8[§cEconomy§eGUI§8]§r §cThis command must be run in-game.");
    }

    public function openShop(Player $player) {
        $name = strtolower($player->getName());
        $gui  = new ShopGUI($this, $player);
        $this->activeGUI[$name] = $gui;
        $this->guiType[$name]   = "shop";
        $player->addWindow($gui);
    }

    public function openSell(Player $player) {
        $name = strtolower($player->getName());
        $gui  = new SellGUI($this, $player);
        $this->activeGUI[$name] = $gui;
        $this->guiType[$name]   = "sell";
        $player->addWindow($gui);
    }

    public function fillShopCategories(BaseGUI $inv) {
        $inv->clearAll();
        foreach ($this->configManager->getShopCategories() as $cat) {
            if (!isset($cat["icon"], $cat["name"])) continue;
            $item = Item::get((int)$cat["icon"], 0, 1);
            $item->setCustomName($cat["name"]);
            $inv->setItem(count($inv->getContents()), $item);
        }
    }

    public function fillSellCategories(BaseGUI $inv) {
        $inv->clearAll();
        foreach ($this->configManager->getSellCategories() as $cat) {
            if (!isset($cat["icon"], $cat["name"])) continue;
            $item = Item::get((int)$cat["icon"], 0, 1);
            $item->setCustomName($cat["name"]);
            $inv->setItem(count($inv->getContents()), $item);
        }
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event) {
        $queue = $event->getTransaction();

        foreach ($queue->getTransactions() as $transaction) {
            $inv = $transaction->getInventory();

            if (!($inv instanceof BaseGUI)) continue;

            $player = $inv->getOwnerPlayer();
            if (!($player instanceof Player)) continue;

            $name = strtolower($player->getName());
            if (!isset($this->activeGUI[$name])) continue;

            $event->setCancelled(true);

            $inv->sendContents($player);
            $player->getInventory()->sendContents($player);

            $type = isset($this->guiType[$name]) ? $this->guiType[$name] : "shop";
            $slot = $transaction->getSlot();
            $item = $inv->getItem($slot);

            if ($type === "shop") {
                $this->handleShopClick($player, $inv, $item);
            } else {
                $this->handleSellClick($player, $inv, $item);
            }
            return;
        }
    }

    private function handleShopClick(Player $player, BaseGUI $inv, Item $item) {
        $itemName = $item->getCustomName();
        $backName = (string)$this->configManager->get("back-button-name", "§r§c« Back");

        if ($itemName === $backName) {
            $ref = $inv->getItem(13);
            if ($ref !== null && $ref->getCustomName() !== "") {
                foreach ($this->configManager->getShopCategories() as $cat) {
                    if (!isset($cat["items"])) continue;
                    foreach ($cat["items"] as $entry) {
                        if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
                        if ($ref->getCustomName() === $this->buildShopItemLore($entry)) {
                            $this->fillShopCategoryItems($inv, $cat);
                            return;
                        }
                    }
                }
            }
            $this->fillShopCategories($inv);
            return;
        }

        if (preg_match('/^§aBuy §e(\d+)x\n§7Price: §e[^\d]*([\d,]+)$/', $itemName, $m)) {
            $buyAmount = (int)$m[1];
            $price     = (int)str_replace(",", "", $m[2]);
            $this->processPurchase($player, $inv, $buyAmount, $price);
            return;
        }

        foreach ($this->configManager->getShopCategories() as $cat) {
            if (!isset($cat["name"]) || $itemName !== $cat["name"]) continue;
            if (!isset($cat["items"]) || !is_array($cat["items"])) continue;
            $this->fillShopCategoryItems($inv, $cat);
            return;
        }

        foreach ($this->configManager->getShopCategories() as $cat) {
            if (!isset($cat["items"])) continue;
            foreach ($cat["items"] as $entry) {
                if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
                if ($itemName === $this->buildShopItemLore($entry)) {
                    $this->fillBuyScreen($inv, $entry);
                    return;
                }
            }
        }
    }

    private function fillShopCategoryItems(BaseGUI $inv, array $cat) {
        $inv->clearAll();
        foreach ($cat["items"] as $entry) {
            if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
            $shopItem = Item::get((int)$entry["id"], (int)$entry["meta"], 1);
            $shopItem->setCustomName($this->buildShopItemLore($entry));
            $inv->setItem(count($inv->getContents()), $shopItem);
        }
        $inv->setItem(26, $this->makeBackButton());
    }

    private function fillBuyScreen(BaseGUI $inv, array $entry) {
        $inv->clearAll();
        $sym = $this->configManager->getSymbol();

        foreach ($this->configManager->getBuySlots() as $slotDef) {
            $amount     = (int)$slotDef["amount"];
            $slot       = (int)$slotDef["slot"];
            $totalPrice = $amount * (int)$entry["price"];
            $woolMeta   = $this->priceToWoolColor($totalPrice);
            $btnItem    = Item::get(35, $woolMeta, 1);
            $btnItem->setCustomName("§aBuy §e{$amount}x\n§7Price: §e{$sym}" . number_format($totalPrice));
            $inv->setItem($slot, $btnItem);
        }

        $refItem = Item::get((int)$entry["id"], (int)$entry["meta"], 1);
        $refItem->setCustomName($this->buildShopItemLore($entry));
        $inv->setItem(13, $refItem);

        $inv->setItem(26, $this->makeBackButton());
    }

    private function buildShopItemLore(array $entry) {
        $sym  = $this->configManager->getSymbol();
        $lore = $entry["name"] . "\n§aPrice: §e" . $sym . number_format((int)$entry["price"]);
        if (isset($entry["amount"]) && (int)$entry["amount"] > 1) {
            $lore .= " §7(x" . (int)$entry["amount"] . ")";
        }
        if (isset($entry["sell"])) {
            $lore .= "\n§6Sell: §e" . $sym . number_format((int)$entry["sell"]);
        }
        if (isset($entry["description"])) {
            $lore .= "\n§7" . $entry["description"];
        }
        return $lore;
    }

    private function handleSellClick(Player $player, BaseGUI $inv, Item $item) {
        $itemName = $item->getCustomName();
        $backName = (string)$this->configManager->get("back-button-name", "§r§c« Back");

        if ($itemName === $backName) {
            $ref = $inv->getItem(13);
            if ($ref !== null && $ref->getCustomName() !== "") {
                foreach ($this->configManager->getSellCategories() as $cat) {
                    if (!isset($cat["items"])) continue;
                    foreach ($cat["items"] as $entry) {
                        if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
                        if ($ref->getCustomName() === $this->buildSellReferenceDisplay($entry)) {
                            $this->fillSellCategoryItems($inv, $cat);
                            return;
                        }
                    }
                }
            }
            $this->fillSellCategories($inv);
            return;
        }

        if (preg_match('/^§eSell §a(\d+)x(\d+)\n§7Earn: §e[^\d]*([\d,]+)$/', $itemName, $m)) {
            $multiplier  = (int)$m[1];
            $bundleAmt   = (int)$m[2];
            $totalAmount = $multiplier * $bundleAmt;
            $payout      = (int)str_replace(",", "", $m[3]);
            $this->processSale($player, $inv, $totalAmount, $payout);
            return;
        }

        foreach ($this->configManager->getSellCategories() as $cat) {
            if (!isset($cat["name"]) || $itemName !== $cat["name"]) continue;
            if (!isset($cat["items"]) || !is_array($cat["items"])) continue;
            $this->fillSellCategoryItems($inv, $cat);
            return;
        }

        foreach ($this->configManager->getSellCategories() as $cat) {
            if (!isset($cat["items"])) continue;
            foreach ($cat["items"] as $entry) {
                if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
                $count   = (int)(isset($entry["amount"]) ? $entry["amount"] : 1);
                $display = $entry["name"] . "\n§eSell §7{$count}§e for §e"
                    . $this->configManager->getSymbol()
                    . number_format((int)$entry["price"]);
                if ($itemName === $display) {
                    $this->fillSellScreen($inv, $entry);
                    return;
                }
            }
        }
    }

    private function fillSellCategoryItems(BaseGUI $inv, array $cat) {
        $inv->clearAll();
        $sym = $this->configManager->getSymbol();
        foreach ($cat["items"] as $entry) {
            if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
            $count   = (int)(isset($entry["amount"]) ? $entry["amount"] : 1);
            $display = $entry["name"] . "\n§eSell §7{$count}§e for §e{$sym}" . number_format((int)$entry["price"]);
            $it = Item::get((int)$entry["id"], (int)$entry["meta"], 1);
            $it->setCustomName($display);
            $inv->setItem(count($inv->getContents()), $it);
        }
        $inv->setItem(26, $this->makeBackButton());
    }

    private function fillSellScreen(BaseGUI $inv, array $entry) {
        $inv->clearAll();
        $sym    = $this->configManager->getSymbol();
        $amount = (int)(isset($entry["amount"]) ? $entry["amount"] : 1);
        $price  = (int)$entry["price"];

        foreach ($this->configManager->getSellMultipliers() as $slotDef) {
            $mult        = (int)$slotDef["multiplier"];
            $slot        = (int)$slotDef["slot"];
            $totalPayout = $mult * $price;
            $woolMeta    = $this->payoutToWoolColor($totalPayout);
            $btn = Item::get(35, $woolMeta, 1);
            $btn->setCustomName("§eSell §a{$mult}x{$amount}\n§7Earn: §e{$sym}" . number_format($totalPayout));
            $inv->setItem($slot, $btn);
        }

        $refItem = Item::get((int)$entry["id"], (int)$entry["meta"], 1);
        $refItem->setCustomName($this->buildSellReferenceDisplay($entry));
        $inv->setItem(13, $refItem);

        $inv->setItem(26, $this->makeBackButton());
    }

    private function buildSellReferenceDisplay(array $entry) {
        $sym    = $this->configManager->getSymbol();
        $amount = (int)(isset($entry["amount"]) ? $entry["amount"] : 1);
        return $entry["name"] . "\n§7[SELL_REF] §e{$sym}" . number_format((int)$entry["price"])
             . " §7x{$amount} id:" . (int)$entry["id"] . ":" . (int)$entry["meta"];
    }

    private function processPurchase(Player $player, BaseGUI $inv, $buyAmount, $price) {
        $name = strtolower($player->getName());
        $sym  = $this->configManager->getSymbol();

        $cd = (int)$this->configManager->get("purchase-cooldown", 0);
        if ($cd > 0 && isset($this->cooldowns[$name])) {
            $left = $cd - (time() - $this->cooldowns[$name]);
            if ($left > 0) {
                $player->sendPopup("§cWait §e{$left}s §cbefore buying again.");
                return;
            }
        }

        $ref     = $inv->getItem(13);
        $matched = $this->findShopEntryByLore($ref ? $ref->getCustomName() : "");
        if ($matched === null) return;

        $itemAmount = (int)(isset($matched["amount"]) ? $matched["amount"] : 1);
        $totalItems = $buyAmount * $itemAmount;
        $free       = $player->hasPermission("economygui.free");

        if (!$free) {
            $money = $this->eco->myMoney($player);
            if ($money < $price) {
                $needed = $price - $money;
                $player->sendPopup($this->configManager->getMessage("not-enough-money", array(
                    "symbol" => $sym, "needed" => number_format($needed),
                )));
                return;
            }
        }

        if (!$player->getInventory()->canAddItem(Item::get((int)$matched["id"], (int)$matched["meta"], $totalItems))) {
            $player->sendPopup($this->configManager->getMessage("inventory-full"));
            return;
        }

        if (!$free) $this->eco->reduceMoney($player, $price);

        $player->getInventory()->addItem(Item::get((int)$matched["id"], (int)$matched["meta"], $totalItems));

        $this->cooldowns[$name] = time();
        $this->configManager->logTransaction("BUY", $name, $totalItems, $matched["name"], $price);

        $msgKey = $free ? "free-purchase" : "purchase-success";
        $player->sendPopup($this->configManager->getMessage($msgKey, array(
            "amount" => $totalItems,
            "item"   => $matched["name"],
            "symbol" => $sym,
            "price"  => number_format($price),
        )));
    }

    private function processSale(Player $player, BaseGUI $inv, $totalAmount, $payout) {
        $name = strtolower($player->getName());
        $sym  = $this->configManager->getSymbol();

        $ref     = $inv->getItem(13);
        $matched = $this->findSellEntryByRef($ref ? $ref->getCustomName() : "");
        if ($matched === null) return;

        $id   = (int)$matched["id"];
        $meta = (int)$matched["meta"];

        $playerInv = $player->getInventory();
        $has = 0;
        foreach ($playerInv->getContents() as $invItem) {
            if ($invItem->getId() === $id && $invItem->getDamage() === $meta) {
                $has += $invItem->getCount();
            }
        }

        if ($has < $totalAmount) {
            $player->sendPopup($this->configManager->getMessage("not-enough-items", array(
                "amount" => $totalAmount,
                "item"   => $matched["name"],
                "has"    => $has,
            )));
            return;
        }

        $toRemove = $totalAmount;
        foreach ($playerInv->getContents() as $slot => $invItem) {
            if ($toRemove <= 0) break;
            if ($invItem->getId() !== $id || $invItem->getDamage() !== $meta) continue;
            $c = $invItem->getCount();
            if ($c <= $toRemove) {
                $playerInv->clear($slot);
                $toRemove -= $c;
            } else {
                $invItem->setCount($c - $toRemove);
                $playerInv->setItem($slot, $invItem);
                $toRemove = 0;
            }
        }

        $this->eco->addMoney($player, $payout);
        $this->configManager->logTransaction("SELL", $name, $totalAmount, $matched["name"], $payout);

        $player->sendPopup($this->configManager->getMessage("sell-success", array(
            "amount" => $totalAmount,
            "item"   => $matched["name"],
            "symbol" => $sym,
            "price"  => number_format($payout),
        )));
    }

    private function findShopEntryByLore($lore) {
        foreach ($this->configManager->getShopCategories() as $cat) {
            if (!isset($cat["items"])) continue;
            foreach ($cat["items"] as $entry) {
                if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
                if ($lore === $this->buildShopItemLore($entry)) return $entry;
            }
        }
        return null;
    }

    private function findSellEntryByRef($refName) {
        foreach ($this->configManager->getSellCategories() as $cat) {
            if (!isset($cat["items"])) continue;
            foreach ($cat["items"] as $entry) {
                if (!isset($entry["name"], $entry["price"], $entry["id"], $entry["meta"])) continue;
                if ($refName === $this->buildSellReferenceDisplay($entry)) return $entry;
            }
        }
        return null;
    }

    private function makeBackButton() {
        $meta = (int)$this->configManager->get("back-button-meta", 14);
        $name = (string)$this->configManager->get("back-button-name", "§r§c« Back");
        $btn  = Item::get(35, $meta, 1);
        $btn->setCustomName($name);
        return $btn;
    }

    private function priceToWoolColor($price) {
        if ($price < 100)  return 5;
        if ($price < 500)  return 4;
        if ($price < 2000) return 1;
        return 14;
    }

    private function payoutToWoolColor($payout) {
        if ($payout < 100)  return 14;
        if ($payout < 500)  return 1;
        if ($payout < 2000) return 4;
        return 5;
    }
}
