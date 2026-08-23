<?php
/**
 * This file represents a single setting in the plugin settings.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
use easySettingsForWordPress\Fields\Button;
use easySettingsForWordPress\Fields\Checkboxes;
use easySettingsForWordPress\Fields\File;
use easySettingsForWordPress\Fields\Files;
use easySettingsForWordPress\Fields\MultiSelect;
use easySettingsForWordPress\Fields\PermalinkSlug;
use easySettingsForWordPress\Fields\Radio;
use easySettingsForWordPress\Fields\Select;
use easySettingsForWordPress\Fields\SelectPostTypeObject;
use easySettingsForWordPress\Fields\Value;

defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single setting.
 */
class Setting extends Base_Object {
	/**
	 * The section this setting belongs to.
	 *
	 * @var ?Section
	 */
	private ?Section $section = null;

	/**
	 * The field object.
	 *
	 * @var ?Field_Base
	 */
	private ?Field_Base $field = null;

	/**
	 * The type.
	 *
	 * @var string
	 */
	private string $type = 'string';

	/**
	 * The default value.
	 *
	 * @var mixed
	 */
	private mixed $default = null;

	/**
	 * Show in REST API.
	 *
	 * @var array<string,mixed>|bool
	 */
	private array|bool $show_in_rest = false;

	/**
	 * Read callback.
	 *
	 * @var callable
	 */
	private $read_callback;

	/**
	 * The callback for save the setting.
	 *
	 * @var callable
	 */
	private $save_callback;

	/**
	 * Export prevent marker.
	 *
	 * @var bool
	 */
	private bool $prevent_export = false;

	/**
	 * Autoload.
	 *
	 * @var bool
	 */
	private bool $autoload = true;

	/**
	 * Help text for this setting.
	 *
	 * @var string
	 */
	private string $help = '';

	/**
	 * The custom vars.
	 *
	 * @var array<string,mixed>
	 */
	private array $vars = array();

	/**
	 * Do not register.
	 *
	 * @var bool
	 */
	private bool $do_not_register = false;

	/**
	 * Whether to force a full page reload after this setting was saved with a changed value.
	 *
	 * @var bool
	 */
	private bool $reload_on_save = false;

	/**
	 * Optional URL to redirect to after this setting was saved with a changed value.
	 * Takes precedence over reload_on_save.
	 *
	 * @var string
	 */
	private string $redirect_on_save = '';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Return the field object.
	 *
	 * @return Field_Base|null
	 */
	public function get_field(): ?Field_Base {
		return $this->field;
	}

	/**
	 * Set the field to this setting.
	 *
	 * If $field is an array it should contain:
	 * - type => one of: Checkbox, MultiSelect, Number, Select (required)
	 * - title => the title to use
	 * - description => the description to show
	 *
	 * @param array<string,mixed>|Field_Base $field The field to use or its configuration as array.
	 *
	 * @return false|Field_Base
	 */
	public function set_field( array|Field_Base $field ): false|Field_Base {
		// initialize the field object value.
		$field_obj = false;

		// if value is an array, create the field object first.
		if ( is_array( $field ) ) {
			// bail if array does not contain a type setting.
			if ( empty( $field['type'] ) ) {
				// log this as error.
				$this->get_settings_obj()->add_error(
					'field_type_not_given',
					'Field type not given.',
				);

				return false;
			}

			// get the object for the given field type.
			$field_obj = Helper::get_field_by_type_name( $field['type'], $this->get_settings_obj() );

			// bail if no object could be found.
			if ( ! $field_obj instanceof Field_Base ) {
				// prepare the message.
				$message = sprintf(
					'A field with the name "%s" is unknown.',
					$field['type']
				);

				// log this as error.
				$this->get_settings_obj()->add_error(
					'field_type_unknown',
					$message
				);

				// do nothing more.
				return false;
			}

			// set configuration.
			$field_obj->set_title( ! empty( $field['title'] ) ? $field['title'] : '' );
			$field_obj->set_description( ! empty( $field['description'] ) ? $field['description'] : '' );
		}

		// if value is a "Field_Base" object, use it.
		if ( $field instanceof Field_Base ) {
			$field_obj = $field;
		}

		// get the active view.
		$view = $this->get_settings_obj()->get_views()->get_view();

		if ( $view instanceof View_Base && 'dataview' === $view->get_name() && $field_obj->should_not_be_registered() ) {
			$this->do_not_register( true );
		}

		// add the field to this setting.
		$this->field = $field_obj;

		// return the field object.
		return $field_obj;
	}

