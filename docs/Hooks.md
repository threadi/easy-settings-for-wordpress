# Hooks

## DataView

Example to use hooks in dataview:

```
import { addAction, addFilter } from '@wordpress/hooks';

addAction(
  'esfw.settingsPage.mounted',
  'my-plugin/settings-mounted',
  ( { config, settings } ) => {
    // add custom handler if SettingsPage has been mounted.
  }
);

addAction(
  'esfw.settingsPage.button.mounted',
  'my-plugin/settings-button-mounted',
  ( { buttonTitle, buttonUrl, buttonClasses, buttonData } ) => {
    // add custom handler if Button has been mounted.
  }
);

addFilter(
  'esfw.settingsPage.fields',
  'my-plugin/add-custom-field',
  ( fields, config ) => {
    // add additional field.
    return [ ...fields, { id: 'my_extra_field', type: 'text', label: 'Extra' } ];
  }
);

addFilter(
  'esfw.settingsPage.beforeSave',
  'my-plugin/trim-message',
  ( settings, config ) => {
    // run custom tasks to change settings before they are saved.
    return settings;
  }
);
```
