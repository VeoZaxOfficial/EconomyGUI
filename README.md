# EconomyGUI
A Shop & Sell GUI plugin for PocketMine-MP 2.0 by VeoZax.

## Features
- Chest-based Shop GUI with categories
- Chest-based Sell GUI with categories
- Buy items in 1x, 10x, 64x amounts
- Sell items in 1x, 2x, 5x multipliers
- Full in-game category and item management
- Config.yml support added for Manual edits
- EconomyAPI integration
- Transaction logging
- Admin free-buy perk

## Requirements
- PocketMine-MP 2.0.0
- EconomyAPI plugin

## Installation
1. Download the plugin from here: ⬇[Download EconomyGUI](https://www.mediafire.com/file/c6979hrbl9s3qg4/EconomyGUI_v1.0.0.phar/file)
2. Add it into your server's `plugins/` folder
3. Restart the server
4. Edit `config.yml`, `shop.yml`, `sell.yml` as needed

## Commands
| Command | Description | Permission |
|---|---|---|
| `/shop` | Open Shop GUI | `economygui.shop` |
| `/sell` | Open Sell GUI | `economygui.sell` |
| `/shopbalance` | Check balance | `economygui.shop` |
| `/shopreload` | Reload configs | `economygui.reload` |
| `/shopgui-add <id:meta> <price> <amount> [cat] [sellprice]` | Add shop item | `economygui.admin` |
| `/shopgui-remove <id:meta> [cat]` | Remove shop item | `economygui.admin` |
| `/shopgui-addcat <key> <iconId> <name>` | Add shop category | `economygui.admin` |
| `/shopgui-removecat <key>` | Remove shop category | `economygui.admin` |
| `/shopgui-listcat` | List shop categories | `economygui.admin` |
| `/sellgui-add <id:meta> <price> <amount> [cat]` | Add sell item | `economygui.admin` |
| `/sellgui-remove <id:meta> [cat]` | Remove sell item | `economygui.admin` |
| `/sellgui-addcat <key> <iconId> <name>` | Add sell category | `economygui.admin` |
| `/sellgui-removecat <key>` | Remove sell category | `economygui.admin` |
| `/sellgui-listcat` | List sell categories | `economygui.admin` |

## Permissions
| Permission | Default | Description |
|---|---|---|
| `economygui.shop` | true | Use /shop |
| `economygui.sell` | true | Use /sell |
| `economygui.reload` | op | Reload configs |
| `economygui.admin` | op | Manage items and categories |
| `economygui.free` | false | Buy items for free |

## Author
* Developed by VeoZax

## License
This project is licensed under the MIT License.
