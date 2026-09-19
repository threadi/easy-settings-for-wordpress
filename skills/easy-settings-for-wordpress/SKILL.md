---
name: easy-settings-for-wordpress
description: >
  Use when integrating or debugging the Composer package
  threadi/easy-settings-for-wordpress (namespace easySettingsForWordPress)
  in a WordPress plugin or theme — settings pages, tabs, sections, fields,
  storage methods (simple/one), get_option() reads, JSON-defined settings,
  or "setting reads empty" issues.
---

# easy-settings-for-wordpress

Agent skill for **consumers** of the Composer package
`threadi/easy-settings-for-wordpress` (namespace `easySettingsForWordPress`).

This skill teaches how to build settings pages that work correctly with the
package. It is **not** for developing the package itself.

## When to use

- Adding or changing a settings page that uses this package
- Choosing or switching storage method (`simple` vs `one`)
- Debugging why `get_option( 'my_setting' )` returns empty/false
- Defining settings in PHP or via JSON
- Migrating existing options into this package

## Requirements

- PHP ^8.2
- WordPress plugin or theme that embeds this package
- Package depends on `threadi/easy-dialog-for-wordpress`

## Structure (mental model)

```
Settings object  →  Page  →  Tab  (→ Sub-Tab)  →  Section  →  Setting  →  Field
```

One `Settings` instance owns exactly one settings page.

## Critical setup order

Inside the function that builds the settings page, **always** do this order
or reads will silently return empty/default:

1. `new Settings( __FILE__ )` (or reuse a singleton getter)
2. `$settings_obj->set_method( 'one' )` **or** leave default `simple` — **before** any `add_setting()`
3. `add_page()` / `get_page()`, `add_tab()`, `add_section()`, `add_setting()` for every setting
4. `$settings_obj->init()` — **last**, after every setting has been added

```php
use easySettingsForWordPress\Fields\Checkbox;
use easySettingsForWordPress\Settings;

function my_plugin_init(): void {
    $settings_obj = my_plugin_get_settings_object();
    $settings_obj->set_method( 'one' ); // or omit for default 'simple'

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

## Reading values

**Always** read with plain WordPress `get_option( $setting_name )` — never
read the merged "one" option array directly. Both storage methods are designed
to be read the same way.

```php
if ( get_option( 'my_plugin_checkbox' ) ) { /* ... */ }
```

`init()` (not `add_setting()` alone) is what attaches the read filters that
make `get_option()` work for method `one`. See `references/setup-ordering.md`.

## Debugging "setting reads empty"

Check in this order:

1. Is `set_method()` called **before** every `add_setting()`?
2. Is `$settings_obj->init()` the **last** call in the setup function?
3. Has the setup function actually run in this request before the `get_option()` call?

More detail: `references/common-pitfalls.md`.

## Storage methods

| Method   | Behavior |
|----------|----------|
| `simple` (default) | One `wp_options` row per setting |
| `one`    | All settings of the page in one serialized array under a single option |

Switching methods on a shipped plugin without data loss: see package docs
`migrate_settings.md` (or the equivalent section in the package README/docs
if present in your install).

## Further references (load on demand)

- `references/setup-ordering.md` — why order matters, late-add behavior
- `references/reading-values.md` — get_option contract, method `one` filters
- `references/common-pitfalls.md` — checklist and typical mistakes

Human documentation lives in the GitHub repository under `docs/`
(not shipped in the Composer dist). Prefer this skill’s `references/`
for agent guidance; use the online/repo docs when you need full field lists
or long-form tutorials.

## Do / Don't

**Do**

- Call `set_method()` before any `add_setting()`
- Call `init()` last
- Read settings only via `get_option( $name )`
- Keep one `Settings` instance per settings page (singleton pattern is fine)

**Don't**

- Call `set_method()` after `add_setting()`
- Read the raw merged option for method `one`
- Assume `add_setting()` alone makes a setting readable without `init()`
- Instantiate services or fields outside the documented flow without checking
  package field constructors
