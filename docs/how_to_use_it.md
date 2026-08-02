# How to use it

This guide explains how to use this Composer package in your own WordPress plugins or themes.

## Structure

The composer package uses the following structure to handle settings:

Settings Object
⬇️
Pages
⬇️
Tabs
⬇️
Sub-Tabs (optional)
⬇️
Settings
⬇️
Field

Or in words: Each setting that corresponds to exactly one field is assigned to a section, which in turn is assigned to a tab or sub-tab, which is in turn assigned to a page.

This makes it possible to display settings spread across multiple pages, organized into multiple tabs or sub-tabs.

## The fields

See [list of fields](fields.md).

## Choose your way

You can choose from the following basic methods for using this composer package in your plugin or theme:

* [Use PHP](how_to_use_with_php.md).
* [Use JSON](how_to_use_with_json.md)

## Error Handling

This applies to every path you take and is highly recommended. Display any errors that occur. This is useful during development but should not be used in production environments.

```
function your_custom_init_for_settings(): void {
    // configure your settings.
    $settings_object = new Settings( __FILE__ );
    $settings_object->init();

    // bail if we have no errors.
    if( ! $settings_obj->has_errors() ) {
        return;
    }

    // log these errors.
    foreach( $settings_obj->get_errors()->errors as $key => $errors ) {
        _doing_it_wrong( '\easySettingsForWordPress\Settings::add_settings()', '<em>' . esc_html( $key ) . '</em>: ' . esc_html( implode( ' ', $errors ) ), '1.0.0' );
    }
}
add_action( 'admin_init', 'your_custom_init_for_settings', 20 );
```

### Sorting

* Pages are not sortable
* Tabs are sorted by its given position
* Sections are sorted by its given position
* Settings are sorted in the order they are added
  -> use Setting->`move_before_setting()` to move a setting on a specific position
