<?php
/**
 * File for the method "OwnTable" to handle settings.
 *
 * This method will save all settings in a dedicated, package-own database table
 * (one row per setting) instead of using the WordPress-own options table.
 * It supports reading and updating their values via get_option() and update_option(),
 * exactly like the other methods, by redirecting the relevant WordPress hooks to this table.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress\Methods;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Fields\FieldTable;
use easySettingsForWordPress\Method_Base;
use easySettingsForWordPress\Section;
use easySettingsForWordPress\Setting;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use wpdb;

/**
 * Object to handle the method "OwnTable" to handle settings.
 */
class OwnTable extends Method_Base {
	/**
	 * The internal name of this method.
	 *
	 * @var string
	 */
	protected string $name = 'own_table';

	/**
	 * The version of our own table schema.
	 *
	 * Increase this value if the table structure in get_schema() changes so
	 * that install_table() runs dbDelta() again on the next request.
	 *
	 * @var int
	 */
	private int $db_version = 1;

	/**
	 * Runtime cache of all settings read from our own table, keyed by setting name.
	 *
	 * Prevents running a single-row query for every setting during one request.
	 * Reset to null whenever the table content changes.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $settings_cache = null;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Initialize this object.
	 *
	 * @return void
	 */
	public function init(): void {
		// check for our table.
		$this->install_table();

		// register the settings.
		$this->register_settings();

		// initialize the parent object.
		parent::init();
	}

	/**
	 * Return the name of the database table we use, including the WP table prefix.
	 *
	 * @return string
	 */
	private function get_table_name(): string {
		$table_name = $this->get_wpdb()->prefix . 'esfw_' . $this->get_settings_obj()->get_slug() . '_settings';

		/**
		 * Filter the name of the database table to use for this method.
		 *
		 * @since 3.6.0 Available since 3.6.0.
		 * @param string $table_name The name of the table.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_settings_table_name', $table_name );
	}

	/**
	 * Return the name of the option we use to track the installed table schema version.
	 *
	 * @return string
	 */
	private function get_db_version_option_name(): string {
		return 'esfw_' . $this->get_settings_obj()->get_slug() . '_settings_table_version';
	}

	/**
	 * Return the global $wpdb object.
	 *
	 * @return wpdb
	 */
	private function get_wpdb(): wpdb {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Return the SQL used to create resp. update our own table, in the format dbDelta() expects.
	 *
	 * @return string
	 */
	private function get_schema(): string {
		$table_name      = $this->get_table_name();
		$charset_collate = $this->get_wpdb()->get_charset_collate();

		return "CREATE TABLE $table_name (
			setting_name varchar(191) NOT NULL,
			setting_value longtext NOT NULL,
			autoload varchar(20) NOT NULL DEFAULT 'yes',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (setting_name)
		) $charset_collate;";
	}

	/**
	 * Create resp. update our own database table.
	 *
	 * Safe to call on every admin_init: dbDelta() only runs (and only alters the
	 * table) once the installed version marker no longer matches $db_version.
	 *
	 * @return void
	 */
	public function install_table(): void {
		// bail if the table is already installed in the current version.
		if ( (int) get_option( $this->get_db_version_option_name(), 0 ) === $this->db_version ) {
			return;
		}

		// dbDelta() is not loaded by default.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// create resp. update the table.
		dbDelta( $this->get_schema() );

		// remember the installed schema version.
		update_option( $this->get_db_version_option_name(), $this->db_version, true );
	}

	/**
	 * Run these tasks during activation of the plugin.
	 *
	 * @return void
	 */
	public function activation(): void {
		// create resp. update our own table.
		$this->update_settings();

		// collect the default values of all settings.
		$values = array();
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			if ( ! $setting->is_default_set() ) {
				continue;
			}

			// do not overwrite a value which has already been saved.
			if ( null !== $this->read_raw_value( $setting->get_name() ) ) {
				continue;
			}

			$values[ $setting->get_name() ] = $setting->get_default();
		}

		// save them, each through its own sanitizing.
		foreach ( $values as $setting_name => $value ) {
			$this->write_raw_value( $setting_name, $this->sanitize_value( $value, $setting_name ), $this->get_settings_obj()->get_setting( $setting_name ) );
		}
	}

