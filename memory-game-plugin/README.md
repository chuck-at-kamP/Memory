# Memory Game WordPress plugin (skeleton)

Copy this folder to `wp-content/plugins/` and activate it. Add `[memory_game]` to a page.

- `memory-game-plugin.php` – bootstrap, hooks
- `includes/class-db.php` – table `{prefix}memory_game_results` via `dbDelta()`
- `includes/class-rest.php` – `POST /wp-json/memory-game/v1/results` (validated, sanitized, nonce + rate limit)
- `includes/class-shortcode.php` – shortcode; assets are enqueued only when it renders
- `assets/` – game shell JS/CSS (placeholder questions; port the rest from `../index.html`)

Not included: anti-fraud, server-side answer validation, payout logic, admin UI.