	/**
	 * Return the section.
	 *
	 * @return Section|null
	 */
	public function get_section(): Section|null {
		return $this->section;
	}

	/**
	 * Set the section this setting will be assigned to.
	 *
	 * @param Section $section_obj The section this setting will be assigned to.
	 *
	 * @return void
	 */
	public function set_section( Section $section_obj ): void {
		$this->section = $section_obj;
	}

	/**
	 * Return the default value for this setting.
	 *
	 * @return mixed
	 */
	public function get_default(): mixed {
		return $this->default;
	}

	/**
	 * Set the default value for this setting.
	 *
	 * @param mixed $default_value The default value.
	 *
	 * @return void
	 */
	public function set_default( mixed $default_value ): void {
		$this->default = $default_value;
	}

	/**
	 * Return the settings type (e.g., "boolean" or "string").
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Set the type. One of:
	 * - boolean
	 * - integer
	 * - string
	 * - number
	 * - array
	 * - object.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_setting/
	 *
	 * @param string $type The type.
	 *
	 * @return void
	 */
	public function set_type( string $type ): void {
		// bail if given type is not supported.
		if ( ! Helper::is_setting_type_valid( $type ) ) {
			return;
		}

		// set the type.
		$this->type = $type;
	}

	/**
	 * Return whether to show this setting in REST API.
	 *
	 * @return array<string,mixed>|bool
	 */
	public function get_show_in_rest(): array|bool {
		return $this->show_in_rest;
	}

	/**
	 * Return whether to show this setting in REST API.
	 *
	 * @depreacted 2.1.0
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function is_show_in_rest(): bool {
		if ( ! is_bool( $this->get_show_in_rest() ) ) {
			return false;
		}
		return $this->get_show_in_rest();
	}

	/**
	 * Set the REST API setting for this setting.
	 *
	 * @param array<string,mixed>|bool $show_in_rest True to show in rest, array for array-types.
	 *
	 * @return void
	 */
	public function set_show_in_rest( array|bool $show_in_rest ): void {
		$this->show_in_rest = $show_in_rest;
	}

	/**
	 * Return whether this setting has a callback, which should be run before saving it.
	 *
	 * @return bool
	 */
	public function has_read_callback(): bool {
		return null !== $this->read_callback;
	}

	/**
	 * Return the save-callback.
	 *
	 * @return callable
	 */
	public function get_read_callback(): callable {
		return $this->read_callback;
	}

	/**
	 * Set the read callback.
	 *
	 * @param callable $read_callback The callback.
	 *
	 * @return void
	 */
	public function set_read_callback( callable $read_callback ): void {
		$this->read_callback = $read_callback;
	}

	/**
	 * Return whether this setting has a callback, which should be run before saving it.
	 *
	 * @return bool
	 */
	public function has_save_callback(): bool {
		return null !== $this->save_callback;
	}

	/**
	 * Return the save-callback.
	 *
	 * @return callable
	 */
	public function get_save_callback(): callable {
		return $this->save_callback;
	}

	/**
	 * Set the save-callback.
	 *
	 * @param callable $save_callback The save-callback.
	 *
	 * @return void
	 */
	public function set_save_callback( callable $save_callback ): void {
		$this->save_callback = $save_callback;
	}

	/**
	 * Return whether a default value is set.
	 *
	 * @return bool
	 */
	public function is_default_set(): bool {
		return $this->get_default() !== null;
	}

	/**
	 * Return the value of this setting.
	 *
	 * @return mixed
	 */
	public function get_value(): mixed {
		return get_option( $this->get_name() );
	}

	/**
	 * Return whether to prevent the export of this setting.
	 *
	 * @return bool
	 */
	public function is_export_prevented(): bool {
		return $this->prevent_export;
	}

	/**
	 * Set prevent the export of this setting.
	 *
	 * @param bool $prevent_export True to prevent the export.
	 *
	 * @return void
	 */
	public function prevent_export( bool $prevent_export ): void {
		$this->prevent_export = $prevent_export;
	}

	/**
	 * Return whether this setting has a help text.
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function has_help(): bool {
		return ! empty( $this->help );
	}

	/**
	 * Return the help text for this setting.
	 *
	 * @return string
	 */
	public function get_help(): string {
		return $this->help;
	}

