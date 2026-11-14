<?php
/**
 * File for an object to search the settings.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use WP_REST_Request;

/**
 * Object to search the settings (opt-in via Settings::set_search()).
 *
 * The search index is not saved anywhere: it is built on demand from the pages, tabs, sections and settings, which
 * are registered in the settings object during the request. Each entry describes a setting or a tab with the texts,
 * which are searched (title, description, options, search terms, titles of its tab and section), and the URL to
 * show it.
 */
class Search {
	/**
	 * The settings object.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * Cache for the index during the request.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	private ?array $index = null;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings_obj The settings object.
	 */
	public function __construct( Settings $settings_obj ) {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Return the settings object.
	 *
	 * @return Settings
	 */
	private function get_settings_obj(): Settings {
		return $this->settings_obj;
	}

	/**
	 * Initialize the search.
	 *
	 * @return void
	 */
	public function init(): void {
		// add the endpoint for the search (used by the command palette).
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// add the command palette.
		add_action( 'admin_enqueue_scripts', array( $this, 'add_command_palette' ) );
	}

	/**
	 * Return the search index with all settings and tabs of all pages.
	 *
	 * Each entry contains:
	 * - type: "setting" or "tab" (or own types added via the hook)
	 * - name: the name of the setting or tab
	 * - title: the title
	 * - description: the description as plain text
	 * - options: the labels of the options as plain text (e.g. of a select)
	 * - keywords: the search terms
	 * - path: the titles of tab, sub-tab and section
	 * - page, tab, subtab, section: the names of the page, tab, sub-tab and section
	 * - url: the URL of the tab, which shows the entry
	 * - row: the CSS class of the row of the setting in the classic view
	 * - depends: the settings, on which the setting depends, with name, title and whether the condition is met
	 * - external: whether the URL should be opened in a new window (e.g. for an external documentation)
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_index(): array {
		// return the index, if it is already built in this request.
		if ( ! is_null( $this->index ) ) {
			return $this->index;
		}

		// collect the entries.
		$entries = array();
		foreach ( $this->get_settings_obj()->get_pages() as $page ) {
			foreach ( $page->get_tabs() as $tab ) {
				// add the tab itself.
				$this->add_tab_entries( $entries, $page, $tab, null );

				// add its sub-tabs.
				foreach ( $tab->get_tabs() as $sub_tab ) {
					$this->add_tab_entries( $entries, $page, $tab, $sub_tab );
				}
			}
		}

		$instance = $this->get_settings_obj();
		/**
		 * Filter the search index of the settings, e.g. to add entries for own pages.
		 *
		 * Each entry needs at least the keys type, name, title and url. See Search::get_index() for all keys.
		 *
		 * @since 3.10.0 Available since 3.10.0.
		 * @param array<int,array<string,mixed>> $entries  List of entries.
		 * @param Settings                       $instance The settings object.
		 */
		$entries = apply_filters( $instance->get_slug() . '_settings_search_index', $entries, $instance );

		// complete the entries with default values.
		$this->index = array();
		foreach ( $entries as $entry ) {
			// bail if entry is not usable.
			if ( ! is_array( $entry ) || empty( $entry['title'] ) || empty( $entry['url'] ) ) { // @phpstan-ignore function.alreadyNarrowedType
				continue;
			}

			$this->index[] = array_merge( $this->get_empty_entry(), $entry );
		}

		// return the index.
		return $this->index;
	}

	/**
	 * Reset the cached index, e.g. after settings have been added.
	 *
	 * @return void
	 */
	public function reset_index(): void {
		$this->index = null;
	}

	/**
	 * Return an entry with all keys and their default values.
	 *
	 * @return array<string,mixed>
	 */
	private function get_empty_entry(): array {
		return array(
			'type'        => 'setting',
			'name'        => '',
			'title'       => '',
			'description' => '',
			'options'     => '',
			'keywords'    => '',
			'path'        => array(),
			'page'        => '',
			'tab'         => '',
			'subtab'      => '',
			'section'     => '',
			'url'         => '',
			'row'         => '',
			'depends'     => array(),
			'external'    => false,
		);
	}

