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
