# MyTabs

Lets you implement tabbed browsing on your MyBB forum. Divide your forums into tabs with customizable styling, AJAX or static switching, and per-user default tab preferences.

## Requirements

- MyBB **1.8.35** or later
- PHP **8.0** or later
- MySQL **8.4** or later (InnoDB required; MyISAM is not supported)

## Installation

1. Upload the contents of `inc/` and `jscripts/` into your MyBB forum root.
2. Upload the contents of `admin/` into your MyBB forum root. If your admin directory has been renamed, adjust the folder name before uploading.
3. Go to **ACP → Configuration → Plugins** and activate **MyTabs**.
4. Go to **ACP → Forums & Posts → MyTabs** to create tabs and configure settings.

## Usage

- **Add a Tab** — assign a name, choose which forums appear in it, and optionally set custom HTML for normal and selected states. Use `{$link}` and `{$name}` as placeholders.
- **Settings** — enable/disable the plugin, toggle AJAX mode, choose the default landing tab, and customize the default tab and tab-list HTML.
- **User CP** — users can pick their preferred default tab under **User CP → Edit Options**.

### Placeholders

| Placeholder  | Replaced with                    |
|-------------|----------------------------------|
| `{$link}`   | The tab's URL + onclick handler |
| `{$name}`   | The tab's display name          |
| `{$tablist}`| The full list of tab buttons    |

## Maintainers

| Period          | Maintainer                               |
|-----------------|------------------------------------------|
| Original        | Ethan / FatalMessiah — [MyBBPlugins](http://www.mybbplug.in/s/) |
| Interim         | FatalMessiah — [UltimateGameModders](http://ultimategamemodders.org/s/) |
| Current (2026–) | **hisoka** — [github.com/hisoka-root](https://github.com/hisoka-root) |

This plugin was originally created by Ethan at MyBBPlugins and later maintained by FatalMessiah. I have rewritten the codebase for compatibility with modern MyBB and PHP, resetting the version to **1.0.0**. I gratefully acknowledge all prior work.

## License

This plugin is licensed under the **GNU General Public License v3.0 or later**.

```
MyTabs - Tabbed forum browsing plugin for MyBB
Copyright (C) 2026  hisoka (root@hisoka.lol)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
```

See the [LICENSE](LICENSE) file for the full text.