	/**
	 * Set the help text.
	 *
	 * @param string $help The help text.
	 *
	 * @return void
	 */
	public function set_help( string $help ): void {
		$this->help = $help;
	}

	/**
	 * Return whether to autoload this setting.
	 *
	 * @return bool
	 */
	public function is_autoloaded(): bool {
		return $this->autoload;
	}

	/**
	 * Set if this setting should be autoloaded (true) or not (false).
	 *
	 * @param bool $autoload The new autoload value.
	 *
	 * @return void
	 */
	public function set_autoload( bool $autoload ): void {
		$this->autoload = $autoload;
	}

	/**
	 * Return the custom var for this setting.
	 *
	 * @param string $key The key.
	 *
	 * @return mixed
	 */
	public function get_custom_var( string $key ): mixed {
		// bail if key does not exist on an object.
		if ( ! isset( $this->vars[ $key ] ) ) {
			return false;
		}

		// return the value assigned to the key.
		return $this->vars[ $key ];
	}

	/**
	 * Add a custom var to this setting.
	 *
	 * @param string $key The key.
	 * @param mixed  $value The value.
	 *
	 * @return void
	 */
	public function add_custom_var( string $key, mixed $value ): void {
		$this->vars[ $key ] = $value;
	}

	/**
	 * Move a setting before another one.
	 *
	 * @param Setting $target_setting The setting before the actual object could be moved.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function move_before_setting( Setting $target_setting ): void {
		// get all settings.
		$settings = $this->get_settings_obj()->get_settings();

		// get position of target setting.
		$target_position = 0;
		$actual_position = 0;
		foreach ( $settings as $index => $setting ) {
			// get the position for the search target setting.
			if ( $setting->get_name() === $target_setting->get_name() ) {
				// get the index as position.
				$target_position = $index;
			}

			// get the position of the actual setting.
			if ( $setting->get_name() === $this->get_name() ) {
				$actual_position = $index;
			}
		}

		// remove the setting from its original position.
		unset( $settings[ $actual_position ] );

		// add the setting on the new position.
		$settings = Helper::add_array_in_array_on_position( $settings, $target_position, array( $target_position => $this ) );

		// save the new settings.
		$this->get_settings_obj()->set_settings( $settings ); // @phpstan-ignore argument.type
	}

	/**
	 * Return whether this setting should not be registered. It will only be used as field.
	 *
	 * @return bool
	 */
	public function should_not_be_registered(): bool {
		return $this->do_not_register;
	}

	/**
	 * Mark setting to not register it. It will only be used as field.
	 *
	 * @param bool $do_not_register True if setting should not be registered.
	 *
	 * @return void
	 */
	public function do_not_register( bool $do_not_register ): void {
		$this->do_not_register = $do_not_register;
		$this->prevent_export( $do_not_register );
	}

	/**
	 * Delete this setting from WP-own table.
	 *
	 * @return void
	 */
	public function delete(): void {
		delete_option( $this->get_name() );
	}

