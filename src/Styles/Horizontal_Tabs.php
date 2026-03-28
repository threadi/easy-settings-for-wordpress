<?php
/**
 * File for an object to handle the output of settings with horizontal tabs.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Styles;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Helper;
use easySettingsForWordPress\Page;
use easySettingsForWordPress\Settings;
use easySettingsForWordPress\Styling_Base;
use easySettingsForWordPress\Tab;

/**
 * Object to hold single setting.
 */
class Horizontal_Tabs extends Styling_Base {
	/**
	 * The internal name of this styling.
	 *
	 * @var string
	 */
	protected string $name = 'horizontal_tabs';

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Add our styling.
	 *
	 * @return void
	 */
	public function add_styles(): void {
		// add backend CSS.
		wp_enqueue_style(
			$this->get_settings_obj()->get_slug() . '-' . $this->get_name() . '-settings',
			$this->get_settings_obj()->get_url() . 'assets/horizontal_tabs.css',
			array(),
			Helper::get_file_version( $this->get_settings_obj()->get_path() . 'assets/horizontal_tabs.css', $this->get_settings_obj() ),
		);
	}

	/**
	 * Show the navigation for this styling.
	 *
	 * @return void
	 */
	public function show_nav(): void {
		// bail on missing capabilities.
		if ( ! current_user_can( $this->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// set active main tab.
		$main_active_tab = false;

		// set active sub tab.
		$sub_active_tab = false;

		// get requested page.
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// bail if page is not called.
		if ( is_null( $page ) ) {
			return;
		}

		// get the requested page.
		$page_obj = $this->get_settings_obj()->get_page( $page );

		// bail if page could not be found.
		if ( ! $page_obj instanceof Page ) {
			return;
		}

		// get the main tab from request.
		$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// get sub tab from request.
		$current_sub_tab = filter_input( INPUT_GET, 'subtab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		// sort the tabs.
		add_filter( $this->get_settings_obj()->get_slug() . '_settings_tabs', array( $this->get_settings_obj(), 'sort' ), PHP_INT_MAX );

		?>
		<div class="wrap easy-settings-for-wordpress">
			<h1 class="wp-heading-inline"><?php echo esc_html( $this->get_settings_obj()->get_title() ); ?></h1>
			<nav class="nav-tab-wrapper">
				<?php
				// loop through the tabs.
				foreach ( $page_obj->get_tabs() as $tab ) {
					// ignore if tab should be a menu item.
					if ( $tab->is_show_in_menu() ) {
						continue;
					}

					// set additional classes.
					$css_classes = '';

					// check for the current tab.
					if ( $tab->is_current() ) {
						$main_active_tab = $tab;
						$css_classes    .= ' nav-tab-active';
					} elseif ( is_null( $current_tab ) && $tab === $page_obj->get_default_tab() ) {
						$main_active_tab = $tab;
						$css_classes    .= ' nav-tab-active';
					}

					if ( ! empty( $tab->get_tab_class() ) ) {
						$css_classes .= ' ' . $tab->get_tab_class();
					}

					// get URL for this tab.
					$url    = add_query_arg(
						array(
							'page' => $page,
							'tab'  => $tab->get_name(),
						),
						get_admin_url() . $this->get_settings_obj()->get_menu_parent_slug()
					);
					$target = $tab->get_url_target();
					if ( ! empty( $tab->get_url() ) ) {
						$url = $tab->get_url();
					}

					// output a non-linked tab.
					if ( $tab->is_not_linked() ) {
						// output.
						?>
						<span class="nav-tab<?php echo esc_attr( $css_classes ); ?>"><?php echo wp_kses_post( $tab->get_title() ); ?></span>
						<?php
						continue;
					}

					// output.
					?>
					<a href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>" class="nav-tab<?php echo esc_attr( $css_classes ); ?>"><?php echo esc_html( $tab->get_title() ); ?></a>
					<?php
				}
				?>
			</nav>

			<?php
			// show sub-tabs of the active tab as breadcrumb-like sub-navigation.
			$sub_tabs = $main_active_tab ? $main_active_tab->get_tabs() : array();
			if ( ! empty( $sub_tabs ) ) {
				?>
				<nav class="nav-subtab-wrapper"><ul>
						<?php
						foreach ( $sub_tabs as $tab ) {
							// get URL for this tab.
							$url    = add_query_arg(
								array(
									'page'   => $page,
									'tab'    => $main_active_tab->get_name(),
									'subtab' => $tab->get_name(),
								),
								get_admin_url() . $this->get_settings_obj()->get_menu_parent_slug()
							);
							$target = $tab->get_url_target();
							if ( ! empty( $tab->get_url() ) ) {
								$url = $tab->get_url();
							}

							// collect classes.
							$css_classes = '';

							// check for the current tab.
							if ( $tab->is_current_sub_tab() ) {
								$sub_active_tab = $tab;
								$css_classes   .= 'active';
							} elseif ( is_null( $current_sub_tab ) && $tab === $main_active_tab->get_default_tab() ) {
								$sub_active_tab = $tab;
								$css_classes   .= 'active';
							}

							// output.
							?>
							<li><a href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>" class="<?php echo esc_attr( $css_classes ); ?>"><?php echo esc_html( $tab->get_title() ); ?></a></li>
							<?php
						}
						?>
					</ul></nav>
				<?php
			}
			?>

			<div class="tab-content">
				<?php
				if ( $main_active_tab instanceof Tab ) {
					call_user_func( $main_active_tab->get_callback() );
				}
				if ( $sub_active_tab instanceof Tab ) {
					call_user_func( $sub_active_tab->get_callback() );
				}
				?>
			</div>
		</div>
		<?php
	}

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
			'options-general.php' !== $this->settings_obj->get_menu_parent_slug() ? settings_errors() : '';
			settings_fields( $tab->get_name() );
			do_settings_sections( $tab->get_name() );
			$tab->is_save_hidden() ? '' : submit_button();
			?>
		</form>
		<?php
	}
}
