[issues]: https://github.com/NetherByte233/PlaceholderAPI-PMMP/issues
[licenseImg]: https://img.shields.io/badge/license-MIT-blue.svg
[license]: LICENSE

[wikiimg]: https://img.shields.io/badge/wiki-PlaceholderAPI-blue
[wiki]: https://netherbyte233.github.io/PlaceholderAPI/

[docs]: https://github.com/NetherByte233/PlaceholderAPI-PMMP/wiki
[Youtubeimg]: https://img.shields.io/badge/YouTube-NetherByte-red
[NetherByte]: https://youtube.com/@netherbyte-e2d?si=640wTLjcs--w_YIC
[pmmp]: https://pmmp.io/

# PlaceholderAPI for PocketMine‑MP (API 5)
<p align="left" width="100%"><a href="https://NetherByte233.github.io/Placeholderapi/"><img src="https://netherbyte233.github.io/Placeholderapi/assets/logo.png" alt="logo" width="512"></a></p> 

[![licenseImg]][license] [![wikiimg]][wiki] [![Youtubeimg]][NetherByte]


A lightweight, extensible Placeholder API for PocketMine‑MP that lets you embed dynamic values in messages, GUIs, and configs using tokens like `%player_name%` or `%server_tps%`. Plugins can also provide their own placeholders via a simple Provider/Expansion model.

---

## Why this PlaceholderAPI?

While there is an existing PlaceholderAPI plugin for PocketMine-MP, this implementation offers several advantages:

- **Modern API 5 Support**: Built specifically for PocketMine-MP API 5 with updated code standards and best practices
- **Lightweight Architecture**: Minimal overhead with optimized performance for high-traffic servers
- **Flexible Provider System**: Enhanced Provider/Expansion model that allows third-party plugins to easily register and distribute their placeholders
- **Built-in Expansion Downloads**: In-game command to download and install expansions directly from providers without manual file management
- **Comprehensive Built-in Placeholders**: Includes extensive server and player placeholders out of the box, reducing dependency on multiple expansion plugins
- **Active Development**: Regular updates and maintenance with responsive support for bug reports and feature requests
- **Clean Console Output**: Follows PocketMine-MP guidelines by avoiding unnecessary startup/shutdown messages

---
## 🌍 Wiki
- Check our plugin [wiki] for details of feature and other things.
---

## Features
- Built‑in placeholders for common player and server information
- Provider/Expansion system for third‑party plugins (e.g., `netherperms`, `pocketvault`)
- In‑game management: list providers, install expansions, reload
- Safe defaults: unknown placeholders remain literal; player placeholders return empty when no player context

---

## Installation
1. Download or build the plugin and place it in your PocketMine‑MP `plugins/` folder.
2. Start the server to generate configuration files.
3. Optional: install third‑party expansions using `/papi download <identifier>`.

Supported server: PocketMine‑MP API 5 ([pmmp]).

---

## Quick Start (for developers) 
Resolve placeholders in your plugin:
```php
use NetherByte\PlaceholderAPI\PlaceholderAPI;

// $player can be null for server-only placeholders
$text = PlaceholderAPI::parse("Welcome %player_name% to %server_name%! TPS: %server_tps%", $player);

// Fetch a single value (identifier without %)
$tps = PlaceholderAPI::get('server_tps');
```

Register a simple expansion:
```php
use NetherByte\PlaceholderAPI\expansion\Expansion;
use pocketmine\player\Player;

final class MyExpansion extends Expansion{
    public function getName() : string { return 'MyExpansion'; }
    public function onRequest(string $identifier, ?Player $player) : ?string {
        return $identifier === 'my_value' ? '42' : null;
    }
}

// In onEnable()
\NetherByte\PlaceholderAPI\PlaceholderAPI::registerExpansion(new MyExpansion($this));
```

Expose an expansion via Provider (optional install with `/papi download`):
```php
use NetherByte\PlaceholderAPI\provider\Provider;
use NetherByte\PlaceholderAPI\expansion\Expansion;
use pocketmine\player\Player;

final class StatsExpansion extends Expansion{
    public function getName() : string { return 'Stats'; }
    public function onRequest(string $id, ?Player $p) : ?string {
        return $id === 'stats_kills' && $p !== null ? (string) 0 : null;
    }
}

final class MyProvider implements Provider{
    public function getName() : string { return 'MyPlugin'; }
    public function listExpansions() : array { return ['stats']; }
    public function provide(string $identifier) : ?Expansion {
        return $identifier === 'stats' ? new StatsExpansion($this) : null;
    }
}

// In onEnable()
\NetherByte\PlaceholderAPI\PlaceholderAPI::registerProvider(new MyProvider());
```

---

## Built‑in Placeholders (selection)
- Player
  - `%player_name%`, `%player_health%`, `%player_gamemode%`
  - `%player_x%`, `%player_y%`, `%player_z%`, `%player_world%`
  - `%player_ping%`, `%player_is_op%`
  - `%player_item_in_hand_name%`, `%player_item_in_offhand_name%`
  - `%player_armor_helmet_name%`, `%player_armor_chestplate_name%`, `%player_armor_leggings_name%`, `%player_armor_boots_name%`
  - `%player_ping_<playername>%`
- Server
  - `%server_name%`, `%server_online%`, `%server_max_players%`, `%server_version%`
  - `%server_tps%`, `%server_tps_1%`, `%server_tps_5%`, `%server_tps_15%`
  - `%server_tps_1_colored%`, `%server_tps_5_colored%`, `%server_tps_15_colored%`
  - `%server_uptime%`
  - `%server_time:<format>%` or `%server_time_<format>%`
  - `%server_online:<world>%` or `%server_online_<world>%`
  - `%server_countdown_<format>_<time>%`

---

## In‑game Commands
- `/papi list` — show loaded expansions
- `/papi providers` — list providers and available identifiers
- `/papi download <identifier>` — install an expansion by identifier
- `/papi reload` — reload expansions
- `/papi info <identifier[:param]> [player]` — inspect handler/value/meta

### Permissions
- `placeholderapi.use` — allow general usage (default: true)
- `placeholderapi.command.base` — access to `/papi` (default: op)
- `placeholderapi.command.list` — use `/papi list` (default: op)
- `placeholderapi.command.providers` — use `/papi providers` (default: op)
- `placeholderapi.command.download` — use `/papi download` (default: op)
- `placeholderapi.command.reload` — use `/papi reload` (default: op)
- `placeholderapi.command.info` — use `/papi info` (default: op)

---

## Tips and Notes
- Unknown placeholders remain unchanged.
- Player‑scoped placeholders return empty when no player context is provided.
- For heavy logic, cache results or precompute and use `getUpdateIntervalSeconds()` in your expansion.
- If your plugin depends on PlaceholderAPI, add `softdepend: [PlaceholderAPI]` and register your Provider/Expansion in `onEnable()`.

---

## Contributing & Support
- Bug reports and feature requests: see [issues].
- Contributions welcome via PRs. Please keep expansions fast and identifiers consistent.
- Subscribe to my YoutubeChannel [NetherByte]

---

## License
This project is licensed under the MIT License — see [LICENSE](LICENSE).
"# PlaceholderAPI" 
