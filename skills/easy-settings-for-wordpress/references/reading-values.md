# Reading setting values

## Contract

Always use:

```php
get_option( 'my_setting_name' );
```

Do **not** read the internal merged option key used by storage method `one`
directly. Both `simple` and `one` are designed so consumer code uses the
same `get_option( $setting_name )` API.

## Method `simple`

Each setting is a real row in `wp_options`. `get_option()` works like any
normal WordPress option once the setting has been registered and saved.

## Method `one`

There is **no** individual `wp_options` row for each setting name. Values
live in one serialized array. `get_option( $setting_name )` only works
because the package attaches a `pre_option_{$setting_name}` filter that
intercepts the call and reads from the merged array.

If that filter is not attached yet (setup order wrong, or setup not run on
this request), `get_option()` silently returns `false` / WordPress default —
not an exception.

## Request types

The same `get_option()` call works on frontend, admin, REST, WP-CLI, and
cron **as long as** the settings setup (including `init()`) has run on that
request before the read. Typical pattern: register on `init` (or earlier)
and read later in the same request or on subsequent requests after options
were saved.
