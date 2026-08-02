# Pages

You can also display your plugins or themes settings on multiple pages. For more information, see the instructions here.

## Restrictions

An instance of `new Settings()` always handles exactly one page. You must create a separate instance for each page with settings. These instances can and should all have your plugin slug as a parameter, but should differ in their menu slug.

## Pages are not tabs

Note that pages are not tabs. It is entirely possible to distribute the settings across a theoretically unlimited tabs within a single page.

## Using URL

A page’s URL can be set using `$settings_object->set_menu_slug()` and `$settings_object->get_menu_parent_slug()`. The parent slug determines the URL within the WordPress backend.

The following options are supported here:

* options-general.php - is located in the WordPress backend under Settings
* admin.php - custom position; can also be placed as a submenu item elsewhere; tabs can optionally be displayed as submenu items
* any other value - the submenu item corresponding to the specified value is displayed
