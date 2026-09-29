<?php
/**
 * File for the base object for any styling of classic settings.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress\Views\Classic;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Base_Object;
use easySettingsForWordPress\Tab;

/**
 * Base object for any styling of classic settings.
 */
class Styling_Base extends Base_Object {
	/**
	 * Add our styling.
	 *
	 * @return void
	 */
	public function add_styles(): void {}

	/**
	 * Show the navigation for this styling.
	 *
	 * @return void
	 */
	public function show_nav(): void {}

	/**
	 * Output the HTML-code for the settings.
	 *
	 * @param Tab $tab The tab to show.
	 *
	 * @return void
	 */
	public function show_content( Tab $tab ): void {
		// show the tab description.
		if ( ! empty( $tab->get_description() ) ) {
			echo wp_kses_post( $tab->get_description() );
		}

		?>
		<form method="POST" action="<?php echo esc_url( get_admin_url() ); ?>options.php">
			<?php
			if ( 'options-general.php' !== $this->settings_obj->get_menu_parent_slug() ) {
				settings_errors();
			}
			settings_fields( $tab->get_name() );
			do_settings_sections( $tab->get_name() );
			if ( ! $tab->is_save_hidden() && ! $tab->has_tabs() ) {
				submit_button();
			}
			?>
		</form>
		<?php
	}

	/**
	 * Output the content of the active tab and its active sub-tab.
	 *
	 * A main tab that only serves as container for its sub-tabs shows just its
	 * description, so no empty form is rendered. It is rendered completely if no
	 * sub-tab is active, or if it has its own callback or its own sections.
	 *
	 * @param false|Tab $main_active_tab The active main tab.
	 * @param false|Tab $sub_active_tab The active sub-tab.
	 *
	 * @return void
	 */
	protected function show_tab_contents( false|Tab $main_active_tab, false|Tab $sub_active_tab ): void {
		if ( $main_active_tab instanceof Tab ) {
			if ( ! $sub_active_tab instanceof Tab || $main_active_tab->has_custom_callback() || ! empty( $main_active_tab->get_sections() ) ) {
				// no sub-tab active, own callback or own fields: render it completely.
				call_user_func( $main_active_tab->get_callback() );
			} elseif ( ! empty( $main_active_tab->get_description() ) ) {
				// only a container for sub-tabs: show its description, but no empty form.
				echo wp_kses_post( $main_active_tab->get_description() );
			}
		}

		// show the active sub-tab.
		if ( $sub_active_tab instanceof Tab ) {
			call_user_func( $sub_active_tab->get_callback() );
		}
	}
}
