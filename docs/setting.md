# Setting

Each setting offers the following options.

## Set the section

This is required for each setting to be used. `$section` must be a Section object.

`$setting->set_section( $section );`

## Set the type.

Every setting must have a type for storing its value.

`$setting->set_type( 'integer' );`

Following types are supported:

* string
* boolean
* integer
* number
* array
* object

## Set the default value

Each setting can and should have a default value that is returned if the user does not specify one.

`$setting->set_default( '' );`

The value depends on the type for the setting. If you have a string, use `''` as the default value. If you have an array, use `array()`.

## Prevent the export

Each setting could be exported. Here is how to prevent this:

`$setting->prevent_export( true );`

## Autoload the setting

Each WordPress settings can be autoloaded to optimize the loading performance. Here is how to change this:

`$setting->set_autoload( false );`

Default: yes, each setting will be autoloaded.

## Set the field

Set the field that should be displayed for configuring the setting.

`$setting->set_field( $field );`

The parameter can be either a Field object or an array containing the field configuration.

See [Fields](fields.md) for more.

## Set help

Add a helping text to the setting.

`$setting->set_help( 'My helping text' );`

## Use callbacks

The value of each setting can be modified using two callbacks.

The first is a callback that is always executed when the setting is saved. This is useful, for example, for checking specific requirements regarding the setting’s format:

`$setting->set_save_callback( array( $this, 'save_the_example' ) );`

The second is a callback that can be executed whenever the setting is read. This allows you to manipulate the value:

`$setting->set_read_callback( array( $this, 'read_the_example' ) );`

The callbacks receive the value of the setting as their first and only parameter.

## Show in REST API

If you want the setting to be displayed in the REST API, you must set this to true.

`$setting->set_show_in_rest( true );`

Default: false, will be hidden.

Exception: Some fields already provide their own configurations for the REST API. You do not need to specify these explicitly yourself. See [fields](fields.md) for details.

## Move a setting

If you want to move the position of your setting to a specific location in the output, use this feature here. `$setting` must be the other setting as Setting object.

`$setting->move_before_setting( $setting );`