	/**
	 * Register the handling of get_option() and update_option() for each setting field,
	 * which should be read and write from our own database table.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// bail if no settings are set.
		if ( ! $this->get_settings_obj()->has_settings() ) {
			return;
		}

		// Field-table cells first (no section of their own), same handling as with "one" and "simple".
		$handled_cells = array();
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			$field_obj = $setting->get_field();
			if ( ! $field_obj instanceof FieldTable ) {
				continue;
			}
			$section = $setting->get_section();
			if ( ! $section instanceof Section ) {
				continue;
			}
			$tab = $section->get_tab();
			if ( ! $tab instanceof Tab ) {
				continue;
			}
			foreach ( $field_obj->get_cell_settings_flat() as $cell_setting ) {
				if ( $cell_setting->should_not_be_registered() ) {
					continue;
				}
				$this->register_single_setting( $cell_setting, $tab );
				$handled_cells[ $cell_setting->get_name() ] = true;
			}
		}

		// then regular settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			if ( $setting->should_not_be_registered() ) {
				continue;
			}
			if ( isset( $handled_cells[ $setting->get_name() ] ) ) {
				continue;
			}
			$section = $setting->get_section();
			if ( ! $section instanceof Section ) {
				// a field-table cell without a resolvable tab: skip silently,
				// it was already attempted above.
				if ( true === $setting->get_custom_var( 'esfw_field_table_cell' ) ) {
					continue;
				}

				// log this as error.
				$this->get_settings_obj()->add_error(
					'setting_missing_section',
					'A section is missing for a setting.',
					array(
						'setting' => $setting->get_name(),
					)
				);

				continue;
			}
			$tab = $section->get_tab();
			if ( ! $tab instanceof Tab ) {
				// log this as error.
				$this->get_settings_obj()->add_error(
					'setting_missing_tab',
					'A tab is missing for a setting.',
					array(
						'setting' => $setting->get_name(),
					)
				);

				continue;
			}
			$this->register_single_setting( $setting, $tab );
		}

		$this->get_settings_obj()->maybe_update();
	}

	/**
	 * Register a single setting for the Settings API and the REST API, and hook its
	 * value into our own table via the pre_option and pre_update_option filters.
	 *
	 * @param Setting $setting The setting to register.
	 * @param Tab     $tab     The tab (settings group) to register it under.
	 *
	 * @return void
	 */
	private function register_single_setting( Setting $setting, Tab $tab ): void {
		$args = array(
			'type'         => $setting->get_type(),
			'default'      => $setting->get_default(),
			'show_in_rest' => $setting->get_show_in_rest(),
		);

		$field_obj = $setting->get_field();
		if ( $field_obj instanceof Field_Base ) {
			$args['sanitize_callback'] = $field_obj->get_sanitize_callback();
		}

		$schema = array( 'type' => $setting->get_type() );
		if ( $field_obj instanceof Field_Base ) {
			$schema = array_merge( $schema, $field_obj->get_rest_schema() );
		}
		if ( isset( $schema['type'] ) ) {
			$args['type'] = $schema['type'];
		}

		if ( $setting->is_show_in_rest() ) {
			if ( is_array( $args['show_in_rest'] ) && isset( $args['show_in_rest']['schema'] ) ) {
				$schema = array_merge( $schema, $args['show_in_rest']['schema'] );
			}
			if ( isset( $schema['type'] ) && 'array' === $schema['type'] && ! isset( $schema['items'] ) ) {
				$schema['items'] = array();
			}
			if ( isset( $schema['type'] ) && 'object' === $schema['type'] && ! isset( $schema['properties'] ) && ! isset( $schema['additionalProperties'] ) ) {
				$schema['additionalProperties'] = true;
			}

			// set marker.
			$schema[ $this->get_settings_obj()->get_slug() ] = $field_obj instanceof Field_Base;

			$show_in_rest           = is_array( $args['show_in_rest'] ) ? $args['show_in_rest'] : array();
			$show_in_rest['schema'] = $schema;
			$args['show_in_rest']   = $show_in_rest;
		} else {
			$args['show_in_rest'] = false;
		}

		// helper to view this setting in REST API and the classic Settings API.
		register_setting(
			$tab->get_name(),
			$setting->get_name(),
			$args
		);

		// redirect reading/writing of this option to our own table.
		add_filter( 'pre_option_' . $setting->get_name(), array( $this, 'get_option' ), 10, 2 );
		add_filter( 'pre_update_option_' . $setting->get_name(), array( $this, 'save_option' ), 10, 3 );
	}

