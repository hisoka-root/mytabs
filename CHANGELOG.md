# Changelog

## [1.0.0] — 2026-06-26

### Rewrite for MyBB 1.8.35+ & PHP 8.0+

Previously published as v1.32 by FatalMessiah/Ethan. This release resets versioning to **1.0.0** under new maintainership and drops support for older MyBB / PHP versions.

### Fixed

- **Removed all `eval()` calls** for tab template rendering. The plugin previously used `eval()` to interpolate `{$link}`, `{$name}`, and `{$tablist}` placeholders into HTML. These have been replaced with safe `str_replace()` calls. This was the single biggest source of instability and a potential security risk.
- **Fixed `$noshow[] .= $fid`** → `$noshow[] = $fid` — concatenation assignment on an array push produced no visible error in PHP 7 but was silently wrong. Now uses proper `[] =` syntax.
- **Fixed `ENGINE = MYISAM`** → `ENGINE = InnoDB` — MyISAM is deprecated and removed in MySQL 8.0+.
- **Fixed `TEXT NOT NULL DEFAULT 0`** → `VARCHAR(20) NOT NULL DEFAULT ''` — MySQL strict mode rejects default values on TEXT columns.
- **Fixed `key()` on empty array** in `mytabs_menu()` — `end()` followed by `key()` on an empty array throws a `TypeError` in PHP 8.1+. Added `is_array()` and `!empty()` guards.
- **Fixed undefined variable `$errors`** in the admin module — now initialized to an empty array before use.
- **Fixed `require_once './inc/functions_themes.php'`** → `require_once MYBB_ROOT.'inc/functions_themes.php'` — the relative path resolved incorrectly from the admin modules directory.
- **Fixed `$tab` vs `$tab2` confusion** in the non-AJAX navigation loop — the outer loop variable was used in place of the inner loop variable when composing tab codes, causing every non-selected tab to render with the wrong style.
- **Fixed `for...in` on `NodeList`** in JavaScript — replaced with proper indexed `for` loops using `.children` instead of `.childNodes` (which includes unwanted text nodes).
- **Fixed global `xmlhttp` variable** in JavaScript — replaced with a locally-scoped `var req` to prevent race conditions between concurrent AJAX requests.
- **Fixed `req.open()` third argument** — was the string `"data:application/xml"`; corrected to `true` (async).
- **Removed `ActiveXObject` fallback** — no modern browser requires this.
- **Fixed "Add Tab" form** — the admin form could never successfully create a new tab due to a misplaced `isset($mybb->input['id'])` guard. The add action no longer requires a pre-existing ID.

### Added

- `isset()` guards on all `$mybb->input` array accesses, preventing PHP warnings when expected keys are absent.
- `isset()` guards on `$forumpermissions[$fid]` and `$forumpermissions[$pid]` before read/write — prevents warnings when tabs reference forum IDs that have been deleted.
- `is_array()` guards before iterating `$forumpermissions` and before `in_array()` checks against the noshow list.
- `array_filter()` applied after `explode()` when processing forum ID lists from the database — prevents PHP 8.1 deprecation when `null` is passed to `explode()`.
- `htmlspecialchars_uni()` escaping on tab names rendered in the frontend.
- `isset()` guards on `$admin_options['codepress']` — CodePress was removed from MyBB starting in 1.8.23.
- Initialized `$tppoptions`, `$tablist`, `$navbar`, `$body_content`, and `$setting` variables before first use.

### Changed

- `intval()` calls replaced with `(int)` casts throughout.
- `empty()` used consistently instead of bare truthiness checks on settings values.
- All legacy `\r\n` literal sequences in default settings converted to actual newlines for cleaner HTML output.
- Version reset to **1.0.0**; new GUID generated.

### Post-release fixes (2026-06-26)

- **Fixed unclosed `<div id="mytabs_full">`** — The opening wrapper div was unconditionally appended to `$forums` before checking whether tabs existed. When the plugin was enabled but no tabs had been created yet (fresh install), the div was never closed, causing the page footer to render inside the unstyled wrapper and break the layout. The function now returns early when disabled or when no tabs exist, leaving the default forum listing intact.
- **Fixed missing admin language file** — `admin/modules/forum/mytabs.php` calls `$lang->load('mytabs')`, which MyBB resolves to `inc/languages/<lang>/admin/mytabs.lang.php`. The distribution only shipped the frontend language file. Created `inc/languages/english/admin/mytabs.lang.php`.
- **Fixed HTTP 500 in admin panel** — All PHP files had closing `?>` tags. Trailing whitespace after `?>` in a `require`d file causes premature output, which breaks MyBB's admin panel with "headers already sent" errors. Removed closing `?>` from `inc/plugins/mytabs.php`, `admin/modules/forum/mytabs.php`, and both language files.
- **Fixed uninitialized `$setting` array** — `mytabs_useroptions()` and `mytabs_save_useroptions()` read `$setting` without initializing it as an array first, which could produce PHP warnings if the settings query returned no rows.
- **Fixed uninitialized `$tabselect`** — `mytabs_useroptions()` concatenated to `$tabselect` via `eval()` without initializing it, potentially triggering a PHP warning.
- **Removed unnecessary `require_once MYBB_ROOT.'inc/functions_themes.php'`** from the admin module — not needed by this module and an unnecessary point of failure.

---

## [1.32] and earlier

- Original plugin by Ethan / FatalMessiah at MyBBPlugins.
- Added tabbed forum browsing with AJAX and non-AJAX modes.
- Added per-user default tab selection in User CP.
- Added customizable tab styles via the Admin CP.
