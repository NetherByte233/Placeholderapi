# User Guide

## Using placeholders

Place tokens like `%....%` in strings parsed by plugins that integrate with PlaceholderAPI.

Example:
```
Welcome %player_name% to %server_name%! TPS: %server_tps%
```

- Unknown placeholders remain unchanged.
- Player placeholders return empty when no player context is provided.

## Parameterized placeholders

Besides `%identifier%`, you can use `%identifier:param%` to pass a parameter.

Examples:
- `%server_time:<Y-m-d H:i>%`
- `%server_online:world%`

Backward-compatible underscore style still works:
- `%server_time_<format>%`
- `%server_online_<world>%`

## Built-in placeholders

Player
- `%player_name%`
- `%player_health%`
- `%player_gamemode%`
- `%player_x%`, `%player_y%`, `%player_z%`
- `%player_world%`
- `%player_ping%`
- `%player_is_op%`
- `%player_item_in_hand_name%`
- `%player_item_in_offhand_name%`
- `%player_armor_helmet_name%`, `%player_armor_chestplate_name%`, `%player_armor_leggings_name%`, `%player_armor_boots_name%`
- `%player_ping_<playername>%`

Server
- `%server_name%`
- `%server_online%`
- `%server_max_players%`
- `%server_version%`
- `%server_tps%`, `%server_tps_1%`, `%server_tps_5%`, `%server_tps_15%`
- `%server_tps_1_colored%`, `%server_tps_5_colored%`, `%server_tps_15_colored%`
- `%server_uptime%`
- `%server_time:<format>%` or `%server_time_<format>%`
- `%server_online:<world>%` or `%server_online_<world>%`
- `%server_countdown_<format>_<time>%`

## Admin tips

- Use `/papi info <identifier[:param]> [player]` to see which expansion handles it and current value.
- Use `/papi providers` then `/papi download <id>` to install optional expansions.

## Navigation
<div class="grid cards" markdown>
- :material-school: **Getting Started**  
    - [> Getting Started](getting-started.md)

- :material-console: **Commands**
    - [> Commands](commands.md)

- :material-tools: **Using Placeholders**
    - [> Common Issues](plugins-using.md)

- :material-format-list-bulleted: **Placeholders List**
    - [> Placeholders List](placeholder-list.md)

- :material-puzzle: **Plugins Using Placeholders**
    - [> Plugins Using Placeholders](plugins-using.md)
</div>