	/**
	 * Register the filters for a setting that is added after init() has already run once
	 * (e.g. by an add-on registering its own settings on a later hook).
	 *
	 * Mirrors the "one" method: if a section/tab can already be resolved, run the full
	 * registration (REST schema + hooks); otherwise fall back to just wiring the hooks
	 * so get_option()/update_option() keep working for this setting.
	 *
	 * @param Setting $setting The setting.
	 *
	 * @return void
	 */
	public function register_setting_value_filters( Setting $setting ): void {
		// bail if this should not be registered.
		if ( $setting->should_not_be_registered() ) {
			return;
		}

		// register in REST API, if a tab can already be resolved.
		$section = $setting->get_section();
		if ( $section instanceof Section ) {
			$tab = $section->get_tab();
			if ( $tab instanceof Tab ) {
				$this->register_single_setting( $setting, $tab );
				return;
			}
		}

		// use hooks only, so at least get_option()/update_option() keep working.
		add_filter( 'pre_option_' . $setting->get_name(), array( $this, 'get_option' ), 10, 2 );
		add_filter( 'pre_update_option_' . $setting->get_name(), array( $this, 'save_option' ), 10, 3 );
	}

	/**
	 * Return the value of a single setting from our own table.
	 *
	 * @param mixed  $value The value to return if nothing is found in our table.
	 * @param string $setting_name The name of the setting.
	 *
	 * @return mixed
	 */
	public function get_option( mixed $value, string $setting_name ): mixed {
		$stored_value = $this->read_raw_value( $setting_name );

		// bail if this setting has no row in our table yet.
		if ( null === $stored_value ) {
			return $value;
		}

		// get the setting object.
		$setting = $this->get_settings_obj()->get_setting( $setting_name );

		// run the custom callback after reading an option.
		if ( $setting instanceof Setting && $setting->has_read_callback() ) {
			$stored_value = call_user_func( $setting->get_read_callback(), $stored_value );
		}

		return $stored_value;
	}

	/**
	 * Save the given value of a single setting in our own table.
	 *
	 * @param mixed  $value The new value.
	 * @param mixed  $old_value The old value.
	 * @param string $setting_name The name of the setting.
	 *
	 * @return mixed
	 */
	public function save_option( mixed $value, mixed $old_value, string $setting_name ): mixed {
		// get the settings object.
		$setting = $this->get_settings_obj()->get_setting( $setting_name );

		// sanitize the value of this setting.
		$value = $this->sanitize_value( $value, $setting_name );

		// run the custom callback before updating an option.
		if ( $setting instanceof Setting && $setting->has_save_callback() ) {
			$value = call_user_func( $setting->get_save_callback(), $value );
		}

		// write the row in our own table.
		$this->write_raw_value( $setting_name, $value, $setting instanceof Setting ? $setting : false );

		// tell WordPress to keep using its current (filtered) value, since we already stored it ourselves.
		return $old_value;
	}

	/**
	 * Read the raw, unsanitized, un-filtered value of a single setting from our own table.
	 *
	 * Populates and reuses the request-wide cache so repeated reads (e.g. for
	 * several settings on the same page) only cost a single query.
	 *
	 * @param string $setting_name The internal name of the setting.
	 *
	 * @return mixed Returns null if no row exists for this setting.
	 */
	private function read_raw_value( string $setting_name ): mixed {
		$this->load_settings_cache();

		return $this->settings_cache[ $setting_name ] ?? null;
	}

	/**
	 * Write the raw value of a single setting to our own table and refresh the cache.
	 *
	 * @param string        $setting_name The internal name of the setting.
	 * @param mixed         $value The value to store (already sanitized).
	 * @param Setting|false $setting The setting object, if available (used for the autoload-flag).
	 *
	 * @return void
	 */
	private function write_raw_value( string $setting_name, mixed $value, Setting|false $setting ): void {
		$this->get_wpdb()->replace(
			$this->get_table_name(),
			array(
				'setting_name'  => $setting_name,
				'setting_value' => maybe_serialize( $value ),
				'autoload'      => ( $setting instanceof Setting && ! $setting->is_autoloaded() ) ? 'no' : 'yes',
				'updated_at'    => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);

		if ( ! is_array( $this->settings_cache ) ) {
			$this->load_settings_cache();
		}
		$this->settings_cache[ $setting_name ] = $value;
		wp_cache_set( $this->get_table_name(), $this->settings_cache, 'esfw_settings' );
	}

	/**
	 * Load all rows of our own table into the request-wide cache, once per request.
	 *
	 * @return void
	 */
	private function load_settings_cache(): void {
		// bail if already loaded.
		if ( is_array( $this->settings_cache ) ) {
			return;
		}

		// use the object cache, if any, to also spare the query across requests where supported.
		$cache_key    = $this->get_table_name();
		$cached_value = wp_cache_get( $cache_key, 'esfw_settings' );
		if ( is_array( $cached_value ) ) {
			$this->settings_cache = $cached_value;
			return;
		}

		$table_name = $this->get_table_name();
		$rows       = $this->get_wpdb()->get_results( "SELECT setting_name, setting_value FROM $table_name", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$settings = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$settings[ $row['setting_name'] ] = maybe_unserialize( $row['setting_value'] );
			}
		}

		$this->settings_cache = $settings;
		wp_cache_set( $cache_key, $settings, 'esfw_settings' );
	}

