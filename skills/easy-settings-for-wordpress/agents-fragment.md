# Fragment for consumer project AGENTS.md

Copy the section below into the **root `AGENTS.md` of the WordPress plugin
or theme** that depends on `threadi/easy-settings-for-wordpress`, if you want
agents working in that project to pick up package rules automatically
without loading the full skill.

Prefer installing the full Agent Skill from
`vendor/threadi/easy-settings-for-wordpress/skills/easy-settings-for-wordpress/`
(see package README). This fragment is only a short reminder.

---

## Using easy-settings-for-wordpress

When adding or changing settings via `threadi/easy-settings-for-wordpress`
(namespace `easySettingsForWordPress`):

1. Call `set_method( 'simple' | 'one' )` **before** any `add_setting()`.
2. Register all pages/tabs/sections/settings/fields.
3. Call `$settings_obj->init()` **last**.
4. Always read values with `get_option( $setting_name )` — never the raw
   merged option of method `one`.

If a setting reads empty: check method order, then `init()` last, then that
setup ran on this request before the read.

Full guidance: Agent Skill `easy-settings-for-wordpress` shipped with the
package under `skills/easy-settings-for-wordpress/`.
