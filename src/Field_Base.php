<?php
/**
 * This file holds an object for a single checkbox field.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Object to hold single setting.
 */
class Field_Base {

	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = '';

	/**
	 * The title / label for this field.
	 *
	 * @var string
	 */
	private string $title = '';

	/**
	 * The description for this field.
	 *
	 * @var string
	 */
	private string $description = '';

	/**
	 * Readonly marker.
	 *
	 * @var bool
	 */
	private bool $readonly = false;

	/**
	 * The sanitize callback.
	 *
	 * @var array
	 */
	private array $sanitize_callback = array();

    /**
     * Depending setting.
     *
     * @var array
     */
    private array $depends = array();

	/**
	 * The setting this field belongs to.
	 *
	 * @var Setting|false
	 */
	private Setting|false $setting = false;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Return the HTML-code to display this field.
	 *
	 * @param array $attr Attributes for this field.
	 *
	 * @return void
	 */
	public function display( array $attr ): void {
		// bail if attributes are empty.
		if ( empty( $attr ) ) {
			return;
		}

		// echo an empty string.
		echo '';
	}

	/**
	 * Return title for this field.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Set title for this field.
	 *
	 * @param string $title The title to use.
	 *
	 * @return void
	 */
	public function set_title( string $title ): void {
		$this->title = $title;
	}

	/**
	 * Return description for this field.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * Set description for this field.
	 *
	 * @param string $description The description to use.
	 *
	 * @return void
	 */
	public function set_description( string $description ): void {
		$this->description = $description;
	}

	/**
	 * Return whether this field is set to readonly.
	 *
	 * @return bool
	 */
	public function is_readonly(): bool {
		/**
		 * Filter the readonly setting for the actual setting.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param bool $readonly The actual value.
		 * @param Field_Base $this The field object.
		 */
		return apply_filters( Settings::get_instance()->get_slug() . '_setting_readonly', $this->readonly, $this );
	}

	/**
	 * Set description for this field.
	 *
	 * @param bool $readonly_value The readonly marker.
	 *
	 * @return void
	 */
	public function set_readonly( bool $readonly_value ): void {
		$this->readonly = $readonly_value;
	}

	/**
	 * Return the callback for this field.
	 *
	 * @return array
	 */
	public function get_callback(): array {
		return array( $this, 'display' );
	}

	/**
	 * Return the sanitize callback.
	 *
	 * @return array
	 */
	public function get_sanitize_callback(): array {
		// if setting is empty, use our default callback.
		if ( empty( $this->sanitize_callback ) ) {
			return array( $this, 'sanitize_callback' );
		}

		// return the sanitize callback.
		return $this->sanitize_callback;
	}

	/**
	 * Set custom sanitize callback.
	 *
	 * @param array $sanitize_callback The custom sanitize callback.
	 *
	 * @return void
	 */
	public function set_sanitize_callback( array $sanitize_callback ): void {
		$this->sanitize_callback = $sanitize_callback;
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return mixed
	 */
	public function sanitize_callback( mixed $value ): mixed {
		return $value;
	}

	/**
	 * Return the field type name.
	 *
	 * @return string
	 */
	public function get_type_name(): string {
		return $this->type_name;
	}

	/**
	 * Return the setting this field belongs to.
	 *
	 * @return Setting|false
	 */
	public function get_setting(): Setting|false {
		return $this->setting;
	}

	/**
	 * Set the setting this field belongs to.
	 *
	 * @param Setting $setting The setting.
	 *
	 * @return void
	 */
	public function set_setting( Setting $setting ): void {
		$this->setting = $setting;
	}

    /**
     * Return the configured fields this field depends on.
     *
     * @return string
     */
    public function get_depend(): string {
        // bail if no depends fields are set.
        if( empty( $this->depends ) ) {
            return '';
        }

        // generate JSON of this setting.
        $json = wp_json_encode( $this->depends, JSON_FORCE_OBJECT );

        // bail if JSON could not be generated.
        if( ! $json ) {
            return '';
        }

        // return the JSON string with the configuration.
        return $json;
    }

    /**
     * Add a setting this field depends on.
     *
     * This field will only be visible if this setting has the requested value.
     *
     * @param Setting $setting The setting this fields depends on.
     * @param mixed   $value The value it must have.
     *
     * @return void
     */
    public function add_depend( Setting $setting, mixed $value ): void {
        $this->depends[$setting->get_name()] = $value;
    }

    /**
     * Return the placeholder.
     *
     * @return string
     */
    public function get_placeholder(): string {
        return '';
    }
}
