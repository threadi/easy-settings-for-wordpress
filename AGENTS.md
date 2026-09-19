# AGENTS.md

Instructions for AI coding agents working **on this repository**
(`threadi/easy-settings-for-wordpress` — namespace `easySettingsForWordPress`).

This file is for **package maintainers and contributors**.  
Plugin developers who only *consume* the package should use the Agent Skill
under `skills/easy-settings-for-wordpress/` (and optionally
`skills/easy-settings-for-wordpress/agents-fragment.md`), not this file as their project root AGENTS.md.

## Project overview

Composer library that wraps the WordPress Settings API: pages, tabs,
sections, settings, typed fields, two storage methods (`simple` / `one`),
admin UI (DataView), export/import, optional JSON-driven definition.

## Requirements

- PHP ^8.2
- WordPress (for runtime behaviour and tests)
- Runtime dependency: `threadi/easy-dialog-for-wordpress`
- Dev tooling: see `composer.json` / `CONTRIBUTING.md`

## Layout

- `src/` — PHP library (PSR-4 `easySettingsForWordPress\`)
- `assets/` — built admin CSS/JS (source under paths excluded from dist as needed)
- `docs/` — human documentation (GitHub/repo only; excluded from Composer dist)
- `skills/easy-settings-for-wordpress/` — Agent Skill for **consumers**
- `tests/` — PHPUnit (when present)
- `bin/` — helpers (WP test install, schema validate)

## Commands (maintainers)

```bash
composer install
composer test
vendor/bin/phpcs .
vendor/bin/phpcbf .
vendor/bin/phpstan analyse
composer validate-schema
```

Local WP test install (see `composer.json` scripts):

```bash
composer test-install
# or during CI-style builds:
composer test-install-during-build
```

Details and contribution process: `CONTRIBUTING.md`.

## Architecture notes (when changing the package)

- Storage methods live under method classes; `set_method()` must be applied
  before settings are added. Lazy resolution caches the active method on
  first use.
- Method `one` relies on `pre_option_{$name}` filters attached during
  `Settings::init()` (and on late `add_setting()` after init has run once).
  Do not replace this with a model that only registers filters at a fixed
  hook priority in a way that breaks WP_Hook reentrancy.
- Consumers must keep reading via `get_option( $setting_name )` for both
  methods — do not break that contract.

## Consumer guidance lives in the skill

Do **not** expand this AGENTS.md with long “how to use the library in a
plugin” tutorials. That content belongs in:

- `skills/easy-settings-for-wordpress/SKILL.md` (+ `references/`)
- `docs/` for humans (repo only; not in Composer dist)
- `skills/easy-settings-for-wordpress/agents-fragment.md` for optional paste into consumer projects

When you change setup order, read filters, or field APIs, update the skill
and docs in the same change.
