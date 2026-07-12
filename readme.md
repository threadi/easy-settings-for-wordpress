# Easy Settings for WordPress

This composer packages add a simple wrapper for settings for WordPress plugins and themes.

## Requirements

* composer

## Installation

`composer require threadi/easy-settings-for-wordpress`

## Usage

_TODO_

### Sorting

* Pages are not sortable
* Tabs are sorted by its given position
* Sections are sorted by its given position
* Settings are sorted in the order they are added
-> use Setting->`move_before_setting()` to move a setting on a specific position

### Migrate the method settings will be saved

The package supports saving settings in various ways, which are referred to here as "methods." If you want to switch from one method to another in your plugin, there is a function that can help you do so.

#### Methods

* Simple => saves every setting in its own entry on the options table
* One => saves all settings in one entry on the options table

#### Migrate

Example to migrate from "Simple" to "One":

`$settings_obj->migrate_method( 'simple', 'one' );`

Hint: you should have already been set the new method as active method by using the following code:

`$settings_obj->set_method( 'one' );`

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