	/**
	 * Add the entries of the given tab (the tab itself and its settings) to the given list.
	 *
	 * @param array<int,array<string,mixed>> $entries  The list of entries.
	 * @param Page                           $page     The page.
	 * @param Tab                            $main_tab The main tab.
	 * @param Tab|null                       $sub_tab  The sub-tab or null for the main tab.
	 *
	 * @return void
	 */
	private function add_tab_entries( array &$entries, Page $page, Tab $main_tab, ?Tab $sub_tab ): void {
		// get the tab to use.
		$tab = $sub_tab ?? $main_tab;

		// bail if this tab could not be shown as part of the settings.
		if ( ! $this->is_tab_searchable( $tab ) || ( $sub_tab instanceof Tab && ! $this->is_tab_searchable( $main_tab ) ) ) {
			return;
		}

		// get the URL of the tab.
		$url = $this->get_tab_url( $page, $main_tab, $sub_tab );

		// get the titles of the tabs.
		$path = array( $this->get_plain_text( $main_tab->get_title() ) );
		if ( $sub_tab instanceof Tab ) {
			$path[] = $this->get_plain_text( $sub_tab->get_title() );
		}

		// add the tab.
		$entries[] = array(
			'type'        => 'tab',
			'name'        => $tab->get_name(),
			'title'       => $this->get_plain_text( $tab->get_title() ),
			'description' => $this->get_plain_text( $tab->get_description() ),
			'keywords'    => implode( ', ', $tab->get_search_keywords() ),
			'path'        => array_slice( $path, 0, -1 ),
			'page'        => $page->get_name(),
			'tab'         => $main_tab->get_name(),
			'subtab'      => $sub_tab instanceof Tab ? $sub_tab->get_name() : '',
			'url'         => $url,
		);

		// add its settings in the order of its sections.
		foreach ( $tab->get_sections() as $section ) {
			// bail if the section is hidden.
			if ( $section->is_hidden() ) {
				continue;
			}

			// get the title of the section.
			$section_title = $this->get_plain_text( $section->get_title() );

			// add each setting of this section.
			foreach ( $this->get_settings_of_section( $section ) as $setting ) {
				// get the field.
				$field = $setting->get_field();

				// bail if the setting has no field.
				if ( ! $field instanceof Field_Base ) {
					continue;
				}

				// get the title.
				$title = $this->get_plain_text( $field->get_title() );

				// bail if the setting has no title.
				if ( '' === $title ) {
					continue;
				}

				// add the entry.
				$entries[] = array(
					'type'        => 'setting',
					'name'        => $setting->get_name(),
					'title'       => $title,
					'description' => $this->get_plain_text( $field->get_description() . ' ' . $setting->get_help() ),
					'options'     => $this->get_options_text( $field ),
					'keywords'    => implode( ', ', $setting->get_search_keywords() ),
					'path'        => '' !== $section_title ? array_merge( $path, array( $section_title ) ) : $path,
					'page'        => $page->get_name(),
					'tab'         => $main_tab->get_name(),
					'subtab'      => $sub_tab instanceof Tab ? $sub_tab->get_name() : '',
					'section'     => $section->get_name(),
					'url'         => $url,
					'row'         => sanitize_title( $setting->get_name() ),
					'depends'     => $this->get_depends( $field ),
				);
			}
		}
	}

	/**
	 * Return whether the given tab could be found by the search.
	 *
	 * Tabs with an own URL (e.g. links to other pages), without title or shown as own menu entry are not part
	 * of the settings page.
	 *
	 * @param Tab $tab The tab.
	 *
	 * @return bool
	 */
	private function is_tab_searchable( Tab $tab ): bool {
		return '' !== $this->get_plain_text( $tab->get_title() ) && '' === $tab->get_url() && ! $tab->is_not_linked() && ! $tab->is_show_in_menu();
	}

	/**
	 * Return the settings of the given section in their order.
	 *
	 * @param Section $section The section.
	 *
	 * @return array<int,Setting>
	 */
	private function get_settings_of_section( Section $section ): array {
		$list = array();
		foreach ( $this->get_settings_obj()->get_settings() as $setting ) {
			if ( $setting->get_section() === $section ) {
				$list[] = $setting;
			}
		}
		return $list;
	}

