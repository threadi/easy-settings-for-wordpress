<!--
  Reading this from vendor/threadi/easy-settings-for-wordpress/AGENTS.md
  inside a project that merely depends on this package?
  AGENTS.md is only read automatically from a project's own root — not from
  vendor/. Copy the "Using easySettingsForWordPress" section below into the
  AGENTS.md of the project you are actually working in, so it gets picked
  up automatically there too.
-->

# AGENTS.md

Instructions for AI coding agents for `threadi/easy-settings-for-wordpress`
(namespace `easySettingsForWordPress`) — a Composer library that wraps the
WordPress Settings API.

## Requirements

- PHP ^8.2
- WordPress (any plugin/theme that embeds this package)
- Depends on `threadi/easy-dialog-for-wordpress`

## Using easySettingsForWordPress

### Structure

```
Settings object  →  Page  →  Tab  (→ Sub-Tab)  →  Section  →  Setting  →  Field
```

One `Settings` instance handles exactly one settings page. Settings are
persisted with one of two storage methods, chosen via `set_method()`:

- **`simple`** (default): every setting gets its own row in `wp_options`.
- **`one`**: all settings of the page live in a single serialized array
  under one `wp_options` row.

Regardless of which method is active, **always read values with the plain
WordPress `get_option( $setting_name )`** — never read the "one" method's
merged option directly. Both methods are designed to be read the same way,
through `get_option()`.

### Setup ordering — do this or reads will silently return empty/default

Inside the function that builds the settings page:

1. `new Settings( __FILE__ )`
2. `$settings_obj->set_method( 'one' )` (or leave the default `simple`) —
   **before** anything below
3. `add_page()` / `get_page()`, `add_tab()`, `add_section()`, `add_setting()`
   for every setting
4. `$settings_obj->init()` — **last**, after every setting has been added

```php
use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Settings;

function my_plugin_init(): void {
    $settings_obj = my_plugin_get_settings_object();
    $settings_obj->set_method( 'one' );

    $page    = $settings_obj->get_page( $settings_obj->get_menu_slug() );
    $tab     = $page->add_tab( 'my-plugin-tab', 10 );
    $section = $tab->add_section( 'my-plugin-section', 10 );

    $setting = $settings_obj->add_setting( 'my_plugin_checkbox' );
    $setting->set_section( $section );
    $setting->set_type( 'boolean' );
    $setting->set_default( false );
    $field = new Checkbox( $settings_obj );
    $field->set_title( __( 'My checkbox', 'my-plugin' ) );
    $setting->set_field( $field );

    $settings_obj->init(); // must be last
}
add_action( 'init', 'my_plugin_init' );

function my_plugin_get_settings_object(): Settings {
    static $settings = null;
    if ( null === $settings ) {
        $settings = new Settings( __FILE__ );
    }
    return $settings;
}
```

Reading the value anywhere else in the plugin, on any request type
(frontend, admin, REST, CLI, cron):

```php
if ( get_option( 'my_plugin_checkbox' ) ) { ... }
```

Adding a setting to the same `Settings` object *after* `init()` already ran
(e.g. from an add-on hooking a later priority on the same action) is safe —
the package wires that one setting up immediately when `add_setting()` is
called. What is not safe: calling `set_method()` after any `add_setting()`
call, or reading a setting via `get_option()` before the code that defines
it has actually run in this request.

### When debugging "my setting reads empty"

Check, in this order:
1. Is `set_method()` called before every `add_setting()` call?
2. Is `$settings_obj->init()` the *last* call in the setup function, after
   all settings are added?
3. Is the code reading `get_option()` for a setting that this request has
   actually defined yet (i.e. did the setup function run first)?

### Reference docs

- `docs/fields.md` — available field types and common field methods.
- `docs/setting.md` — per-setting options: type, default, autoload, export,
  save/read callbacks, REST visibility.
- `docs/tabs.md`, `docs/pages.md` — tab and multi-page options.
- `docs/hooks.md` — JS hooks for the DataView admin UI.
- `docs/migrate_settings.md` — switching a shipped plugin between storage
  methods without losing user data.
- `docs/how_to_use_with_json.md` — defining a settings page from JSON.
- `.claude/skills/easy-settings-for-wordpress/` — the same guidance as an
  Agent Skill (agentskills.io format), with progressive-disclosure
  reference files.

## Internals — why the ordering rule above exists

Relevant if you are modifying this package itself (`Method_Base.php`,
`src/Methods/One.php`, `Settings::add_setting()`), or debugging a case the
checklist above didn't resolve:

- For `simple`, `get_option()` works immediately because the value is a
  real `wp_options` row — no filter needs to be attached first.
- For `one`, there is no real `wp_options` row for the individual setting
  name. `get_option( $setting_name )` only works because the package
  attaches a `pre_option_{$setting_name}` filter that intercepts the call
  and reads from the merged array. If that filter has not been attached yet
  when `get_option()` is called, the result is silently `false`/the WP
  default — not an error.
- `Settings::add_setting()` wires up that filter for a setting immediately
  once `Settings::init()` has already run once, so settings added late (by
  code hooking a later priority on the same action) still work. Do not
  replace this with a batch/sweep model gated by a fixed hook priority —
  that reintroduces a WP_Hook reentrancy bug where filters added while a
  hook is already dispatching can silently never fire in that request.
- `Methods::get_method()` resolves and caches the active method lazily on
  first use — this is why `set_method()` must be called before the first
  `add_setting()` call.

## Contributing to this package

Build/test/lint commands, local setup, and coding-standards tooling are
documented in `CONTRIBUTING.md`.
