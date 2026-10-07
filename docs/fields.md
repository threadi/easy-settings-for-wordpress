# Fields

This composer package provides the following fields for use as settings in WordPress plugins or themes.

## List of fields.

| Field         | Outout                                                               | REST API |
|---------------|----------------------------------------------------------------------|----------|
| Button        | Show a clickable button to run custom tasks                          | false    |
| Checkbox      | Show a checkbox to enable or diable things                           | used     |
| Checkboxes    | Show a list of checkboxes where each could be checked                | used     |
| Date          | Show a field to choose a date                                        | used     |
| DateTime      | Show a field to choose a date and a time                             | used     |
| FieldTable    | Show a list of fields in a table                                     | false    |
| File          | Choose a field from the media library or upload it here              | used     |
| Files         | Choose one or more fields from the media library or upload them here | used     |
| MultiField     | Repeat one inner field to collect a list of entries                  | used     |
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
| Time | Show a field to choose a time | used     |
| Value | Show a value from a setting without option to configure it | false    |

## Hint

Some fields provide their own configurations for the REST API. You do not need to specify these explicitly yourself.

## Nested fields (MultiField and FieldTable)

`MultiField` (its repeated inner field) and `FieldTable` (its cells) accept any of
the following field types. In the classic view every field type works; in the
DataView these types are supported:

`Text`, `Textarea`, `Number`, `Password`, `Select`, `Radio`, `Checkbox`,
`MultiSelect`, `File`, `Files`, `SelectPostTypeObject`, `PermalinkSlug`,
`Date`, `DateTime`, `Time`.

Layout / action / display-only fields (`Button`, `TextInfo`, `Value`, `Table`,
`FieldTable`) and a nested `MultiField` are not supported as inner fields and are
ignored in the DataView.

Hint: `FieldTable` in the DataView requires the `simple` storage method (the
default), because its cells are registered as individual REST options.

## Date and time fields (Date, DateTime and Time)

These fields use the native date and time picker of the browser in the classic
view and in the DataView. Their values are saved as string in a fixed format:

| Field    | Format      | Example            |
|----------|-------------|--------------------|
| Date     | `Y-m-d`     | `2026-12-24`       |
| DateTime | `Y-m-d H:i` | `2026-12-24 12:00` |
| Time     | `H:i`       | `12:00`            |

The values do not contain a timezone. Interpret them in the timezone of the
website, e.g.:

```
$date = date_create_immutable( get_option( 'my_setting' ), wp_timezone() );
```

An empty string is saved if no or an invalid value is given. Use the type
`string` for the setting:

```
$setting = $settings_object->add_setting( 'my_start_time' );
$setting->set_section( $section );
$setting->set_type( 'string' );
$setting->set_default( '12:00' );
$field = new Time( $settings_object );
$field->set_title( 'Start time' );
$setting->set_field( $field );
```

### Limit the value

`$field->set_min( '08:00' );`

`$field->set_max( '18:00' );`

Both must be given in the format of the field. A value outside of this range is
set to the nearest limit on save. A `Time` field also accepts a range which
crosses midnight (e.g. min `22:00` and max `06:00`).

### Set the step

`$field->set_step( 900 );`

`DateTime` and `Time` use seconds (900 = steps of 15 minutes), `Date` uses days.
If the step is not a multiple of 60, the time is saved with seconds (`H:i:s`).

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