	/**
	 * Return the URL of the given tab.
	 *
	 * @param Page     $page     The page.
	 * @param Tab      $main_tab The main tab.
	 * @param Tab|null $sub_tab  The sub-tab or null.
	 *
	 * @return string
	 */
	private function get_tab_url( Page $page, Tab $main_tab, ?Tab $sub_tab ): string {
		// collect the arguments.
		$args = array(
			'page' => $page->get_name(),
			'tab'  => $main_tab->get_name(),
		);
		if ( $sub_tab instanceof Tab ) {
			$args['subtab'] = $sub_tab->get_name();
		}

		// return the URL.
		return add_query_arg( $args, get_admin_url() . $this->get_settings_obj()->get_menu_parent_slug() );
	}

	/**
	 * Return the labels of the options of the given field as plain text.
	 *
	 * @param Field_Base $field The field.
	 *
	 * @return string
	 */
	private function get_options_text( Field_Base $field ): string {
		// bail if the field has no options.
		if ( ! method_exists( $field, 'get_options' ) ) {
			return '';
		}

		// get the options.
		$options = $field->get_options();

		// bail if options are not a list.
		if ( ! is_array( $options ) ) {
			return '';
		}

		// collect all texts of the options (options could be arrays with label and description).
		$texts = array();
		array_walk_recursive(
			$options,
			static function ( $value ) use ( &$texts ): void {
				if ( is_string( $value ) ) {
					$texts[] = $value;
				}
			}
		);

		// return them as plain text.
		return $this->get_plain_text( implode( ', ', $texts ) );
	}

	/**
	 * Return the settings, on which the given field depends, with their title and whether the condition is met.
	 *
	 * @param Field_Base $field The field.
	 *
	 * @return array<int,array{name: string, title: string, value: mixed, enabled: bool, met: bool, row: string}>
	 */
	private function get_depends( Field_Base $field ): array {
		$list = array();
		foreach ( $field->get_depend_as_array() as $name => $value ) {
			// get the setting.
			$setting = $this->get_settings_obj()->get_setting( (string) $name );

			// bail if setting does not exist.
			if ( ! $setting instanceof Setting ) {
				continue;
			}

			// get its field.
			$depend_field = $setting->get_field();

			// add the entry.
			$list[] = array(
				'name'    => $setting->get_name(),
				'title'   => $depend_field instanceof Field_Base ? $this->get_plain_text( $depend_field->get_title() ) : $setting->get_name(),
				'value'   => $value,
				'enabled' => in_array( $value, array( 1, '1', true ), true ),
				'met'     => (string) $setting->get_value() === (string) ( is_bool( $value ) ? (int) $value : $value ),
				'row'     => sanitize_title( $setting->get_name() ),
			);
		}
		return $list;
	}

