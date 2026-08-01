# How to use it with PHP

## Requirements

* A custom plugin or theme.
* composer

## Installation

`composer require threadi/easy-settings-for-wordpress`

Don't forget to embed the composer autoloader in your plugin or theme:

`require __DIR__ . '/vendor/autoload.php';`

## Embed it

This will give you a demo view that you can then further customize:

```
function your_custom_init_for_settings(): void {
    $settings_object = new Settings( __FILE__ );
    $settings_object->init();
}
add_action( 'init', 'your_custom_init_for_settings' );
```

### Customize it

#### Set slug

The slug is used internally to allow you to use hooks. Default: "easy-settings-for-wordpress".

`$settings_object->set_slug( 'your-slug' );`

Tipp: should be your plugin-slug.

#### Set the menu title

This is the text that appears as a menu item in the menu, which users can click to access your settings. Default: "Easy Settings for WordPress."

`$settings_object->set_menu_title( 'Your plugin name' );`

#### Set the title

That is the heading of the Settings page that appears at the top of the page when you open it. Default: "Easy Settings for WordPress."

`$settings_object->set_title( 'Your plugin name' );`

#### Set the menu slug

This is the slug used in the URL to access your settings. Default: "easy-settings-for-wordpress-settings".

`$settings_object->set_menu_slug( 'your-slug' );`

#### Set the parent slug

Specifies under which menu item your settings will be displayed. Default: "options-general.php".

`$settings_object->set_menu_parent_slug( 'your-slug' );`

The following options are supported here:

* options-general.php - is located in the WordPress backend under Settings
* admin.php - custom position; can also be placed as a submenu item elsewhere; tabs can optionally be displayed as submenu items
* any other value - the submenu item corresponding to the specified value is displayed

#### Show your settings in plugin list

If this value is set to true, the configured settings URL will be displayed next to your plugin in the list of all plugins. This helps users find your plugin's settings even faster.

`$settings_object->show_settings_link_in_plugin_list( 'your-slug' );`

#### Set the capability

Configure who could access these settings.

`$settings_object->set_capability( 'god' );`

Note: This only protects the setting from being modified; it does not protect it from being read.

#### Set method

Specifies the method used to store settings in the WordPress database.

`$settings_object->set_method( 'one' );`

The following options are available:

* Simple (name "simple") - each setting is stored in a field designated specifically for that setting
* One (name "one") - all settings are store in one single field

#### Set views

The settings can be displayed in the backend in various ways. The package offers the following options:

* Classic (name "classic") - the classic way to use settings in the backend incl. tabs
* DataView (name "dataview") - the modern way to handle settings in the backend, only usable in WordPress 7.0 or newer

To set the view for your plugin/theme:

`$settings_object->set_view( 'classic' );`

Hint: if you set "dataview" users with WordPress < 7.0 will be use the classic view.

### Add your settings

For each setting, you'll need:

* a settings page
* a tab in this settings page
* a section in this tab
* a field

The page is already available to you. All you have to do is access it:

`$settings_page = $settings_obj->get_page( 'demo-settings' );`

Or create one:

`$settings_page = $settings_obj->add_page( 'demo-settings' );`

Now add the tab:

```
$fields_tab = $settings_page->add_tab( 'my_tab', 10 );
$fields_tab->set_title( 'My settings' );
```

Read more about Tabs [here](tabs.md)

Now the section in this tab:

```
$section = $fields_tab->add_section( 'first_section', 10 );
$section->set_title( 'My settings section' );
```

And now the setting:

```
$setting = $settings_obj->add_setting( 'my_setting' );
$setting->set_section( $section );
```

For more about the setting configuration see [here](fields.md).
