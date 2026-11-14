# Search in the settings

Plugins with many settings could enable a search field on their settings page. It is optional and disabled by
default: small plugins do not need it.

## Enable it

```php
$settings_obj->set_search( true );
```

Or in the JSON configuration: `"search": true`.

The search field is shown above the settings, in the classic view and in the DataView:

* **Classic view:** the search filters the settings of the actual tab while typing. Sections without matches are
  hidden. The tabs show the number of their matches, a list below the settings shows the matches in other tabs. A
  click on a match opens its tab with the search and highlights the setting. Settings, which are only visible if
  another setting has a specific value (see `add_depend()`), are listed with a hint and a link to this setting.
* **DataView:** the search shows the matching settings of all tabs on one page, grouped by tab and section. They could
  be changed directly there. For settings, which depend on another setting, this setting is shown too.

The search terms in the URL (`esfw_search`) are used when the page is opened, e.g. from a link to a match. The
parameter `esfw_highlight` with the name of a setting scrolls to it and highlights it.

## What is searched

The search uses an index, which is built on demand from the pages, tabs, sections and settings registered during the
request. It is not saved anywhere, so it is always up-to-date (e.g. translations, new settings).

For each setting the index contains:

* the title and the description of its field and its help text,
* the labels of the options (e.g. of a select or radio field), so "hourly" finds an interval setting,
* the titles of its tab, sub-tab and section,
* additional search terms (see below),
* the name of the setting (e.g. for support requests).

Tabs are part of the index too, e.g. for tabs without settings like a log.

All words of a search must be found. A word matches the beginning of a word, words with at least 4 characters also
any part of it. Case and accents are ignored. Matches in the title are listed first, then matches in the tab or
section, then matches in the description, the options and the search terms.

## Additional search terms

Add other names of the same thing, which your users may search for:

```php
$setting->set_search_keywords( 'offload, cloud, S3' );
$tab->set_search_keywords( array( 'log', 'errors', 'debug' ) );
```

Or in the JSON configuration: `"search_keywords": "offload, cloud, S3"` for settings and tabs.

## Add own entries

Use the filter `<slug>_settings_search_index` to add own entries, e.g. for pages of your plugin outside of the
settings. Each entry needs at least `type`, `name`, `title` and `url`. Set `external` to `true` to open the URL in a
new window:

```php
add_filter( 'my-plugin_settings_search_index', function( array $entries ): array {
    $entries[] = array(
        'type'     => 'page',
        'name'     => 'my-tools',
        'title'    => 'Tools',
        'keywords' => 'export, import',
        'url'      => admin_url( 'tools.php?page=my-tools' ),
    );
    return $entries;
} );
```

## Command palette

The settings could also be found via the command palette of WordPress (`Ctrl + K`). This is optional too and
requires the enabled search:

```php
$settings_obj->set_search( true );
$settings_obj->set_search_in_command_palette( true );
```

Or in the JSON configuration: `"search_in_command_palette": true`.

Plugins with an own command palette could use the search without this option:

```php
$results = $settings_obj->get_search()->get_results( $search, 10, array( 'setting' ) );
foreach ( $results as $entry ) {
    $url = $settings_obj->get_search()->get_result_url( $entry, $search );
}
```

## Texts

The texts of the search are part of the translations (`Settings::set_translations()`), e.g. `search_placeholder`,
`search_no_results_tab`, `search_other_results` and `search_command_label`. See `Settings::get_translations()` for
all keys.
