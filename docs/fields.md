# Fields

This composer package provides the following fields for use as settings in WordPress plugins or themes.

## List of fields.

| Field         | Outout                                                               | REST API |
|---------------|----------------------------------------------------------------------|----------|
| Button        | Show a clickable button to run custom tasks                          | false    |
| Checkbox      | Show a checkbox to enable or diable things                           | used     |
| Checkboxes    | Show a list of checkboxes where each could be checked                | used     |
| FieldTable    | Show a list of fields in a table                                     | false    |
| File          | Choose a field from the media library or upload it here              | used     |
| Files         | Choose one or more fields from the media library or upload them here | used     |
| MultiField     | Show multiple files in a list                                        | used     |
| MultiSelect   | Show a field to select multiple entries | used     |
| Number        | Show a field to input a number                                       | false    |
| Passwort      | Show a password field, e.g., to enter a API key                      | false    |
| PermalinkSlug | Configure individuel permalink slug with parameters                  | used     |
| Radio         | Show a list of radio boxes where only one could be selected | false    |
| Select        | Show a dropdown to select one entry. | false    |
| SelectPostTypeObject | Select a post type object, e.g., to chose a page as target for something | used     |
| Table | Show a table of settings | used     |
| Text | Show a simple text field | false    |
| Textarea | Show a multiline text field | false    |
| TextInfo | Show a info to the user, not usage als configuration | false    |
| Value | Show a value from a setting without option to configure it | false    |

## Hint

Some fields provide their own configurations for the REST API. You do not need to specify these explicitly yourself.

## Usage

Initialize a field:

```
$field = new Text( $settings_object );
```

The parameter must be the settings object.

The following functions are available for all fields in the object. Additional functions are available for individual fields.

### Set title

`$field->set_title( 'My field title' );`

### Set description

`$field->set_description( 'My field title' );`

### Make it dependent

If you want the visibility of one field to depend on another, you can add the other field here as a dependency:

`$field->add_depend( $setting, 'value' );`

The first parameter must be a Setting object of the other field.

The second parameter is the value it should have to show this field.

### Readonly

`$field->set_readonly( true );`

### Custom sanitize callback

`$field->set_sanitize_callback( array( $this, 'sanitize_my_field' ) );`