	/**
	 * Return the dataview configuration for this field.
	 *
	 * @return array<string,mixed>
	 */
	public function get_dataview(): array {
		// bail if no field is configured.
		if ( null === $this->get_field() ) {
			return array();
		}

		// get the field.
		$field = $this->get_field();

		// prepare the basic data.
		$configuration = array(
			'id'          => $this->get_name(),
			'label'       => $field->get_title(),
			'description' => wp_kses_post( $field->get_description() ),
			'depend'      => $field->get_depend_as_array(),
			'type'        => 'text'
		);

		// add type specific settings.
		switch ( $field->get_type_name() ) {
			case 'Button':
				$configuration['type'] = 'esfw-button';
				if ( $field instanceof Button ) {
					$configuration['button_title']   = $field->get_button_title();
					$configuration['button_url']     = $field->get_button_url();
					$configuration['button_classes'] = $field->get_classes_as_array();
					$configuration['button_data']    = $field->get_data_as_array();
				}
				break;
			case 'Checkbox':
				$configuration['type'] = 'boolean';
				break;
			case 'Checkboxes':
				$configuration['type'] = 'esfw-checkboxes';
				if ( $field instanceof Checkboxes ) {
					$options = array();
					foreach ( $field->get_options() as $key => $label ) {
						$options[] = array(
							'value' => $key,
							'label' => is_array( $label ) ? $label['label'] : $label,
						);
					}
					$configuration['options'] = $options;
				}
				break;
			case 'FieldTable':
				$configuration['type'] = 'esfw-table';
				break;
			case 'File':
				$configuration['type']     = 'media';
				$configuration['multiple'] = false;
				if ( $field instanceof File ) {
					$configuration['allowed_types'] = $field->get_file_types();
				}
				break;
			case 'Files':
				$configuration['type']     = 'media';
				$configuration['multiple'] = true;
				if ( $field instanceof Files ) {
					$configuration['allowed_types'] = $field->get_file_types();
				}
				break;
			case 'MultiField':
				$configuration['type'] = 'esfw-multifield';
				break;
			case 'MultiSelect':
				$configuration['type'] = 'esfw-multiselect';
				if ( $field instanceof MultiSelect ) {
					$options = array();
					foreach ( $field->get_options() as $key => $label ) {
						$options[] = array(
							'value' => (string) $key,
							'label' => $label,
						);
					}
					$configuration['options']  = $options;
					$configuration['sortable'] = $field->is_sortable();
				}
				break;
			case 'Number':
				$configuration['type'] = 'integer';
				break;
			case 'Password':
				$configuration['type'] = 'text';
				$configuration['Edit'] = 'password';
				break;
			case 'PermalinkSlug':
				$configuration['type'] = 'esfw-permalink-slug';
				if ( $field instanceof PermalinkSlug ) {
					$options = array();
					foreach ( $field->get_options() as $key => $label ) {
						$options[] = array(
							'placeholder' => '%' . $key . '%',
							'label'       => $label,
						);
					}
					$configuration['options']    = $options;
					$configuration['list_title'] = $field->get_list_title();
				}
				break;
			case 'Radio':
				$configuration['type'] = 'text';
				$configuration['Edit'] = 'radio';
				$options               = array();
				if ( $field instanceof Radio ) {
					foreach ( $field->get_options() as $key => $label ) {
						$options[] = array(
							'value' => $key,
							'label' => $label,
						);
					}
				}
				$configuration['elements'] = $options;
				break;
			case 'Select':
				$configuration['type'] = 'text';
				$configuration['Edit'] = 'select';
				$options               = array();
				if ( $field instanceof Select ) {
					foreach ( $field->get_options() as $key => $label ) {
						$options[] = array(
							'value' => $key,
							'label' => $label,
						);
					}
				}
				$configuration['elements'] = $options;
				break;
			case 'SelectPostTypeObject':
				$configuration['type'] = 'esfw-post-select';
				if ( $field instanceof SelectPostTypeObject ) {
					$configuration['endpoint']    = $field->get_endpoint();
					$configuration['limit']       = $field->get_limit();
					$configuration['placeholder'] = $field->get_placeholder();
				}
				break;
			case 'Table':
				$configuration['type'] = 'esfw-table';
				break;
			case 'Textarea':
				$configuration['type'] = 'text';
				$configuration['Edit'] = 'textarea';
				break;
			case 'TextInfo':
				$configuration['type']      = 'esfw-display';
				$configuration['is_static'] = true;
				$configuration['text']      = wp_kses_post( $field->get_description() );
				break;
			case 'Value':
				$configuration['type']      = 'esfw-display';
				$configuration['is_static'] = true;
				if ( $field instanceof Value ) {
					$configuration['text'] = $field->get_value();
				}
				break;
		}

		// set reload setting.
		if ( $this->should_reload_on_save() ) {
			$configuration['reload_on_save'] = true;
		}

		// set redirect setting.
		if ( ! empty( $this->get_redirect_on_save() ) ) {
			$configuration['redirect_on_save'] = $this->get_redirect_on_save();
		}

		// return the configuration for this setting to use in dataview.
		return $configuration;
	}

	/**
	 * Force a full page reload after saving a changed value of this setting.
	 *
	 * @param bool $reload Whether to reload.
	 * @return void
	 */
	public function set_reload_on_save( bool $reload ): void {
		$this->reload_on_save = $reload;
	}

	/**
	 * Redirect to a URL after saving a changed value of this setting.
	 *
	 * @param string $url Absolute or relative URL.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 **/
	public function set_redirect_on_save( string $url ): void {
		$this->redirect_on_save = $url;
	}

	/**
	 * Return whether we should reload on save.
	 *
	 * @return bool
	 */
	public function should_reload_on_save(): bool {
		return $this->reload_on_save;
	}

	/**
	 * Return the redirect target on save.
	 *
	 * @return string
	 */
	public function get_redirect_on_save(): string {
		return $this->redirect_on_save;
	}
}
