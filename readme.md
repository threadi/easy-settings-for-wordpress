# Easy Settings for WordPress

This composer packages add a simple wrapper for settings for WordPress plugins and themes. You no longer need to worry about inputting or outputting settings for your own implementation - leave that to this package. Simply use the WordPress-own `get_option()` to get the values of your settings.

## Requirements

* A custom WordPress plugin or theme
* composer

## Installation

Run this in your custom plugin or theme directory:

`composer require threadi/easy-settings-for-wordpress`

Don't forget to embed the composer autoloader in your plugin or theme:

`require __DIR__ . '/vendor/autoload.php';`

## Usage

Note: Take a look [at the demo plugin](https://github.com/threadi/easy-settings-for-wordpress-demo) to see how this package can be used.

### Quick-Start

This will give you a demo view that you can then further customize:

```
function your_custom_init_for_settings(): void {
    $settings_object = new Settings( __FILE__ );
    $settings_object->init();
}
add_action( 'init', 'your_custom_init_for_settings' );
```

### Use it

Follow the documentation [here](docs/how_to_use_it.md).

### Upgrade hints

#### for 3.0.0

- remove do_not_register() from TextInfo() and Value() fields
- remove set_type() from Checkbox() fields
- use add_data() instead of set_custom_attributes() for Import and Export buttons

## For changes of this package

### Check for WordPress Coding Standards

#### Initialize

`composer install`

#### Run

`vendor/bin/phpcs --extensions=php --ignore=*/vendor/* --standard=WordPress .`

#### Repair

`vendor/bin/phpcbf --extensions=php --ignore=*/vendor/* --standard=WordPress .`

## Check for WordPress VIP Coding Standards

Hint: this check runs against the VIP-GO-platform which is not our target for this package. Many warnings can be ignored.

### Run

`vendor/bin/phpcs --extensions=php --ignore=*/vendor/* --standard=WordPress-VIP-Go .`
