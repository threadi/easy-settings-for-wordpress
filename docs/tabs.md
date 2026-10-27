# Tabs

This composer package provides the tabs to group [fields](fields.md) for use as settings in WordPress plugins or themes.

## Add a tab

```
$fields_tab = $settings_page->add_tab( 'my_tab', 10 );
$fields_tab->set_title( 'My settings' );
```

## Settings

### Title

`$fields_tab->set_title( 'My settings' );`

### Description

`$fields_tab->set_description( 'My tab description' );`

### Hide the save button

`$fields_tab->set_hide_save( true );`

### Do not link

`$fields_tab->set_not_linked( true );`

### Use a callback

`$fields_tab->set_callback( array( $this, 'callback_for_my_tab' ) );`

This will be an output bellow the description.

### Set a class

Use this for custom stylings of tabs.

`$fields_tab->set_tab_class( 'my_class' );`

### Set custom URL

`$fields_tab->set_url( 'my_class' );`

## Single tab

If a settings page has only one tab (without sub-tabs), the tab navigation is not shown, neither in the
classic view nor in the DataView. The settings of this tab are shown directly below the title of the page.

Tabs shown as menu item (`set_show_in_menu( true )`) and tabs with the class `hidden` are not counted.
A tab with a custom URL (e.g. a link to the help) is counted, so the navigation stays visible for it.

To show the tab navigation also for a single tab:

`$settings_obj->set_show_single_tab( true );`

Or in the JSON configuration: `"show_single_tab": true`.

The decision could also be changed with the filter `<slug>_settings_show_tab_navigation`:

```
add_filter( 'my_slug_settings_show_tab_navigation', '__return_true' );
```
