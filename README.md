# ACF Sync Bar

A WordPress plugin that adds an admin bar button surfacing [Advanced Custom Fields](https://www.advancedcustomfields.com/) field groups with pending local-JSON syncs. Sync any group — or all of them — in one click, from any page, without visiting **Custom Fields → Field Groups**.

- **Version:** 1.1.1
- **Author:** Paperstreet
- **License:** GPL-2.0-or-later
- **Requires PHP:** 7.4+
- **Requires WordPress:** 6.0+
- **Requires:** Advanced Custom Fields (free or Pro)

## What it does

When ACF loads field groups from local JSON (`acf-json/`), the database copy can fall out of date — after a `git pull`, a deploy, or another dev editing fields. ACF flags these on the Field Groups screen, but you have to go there to see and clear them.

ACF Sync Bar moves that workflow into the admin bar:

- Shows an **ACF Sync (N)** button whenever one or more groups need syncing.
- Lists each pending group with its status:
  - `new` — JSON defines a group with no database copy yet.
  - `json_newer` — JSON file is newer than the database copy.
- One-click sync per group, plus **Sync All** when more than one is pending.
- Syncs via AJAX — no page reload. Nodes disappear from the bar as they sync; the button removes itself when nothing is left pending.

The button only appears for users who hold ACF's configured sync capability (`manage_options` by default).

## Installation

1. Copy the plugin folder to `wp-content/plugins/acf-sync-bar/`.
2. Activate **ACF Sync Bar** in **Plugins**.
3. Ensure Advanced Custom Fields (free or Pro) is installed and active.

If ACF is not active, the plugin deactivates itself on activation and shows an admin notice; it does nothing until ACF is enabled.

## Usage

1. Make sure your theme/plugin registers a local JSON save path (`acf-json/`) — the standard ACF local JSON setup.
2. When a group's JSON differs from the database, the **ACF Sync (N)** button appears in the admin bar.
3. Open the dropdown, click a group (or **Sync All**), confirm the prompt, and the database copy is overwritten from JSON.

> **Note:** Sync is a **dev-only** action. It overwrites the database field group with the JSON file. The confirmation prompt reflects this.

## How it works

| File                                        | Responsibility                                                                                         |
| ------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `acf-sync-bar.php`                          | Bootstrap — constants, ACF dependency guard, hook wiring.                                              |
| `includes/class-acf-sync-bar-detector.php`  | Detects local-JSON groups whose JSON is `new` or `json_newer` than the DB. Results cached per request. |
| `includes/class-acf-sync-bar-admin-bar.php` | Renders the admin bar node + dropdown, enqueues the front-end script.                                  |
| `includes/class-acf-sync-bar-ajax.php`      | Handles the AJAX sync request: nonce + capability checks, then imports the field group from JSON.      |
| `assets/sync-bar.js`                        | Wires up clicks, posts to AJAX, updates the bar in place.                                              |

### Sync safety

The sync path mirrors ACF's native behavior by reading the raw local JSON file (where fields are nested) before import. `acf_get_local_field_group()` returns group settings **without** their fields, so importing that result directly would delete every existing field and re-create nothing — the bug that silently wiped field groups (fixed in 1.1.x). The AJAX handler refuses to import an empty field set as a guard.

## Security

- Every sync request verifies a nonce (`acf_sync_bar`) and rechecks the ACF sync capability server-side.
- The admin bar node and enqueued assets only render for capable users.
- Only groups the detector reports as pending can be synced; arbitrary keys are rejected.

## Development

- `includes/` and `assets/` hold the source; `dist/` is the packaged build.
- `tests/verify-sync.php` is a WP-CLI verification script for ACF sync field retention.

## License

GPL-2.0-or-later. See [LICENCE](LICENCE).

