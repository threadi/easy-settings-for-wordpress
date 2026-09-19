# Setup ordering — why it matters

## Required order

1. Create / obtain `Settings` instance
2. `set_method( 'simple' | 'one' )` — **before** any `add_setting()`
3. Register pages, tabs, sections, settings, fields
4. `init()` — **last**

## What `init()` does

`Settings::init()` walks every setting registered so far and attaches the
read filters needed for method `one` (`pre_option_{$setting_name}`). For
method `simple`, individual option rows already exist; filters are less
critical, but you must still call `init()` for full admin UI registration.

Calling `add_setting()` alone does **not** attach those filters, except in
the late-add case below.

## Late-add (settings after `init()`)

If another component (e.g. an add-on) calls `add_setting()` on the **same**
`Settings` object *after* `init()` has already run once, `add_setting()`
attaches the filter immediately. That is intentional so late registration
still works without a second `init()`.

What remains unsafe:

- Calling `set_method()` after any `add_setting()`
- Calling `get_option()` for a setting before both `add_setting()` and
  `init()` have run for it in the current request (except the late-add path)

## Method resolution

`Methods::get_method()` resolves and caches the active method on first use.
That is why `set_method()` must happen before the first `add_setting()`.
