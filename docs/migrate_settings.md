# Migrate settings

This document does not describe how to migrate existing settings from other projects into this package. It describes how to migrate settings already managed by this package between the various supported methods.

The package supports saving settings in various ways, which are referred to here as "methods". If you want to switch from one method to another in your plugin, there is a function that can help you do so.

#### Migrate

Example to migrate from "Simple" to "One":

```
$settings->set_method( 'simple' );
// define your settings here.
$settings->migrate_method( 'one' );
$settings->init();
```