	/**
	 * Reset the request-wide (and object) cache of our own table.
	 *
	 * @return void
	 */
	private function reset_cache(): void {
		$this->settings_cache = null;
		wp_cache_delete( $this->get_table_name(), 'esfw_settings' );
	}

	/**
	 * Delete all settings, i.e. empty our own table and remove the schema version marker.
	 *
	 * The table structure itself is intentionally kept (a consuming plugin's
	 * uninstall routine should DROP it explicitly if it should also disappear).
	 *
	 * @return void
	 */
	public function delete_settings(): void {
		$table_name = $this->get_table_name();

		$this->get_wpdb()->query( "TRUNCATE TABLE $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		delete_option( $this->get_db_version_option_name() );

		$this->reset_cache();
	}

	/**
	 * Return the value of a single setting from our own table.
	 *
	 * Used e.g. during migration to read data from the method we are migrating away from,
	 * and by the sibling methods when migrating away from us.
	 *
	 * @param string $setting_name The internal name of the setting.
	 *
	 * @return mixed
	 */
	public function get_setting_value( string $setting_name ): mixed {
		return $this->read_raw_value( $setting_name );
	}

	/**
	 * Return whether this method already has settings data saved.
	 *
	 * @return bool
	 */
	public function has_data(): bool {
		$this->load_settings_cache();

		return ! empty( $this->settings_cache );
	}

	/**
	 * Migrate existing settings to this method.
	 *
	 * @param Method_Base $old_method Object of the old method.
	 *
	 * @return void
	 */
	public function migrate( Method_Base $old_method ): void {
		// make sure our own table exists before we try to write to it.
		$this->install_table();

		// bail if we already have data - migration already happened.
		if ( $this->has_data() ) {
			return;
		}

		// loop through the settings.
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			$setting_name = $setting->get_name();

			// read the value using the OLD method's own logic.
			$value = $old_method->get_setting_value( $setting_name );

			// sanitize and store it in our own table.
			$this->write_raw_value( $setting_name, $this->sanitize_value( $value, $setting_name ), $setting );
		}
	}

	/**
	 * Sanitize a single setting's value the same way the "one" and "simple" methods do:
	 * via the field's own sanitize_callback, so all storage methods enforce the same
	 * validation rules for the same field.
	 *
	 * Falls back to the generic type-coercion in Method_Base::sanitize_option()
	 * only if no field is attached to this setting.
	 *
	 * @param mixed  $value The value to sanitize.
	 * @param string $setting_name The internal name of the setting.
	 *
	 * @return mixed
	 */
	private function sanitize_value( mixed $value, string $setting_name ): mixed {
		$setting = $this->get_settings_obj()->get_setting( $setting_name );

		if ( ! $setting instanceof Setting ) {
			return $this->sanitize_option( $value, $setting_name );
		}

		$field_obj = $setting->get_field();

		if ( ! $field_obj instanceof Field_Base ) {
			return $this->sanitize_option( $value, $setting_name );
		}

		return call_user_func( $field_obj->get_sanitize_callback(), $value );
	}

	/**
	 * Seed missing default values after a consumer plugin version bump.
	 * Called by Settings::maybe_update(). Schema is handled by install_table().
	 *
	 * @return void
	 */
	public function update_settings(): void {
		$this->install_table();

		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			if ( ! $setting->is_default_set() ) {
				continue;
			}

			if ( null !== $this->read_raw_value( $setting->get_name() ) ) {
				continue;
			}

			$this->write_raw_value(
				$setting->get_name(),
				$this->sanitize_value( $setting->get_default(), $setting->get_name() ),
				$setting
			);
		}
	}
}
