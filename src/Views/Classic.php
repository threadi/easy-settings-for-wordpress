<?php
/**
 * File for an object to handle the classic view of settings in the backend.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Views;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Tab;
use easySettingsForWordPress\View_Base;
use easySettingsForWordPress\Views\Classic\Styling_Base;

/**
 * Object to handle the classic view of settings in the backend.
 */
class Classic extends View_Base {
	/**
	 * The internal name of this styling.
	 *
	 * @var string
	 */
	protected string $name = 'classic';

	/**
	 * The default styling.
	 *
	 * @var string
	 */
	private string $styling = 'horizontal_tabs';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;

		add_action( 'admin_enqueue_scripts', array( $this, 'add_js_and_css' ) );
	}

	/**
	 * Add own JS and CSS for the backend.
	 *
	 * @param string $hook The requested hook.
	 * @return void
	 */
	public function add_js_and_css( string $hook ): void {
		// bail if not the menu slug is called.
		if ( ! $this->get_settings_obj()->enqueue_styles_and_scripts( $hook ) ) {
			return;
		}

		// add backend JS.
		wp_enqueue_script(
			$this->get_settings_obj()->get_slug() . '-settings',
			$this->get_settings_obj()->get_url() . 'assets/js.js',
			array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ),
			Helper::get_file_version( $this->get_settings_obj()->get_path() . 'assets/js.js', $this->get_settings_obj() ),
			true
		);

		// add dirty.js.
		wp_enqueue_script(
			$this->get_settings_obj()->get_slug() . '-dirty',
			$this->get_settings_obj()->get_url() . 'assets/jquery.dirty.js',
			array( 'jquery' ),
			Helper::get_file_version( $this->get_settings_obj()->get_path() . 'assets/jquery.dirty.js', $this->get_settings_obj() ),
			true
		);

		// add backend CSS.
		wp_enqueue_style(
			$this->get_settings_obj()->get_slug() . '-settings',
			$this->get_settings_obj()->get_url() . 'assets/style.css',
			array(),
			Helper::get_file_version( $this->get_settings_obj()->get_path() . 'assets/style.css', $this->get_settings_obj() ),
		);

		// add CSS for chosen styling.
		$styling_object = $this->get_styling_object();
		if ( $styling_object instanceof Styling_Base ) {
			$styling_object->add_styles();
		}

		// get the translations.
		$translations = $this->get_settings_obj()->get_translations();

		// get the settings object.
		$settings_obj = $this->get_settings_obj();

		// add php-vars to our js-script.
		wp_localize_script(
			$settings_obj->get_slug() . '-settings',
			'esfwJsVars',
			array(
				'rest_settings'             => rest_url( 'wp/v2/settings' ),
				'rest_nonce'                => wp_create_nonce( 'wp_rest' ),
				'title_add_image'           => $translations['file_add_file'],
				'button_add_image'          => $translations['file_choose_file'],
				'lbl_upload_image'          => $translations['file_choose_image'],
				'label_sortable_title'      => $translations['drag_n_drop'],
				'auto_save'                 => $settings_obj->get_auto_save(),
				'lock_form_on_save'         => $settings_obj->should_lock_form_on_save(),
				'collapsible_sections'      => $this->get_collapsible_sections_map(),
				'persist_section_collapse'  => $settings_obj->should_persist_section_collapse(),
				'section_collapse_meta_key' => $settings_obj->get_section_collapse_meta_key(),
				'label_saved'               => $translations['settings_saved'],
				'label_save_error'          => $translations['settings_save_error'],
			)
		);

		// add media library.
		wp_enqueue_media();
	}

	/**
	 * Output this view.
	 *
	 * @return void
	 */
	public function display(): void {
		// get the styling object.
		$styling_object = $this->get_styling_object();

		// bail if no styling object could be found.
		if ( ! $styling_object instanceof Styling_Base ) {
			return;
		}

		// show the navigation.
		$styling_object->show_nav();
	}

	/**
	 * Return the object of the configured styling.
	 *
	 * @return Styling_Base|false
	 */
	public function get_styling_object(): Styling_Base|false {
		// prepare the result.
		$style_obj = false;

		// check each supported styling for the configured styling name.
		foreach ( $this->get_styling_objects() as $styling_name ) {
			// bail if the class name does not exist.
			if ( ! class_exists( $styling_name ) ) {
				continue;
			}

			// get the object.
			$obj = new $styling_name( $this->get_settings_obj() );

			// bail if an object is not Schedules_Base.
			if ( ! $obj instanceof Styling_Base ) {
				continue;
			}

			// bail if name does not match.
			if ( $obj->get_name() !== $this->get_styling() ) {
				continue;
			}

			// use this object.
			$style_obj = $obj;
		}

		// return the resulting object.
		return $style_obj;
	}

	/**
	 * Return the configured styling name.
	 *
	 * @return string
	 */
	private function get_styling(): string {
		return $this->styling;
	}

	/**
	 * Set the styling to use by its name.
	 *
	 * @param string $styling The styling name.
	 *
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function set_styling( string $styling ): void {
		$this->styling = $styling;
	}

	/**
	 * Return the list of possible styling objects.
	 *
	 * @return array<int,string>
	 */
	private function get_styling_objects(): array {
		$list = array(
			'\easySettingsForWordPress\Views\Classic\Styles\Horizontal_Tabs',
			'\easySettingsForWordPress\Views\Classic\Styles\Vertical_Tabs',
		);

		/**
		 * Filter the list of possible styling for settings.
		 *
		 * @since 2.0.0 Available since 2.0.0.
		 * @param array $list The list.
		 */
		return apply_filters( $this->get_settings_obj()->get_slug() . '_styling_objects', $list );
	}

	/**
	 * Show the content of this view.
	 *
	 * @param Tab $tab The requested tab.
	 *
	 * @return void
	 */
	public function show_content( Tab $tab ): void {
		// get the styling object.
		$styling_object = $this->get_styling_object();

		// bail if no styling object could be loaded.
		if ( ! $styling_object instanceof Styling_Base ) {
			return;
		}

		// show the styling object.
		$styling_object->show_content( $tab );
	}

	/**
	 * Return list of collapse sections.
	 *
	 * @return array<string,mixed>
	 */
	private function get_collapsible_sections_map(): array {
		$map = array();

		foreach ( $this->collect_all_tabs() as $tab ) {
			foreach ( $tab->get_sections() as $section ) {
				if ( ! $section->is_collapsible() ) {
					continue;
				}
				$name = $section->get_name();
				if ( isset( $map[ $name ] ) ) {
					continue;
				}
				$map[ $name ] = array(
					'title'     => $section->get_title(),
					'collapsed' => $this->get_settings_obj()->get_effective_section_collapsed( $section ),
				);
			}
		}

		return $map;
	}

	/**
	 * Collect all tabs.
	 *
	 * @return array<int,Tab>
	 */
	private function collect_all_tabs(): array {
		$all  = array();
		$walk = static function ( array $tabs ) use ( &$all, &$walk ): void {
			foreach ( $tabs as $tab ) {
				if ( ! $tab instanceof Tab ) {
					continue;
				}
				$all[] = $tab;
				$subs  = $tab->get_tabs();
				if ( ! empty( $subs ) ) {
					$walk( $subs );
				}
			}
		};

		$walk( $this->get_settings_obj()->get_tabs() );
		foreach ( $this->get_settings_obj()->get_pages() as $page ) {
			$walk( $page->get_tabs() );
		}

		return $all;
	}
}
