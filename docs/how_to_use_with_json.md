# How to use it with JSON

## Requirements

* A custom plugin or theme.
* composer

## Installation

`composer require threadi/easy-settings-for-wordpress`

Don't forget to embed the composer autoloader in your plugin or theme:

`require __DIR__ . '/vendor/autoload.php';`

## Embed it

```
function your_custom_init_for_settings(): void {
    $your_json_content = 'you JSON content';
    $settings_object = new Settings( __FILE__ );
    $settings_object->set_json( $your_json_content );
    $settings_object->init();
}
add_action( 'init', 'your_custom_init_for_settings' );
```

Alternatively you can also input your JSON file direct:

`$settings_object->set_json_by_path( '/absolute/path/to/the/file.json' );`

## The JSON file

The JSON file must have a structure that is compatible with [the schema](../settings.schema.json).

An example you will find [here](example.json).
