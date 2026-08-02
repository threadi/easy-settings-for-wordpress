# Pages

You can also display your plugins or themes settings on multiple pages. For more information, see the instructions here.

## Restrictions

An instance of `new Settings()` always handles exactly one page. You must create a separate instance for each page with settings. These instances can and should all have your plugin slug as a parameter, but should differ in their menu slug.

## Pages are not tabs

Note that pages are not tabs. It is entirely possible to distribute the settings across a theoretically unlimited number of tabs within a single page.
