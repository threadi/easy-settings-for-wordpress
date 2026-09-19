# Common pitfalls

## "My setting always reads empty / false"

1. **`set_method()` after `add_setting()`**  
   Fix: move `set_method()` to the top of the setup function, before any
   `add_setting()`.

2. **Missing or early `init()`**  
   Fix: call `$settings_obj->init()` only after every setting is added.

3. **Setup not run on this request**  
   Reading `get_option()` in a context where your settings bootstrap never
   ran (e.g. wrong hook, conditional that skips admin-only code on the
   frontend). Fix: ensure the bootstrap runs on every request type that
   needs to read the option, or at least early enough for that request.

4. **Wrong option name**  
   Typo between `add_setting( 'foo' )` and `get_option( 'bar' )`.

5. **Expecting method `one` raw array**  
   Reading the parent option key instead of `get_option( $setting_name )`.

## Ordering anti-patterns

```php
// BAD
$settings->add_setting( 'x' );
$settings->set_method( 'one' ); // too late
$settings->init();

// BAD
$settings->set_method( 'one' );
$settings->add_setting( 'x' );
// forgot init()

// GOOD
$settings->set_method( 'one' );
$settings->add_setting( 'x' );
// ... more settings ...
$settings->init();
```

## Multiple Settings instances

Prefer one `Settings` object per settings page (often a static/singleton
getter). Creating multiple instances for the same page without a clear
design usually causes duplicate menus or confusing option keys.

## Autoloader

Consumers must load Composer's autoloader:

```php
require __DIR__ . '/vendor/autoload.php';
```

Without it, class resolution for `easySettingsForWordPress\...` fails.
