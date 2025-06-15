# Easy Settings for WordPress

This composer packages add a simple wrapper for settings for plugins and themes.

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
