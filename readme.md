# Easy Settings for WordPress

This composer packages add a simple wrapper for settings for WordPress plugins and themes.

## Requirements

* composer

## Installation

`composer require threadi/easy-settings-for-wordpress`

## Usage

_TODO_

### Upgrade hints

#### for 3.0.0

- remove do_not_register() from TextInfo() and Value() fields
- remove set_type() from Checkbox() fields
- use add_data() instead of set_custom_attributes() for Import and Export buttons

### Sorting

* Pages are not sortable
* Tabs are sorted by its given position
* Sections are sorted by its given position
* Settings are sorted in the order they are added
-> use Setting->`move_before_setting()` to move a setting on a specific position

### Migrate the method settings will be saved

The package supports saving settings in various ways, which are referred to here as "methods." If you want to switch from one method to another in your plugin, there is a function that can help you do so.

#### Methods

* Simple (name "simple") - saves every setting in its own entry on the options table (default)
* One (name "one") - saves all settings in one entry on the options table

To set the method for your plugin/theme:

`$settings_obj->set_method( 'one' );`

#### Views

The settings can be displayed in the backend in various ways. The package offers the following options:

* Classic (name "classic") - the classic way to use settings in backend incl. tabs
* DataView (name "dataview") - the modern way to handle settings in backend, only usable in WordPress 7.0 or newer

To set the view for your plugin/theme:

`$settings_obj->set_view( 'classic' );`

Hint: if you set "dataview" users with WordPress < 7.0 will be use the classic view.

#### Migrate

Example to migrate from "Simple" to "One":

`$settings_obj->migrate_method( 'simple', 'one' );`

Hint: you should have already been set the new method as active method by using the following code:

`$settings_obj->set_method( 'one' );`

### Error handling

Get all errors:

`$settings_obj->get_errors();`

## For changes of this package

### Check for WordPress Coding Standards

#### Initialize

`composer install`

#### Run

`vendor/bin/phpcs --extensions=php --ignore=*/vendor/* --standard=WordPress .`

#### Repair

`vendor/bin/phpcbf --extensions=php --ignore=*/vendor/* --standard=WordPress .`

## Check for WordPress VIP Coding Standards

Hint: this check runs against the VIP-GO-platform which is not our target for this package. Many warnings can be ignored.

### Run

`vendor/bin/phpcs --extensions=php --ignore=*/vendor/* --standard=WordPress-VIP-Go .`