	/**
	 * Return the given HTML as plain text in one line.
	 *
	 * @param string $html The HTML.
	 *
	 * @return string
	 */
	private function get_plain_text( string $html ): string {
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

	/**
	 * Return the entries of the index, which match the given search, sorted by relevance.
	 *
	 * All words of the search must be found. A word matches the beginning of a word of the entry, words with at least
	 * 4 characters also any part of it. Case and accents are ignored. Matches in the title count more than matches in
	 * the path (tab and section), which count more than matches in the description, the options and the search terms.
	 *
	 * @param string            $search The search.
	 * @param int               $limit  The maximum number of entries (0 for all).
	 * @param array<int,string> $types  Return only entries of these types (empty for all).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_results( string $search, int $limit = 0, array $types = array() ): array {
		// get the words of the search.
		$words = self::get_words( $search );

		// bail if search is empty.
		if ( empty( $words ) ) {
			return array();
		}

		// score each entry.
		$results = array();
		foreach ( $this->get_index() as $position => $entry ) {
			// bail if the type is not requested.
			if ( ! empty( $types ) && ! in_array( $entry['type'], $types, true ) ) {
				continue;
			}

			// get the score.
			$score = self::get_score( $entry, $words );

			// bail if the entry does not match.
			if ( 0 === $score ) {
				continue;
			}

			$entry['score']    = $score;
			$entry['position'] = $position;
			$results[]         = $entry;
		}

		// sort by score and keep the order of the settings for the same score.
		usort(
			$results,
			static function ( array $a, array $b ): int {
				return array( $b['score'], $a['position'] ) <=> array( $a['score'], $b['position'] );
			}
		);

		// limit the list.
		if ( $limit > 0 ) {
			$results = array_slice( $results, 0, $limit );
		}

		// return the results.
		return $results;
	}

	/**
	 * Return the score of the given entry for the given words of a search (0 if it does not match).
	 *
	 * Keep in sync with assets/js/utils/search.js.
	 *
	 * @param array<string,mixed> $entry The entry.
	 * @param array<int,string>   $words The normalized words of the search.
	 *
	 * @return int
	 */
	public static function get_score( array $entry, array $words ): int {
		// get the words of the parts of the entry with their weight.
		$parts = array(
			array( self::get_words( (string) $entry['title'] ), 3 ),
			array( self::get_words( implode( ' ', (array) $entry['path'] ) ), 2 ),
			array( self::get_words( $entry['description'] . ' ' . $entry['options'] . ' ' . $entry['keywords'] . ' ' . $entry['name'] ), 1 ),
		);

		// check each word of the search.
		$score = 0;
		foreach ( $words as $word ) {
			// get the best match for this word.
			$best = 0;
			foreach ( $parts as $part ) {
				if ( $part[1] > $best && self::is_word_matching( $word, $part[0] ) ) {
					$best = $part[1];
				}
			}

			// bail if a word could not be found.
			if ( 0 === $best ) {
				return 0;
			}

			$score += $best;
		}

		// return the score.
		return $score;
	}

	/**
	 * Return whether the given word of a search matches one of the given words of an entry.
	 *
	 * @param string            $word  The word of the search.
	 * @param array<int,string> $words The words of the entry.
	 *
	 * @return bool
	 */
	private static function is_word_matching( string $word, array $words ): bool {
		foreach ( $words as $entry_word ) {
			if ( str_starts_with( $entry_word, $word ) || ( mb_strlen( $word ) >= 4 && str_contains( $entry_word, $word ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return the normalized words of the given text: lowercase, without accents (e.g. "ä" becomes "a", "ß" becomes
	 * "ss"), split at all characters except letters, numbers and "_".
	 *
	 * Keep in sync with assets/js/utils/search.js.
	 *
	 * @param string $text The text.
	 *
	 * @return array<int,string>
	 */
	public static function get_words( string $text ): array {
		// remove the accents independent of the language (e.g. not "ae" for "ä" in German), but "ß" becomes "ss".
		$text = remove_accents( str_replace( 'ß', 'ss', $text ), 'en_US' );

		// split it.
		$words = preg_split( '/[^\p{L}\p{N}_]+/u', mb_strtolower( $text ), -1, PREG_SPLIT_NO_EMPTY );

		// return the words.
		return is_array( $words ) ? $words : array();
	}

	/**
	 * Return the configuration for the search in the views.
	 *
	 * @param string $page_slug The requested page.
	 *
	 * @return array<string,mixed>
	 */
	public function get_view_config( string $page_slug ): array {
		// get the translations.
		$translations = $this->get_settings_obj()->get_translations();

		// get the current tab and sub-tab.
		$current = $this->get_current_tabs( $page_slug );

		// return the configuration.
		return array(
			'enabled' => true,
			'index'   => $this->get_index(),
			'page'    => $page_slug,
			'tab'     => $current['tab'],
			'subtab'  => $current['subtab'],
			'labels'  => array(
				'placeholder'     => $translations['search_placeholder'],
				'label'           => $translations['search_label'],
				'no_results'      => $translations['search_no_results'],
				'no_results_tab'  => $translations['search_no_results_tab'],
				'other_results'   => $translations['search_other_results'],
				'results'         => $translations['search_results'],
				'depends'         => $translations['search_depends'],
				'depends_enabled' => $translations['search_depends_enabled'],
				'go_to'           => $translations['search_go_to'],
				'open_tab'        => $translations['search_open_tab'],
			),
		);
	}

	/**
	 * Return the names of the tab and the sub-tab, which are shown for the given page in this request.
	 *
	 * @param string $page_slug The page.
	 *
	 * @return array{tab: string, subtab: string}
	 */
	private function get_current_tabs( string $page_slug ): array {
		// get the page.
		$page = $this->get_settings_obj()->get_page( $page_slug );

		// bail if page does not exist.
		if ( ! $page instanceof Page ) {
			return array(
				'tab'    => '',
				'subtab' => '',
			);
		}

		// get the main tab from request or use the default tab.
		$tab_name = (string) filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$tab      = '' !== $tab_name ? $page->get_tab( $tab_name ) : $page->get_default_tab();
		if ( ! $tab instanceof Tab ) {
			$visible = $this->get_settings_obj()->get_visible_tabs( $page->get_tabs() );
			$tab     = $visible[0] ?? false;
		}

		// bail if no tab is used.
		if ( ! $tab instanceof Tab ) {
			return array(
				'tab'    => '',
				'subtab' => '',
			);
		}

		// get the sub-tab from request or use the default sub-tab.
		$sub_tab = false;
		if ( $tab->has_tabs() ) {
			$sub_tab_name = (string) filter_input( INPUT_GET, 'subtab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$sub_tab      = '' !== $sub_tab_name ? $tab->get_tab( $sub_tab_name ) : $tab->get_default_tab();
		}

		// return the names.
		return array(
			'tab'    => $tab->get_name(),
			'subtab' => $sub_tab instanceof Tab ? $sub_tab->get_name() : '',
		);
	}

	/**
	 * Register the REST route for the search.
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		// bail if the command palette is not enabled.
		if ( ! $this->get_settings_obj()->is_search_in_command_palette_enabled() ) {
			return;
		}

		register_rest_route(
			$this->get_settings_obj()->get_slug() . '/v1',
			'/settings-search',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					return current_user_can( $this->get_settings_obj()->get_capability() );
				},
				'callback'            => function ( WP_REST_Request $request ) {
					return rest_ensure_response( $this->get_command_palette_results( (string) $request->get_param( 'search' ) ) );
				},
			)
		);
	}

	/**
	 * Return the results for the command palette.
	 *
	 * @param string $search The search.
	 *
	 * @return array<int,array{name: string, label: string, url: string}>
	 */
	public function get_command_palette_results( string $search ): array {
		// get the translations.
		$translations = $this->get_settings_obj()->get_translations();

		// collect the results.
		$list = array();
		foreach ( $this->get_results( $search, 10 ) as $entry ) {
			// get the path as text.
			$path = implode( ' › ', (array) $entry['path'] );

			// add the entry.
			$list[] = array(
				'name'  => $entry['type'] . '-' . $entry['name'],
				'label' => sprintf( $translations['search_command_label'], $entry['title'] . ( '' !== $path ? ' (' . $path . ')' : '' ) ),
				'url'   => $this->get_result_url( $entry, $search ),
			);
		}

		// return the list.
		return $list;
	}

	/**
	 * Return the URL to show the given entry of the results for the given search.
	 *
	 * The URL contains the search, so the view shows the result, and the name of the setting to highlight it.
	 *
	 * @param array<string,mixed> $entry  The entry.
	 * @param string              $search The search.
	 *
	 * @return string
	 */
	public function get_result_url( array $entry, string $search ): string {
		// bail for other entries than settings.
		if ( 'setting' !== $entry['type'] ) {
			return (string) $entry['url'];
		}

		// return the URL with search and highlight.
		return add_query_arg(
			array(
				'esfw_search'    => rawurlencode( $search ),
				'esfw_highlight' => rawurlencode( (string) $entry['name'] ),
			),
			(string) $entry['url']
		);
	}

	/**
	 * Add the script for the command palette.
	 *
	 * @return void
	 */
	public function add_command_palette(): void {
		// bail if the command palette is not enabled.
		if ( ! $this->get_settings_obj()->is_search_in_command_palette_enabled() ) {
			return;
		}

		// bail if the user is not allowed to use the settings.
		if ( ! current_user_can( $this->get_settings_obj()->get_capability() ) ) {
			return;
		}

		// bail if the script is missing.
		$asset_file = $this->get_settings_obj()->get_path() . 'build/commands.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		// get the asset data.
		$script_asset = require $asset_file;

		// add the script.
		$handle = $this->get_settings_obj()->get_slug() . '-settings-commands';
		wp_enqueue_script(
			$handle,
			$this->get_settings_obj()->get_url() . 'build/commands.js',
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		// add the configuration for this settings object (several plugins could use the command palette).
		$config = array(
			'name' => $this->get_settings_obj()->get_slug() . '/settings-search',
			'path' => '/' . $this->get_settings_obj()->get_slug() . '/v1/settings-search',
		);
		wp_add_inline_script( $handle, 'window.esfwCommandLoaders = window.esfwCommandLoaders || []; window.esfwCommandLoaders.push(' . wp_json_encode( $config ) . ');', 'before' );
	}
}
