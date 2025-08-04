# Changelog

## [1.12.2] - 04.08.2025

### Changed

- Extended field Button to return its custom attributes as array

## [1.12.1] - 23.07.2025

### Fixed

- Fixed active marker for subtab navigation

## [1.12.0] - 21.07.2025

### Added

- Added sortable option for MultiSelect field
- Added hide animation for settings if they are dependent

### Changed

- Moved easy dialog support to import/export

## [1.11.2] - 20.07.2025

### Fixed

- Fixed usage of easy dialog for WordPress script dependency

## [1.11.1] - 01.07.2025

### Fixed

- Fixed missing execution of dirty.js

## [1.11.0] - 30.06.2025

### Added

- Added warning if settings form is leaved without saving it

## [1.10.6] - 30.06.2025

### Fixed

- Fixed missing REST nonce

## [1.10.5] - 28.05.2025

### Fixed

- Fixed usage of widefat on Multiselect fields

## [1.10.4] - 24.05.2025

### Added

- Added readonly-attribute for select field
- Added default WordPress backend class "widefat" on some fields 

## [1.10.3] - 24.05.2025

### Fixed

- Fixed version number

## [1.10.2] - 23.05.2025

### Added

- Added error output

## Fixed

- Revert the change for support for easy dialog for WordPress

## [1.10.1] - 23.06.2025

### Added

- Added function to delete a setting from DB

### Changed

- Use next free index in each array if the target index is already used

### Fixed

- Fixed missing import of setting tab in File field
- Fixed support for easy dialog for WordPress

## [1.10.0] - 19.06.2025

### Added

- Added possibility for sub-tabs
- Added output of sub-tabs in settings page

## [1.9.1] - 15.06.2025

### Changed

- Field PermalinkSlug now also used a title for its list

## [1.9.0] - 15.06.2025

### Added

- Added new fields File and Files to select single or multiple files
- Added new field MultiField to show one field type multiple times for one setting
- Added new field SelectPostTypeObject which allows to search for any post type and let choose them for a setting
- Added function `is_settings_page()` to check if a settings page is called
- Added option to prevent registering of a setting, it will only be used as field

### Changed

- Number fields now using the default value for initial input value

### Fixed

- Fixed missing usage of data attributes on Button field

## [1.8.0] - 14.06.2025

### Added

- Added positions for sections
- Added option to move settings before other ones
- Added option to add complete settings on one rush
- Added new field PermalinkSlug for using a permalink field with URL-part-selection

### Changed

- Hooks for filter sections got new name
- Moved JS in subfolder
- Multiple new hooks

## [1.7.0] - 14.06.2025

### Added

- New Page object for pages where settings can be placed

### Changed

- Tabs are now assigned to Page and not to the main Settings object
- Changed `get_depend()` from protected to public

## [1.6.1] - 13.06.2025

### Changed

- version number fixed

## [1.6.0] - 13.06.2025

### Added

- Assign tabs and sections to pages

### Changed

- Display only tabs and settings for requested page

## [1.5.0] - 12.06.2025

### Added

- Added new field TextInfo to output just a text info
- Added new field Textarea for multiline text fields
- Added option to delete an existing tab from settings

### Changed

- Button field can now also use data-attributes

## [1.4.1] - 11.06.2025

### Changed

- all get_options() for fields are now public

## [1.4.0] - 11.06.2025

### Added

- Added custom vars on settings

### Changed

- Placeholder now also in Field_Base

## [1.3.2] - 10.06.2025

### Fixed

- Fixed missing version number update

## [1.3.1] - 10.06.2025

### Added

- Added custom position for tabs

### Changed

- Optimized each field for loading its own properties

### Fixed

- Fixed invalid HTML-code for Text and Checkbox fields
- Fixed missing embedding of own JS-file

## [1.3.0] - 10.06.2025

### Added

- Added option for menu position
- Added support for cpt-own menu entries
- Added new fields: Radio and Checkboxes (plural)
- Added read callback for settings
- Added option to depend the fields from each other in settings form

### Changed

- Text fields are now using widefat class

## [1.2.2] - 14.05.2025

### Changed

- Change some texts

## [1.2.1] - 27.04.2025

### Changed

- Optimized Number field for min, max and steps

### Fixed

- Fixed visibility of settings link in plugin list

## [1.2.0] - 27.04.2025

### Added

- Added output of generated settings link
- Added option to show settings link in plugin list

### Changed

- Allow functions as callback

## [1.1.1] - 21.04.2025

### Changed

- Optimized handling of adding tab, section and settings

## [1.1.0] - 18.04.2025

### Added

- Added setting for used URL
- Added dependency for Easy Dialog for WordPress

### Changed

- Changed usage of import script

## [1.0.2] - 18.04.2025

### Fixed

- Fixed missing version number update

## [1.0.1] - 18.04.2025

### Fixed

- Removed external dependencies

## [1.0.0] - 18.04.2025

### Added

- Initial release