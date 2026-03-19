<?php
/**
 * This file contains a table view for the table field.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Tables;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Settings;
use WP_List_Table;

/**
 * Initialize the log viewer.
 */
class Table extends WP_List_Table {
	/**
	 * Hold the table data.
	 *
	 * @var array<string,mixed>
	 */
	private array $table_data = array();

	/**
	 * List of options.
	 *
	 * @var array<string,mixed>
	 */
	private array $table_options = array();

	/**
	 * The settings object.
	 *
	 * @var Settings
	 */
	private Settings $settings_obj;

	/**
	 * Set the setting object.
	 *
	 * @param Settings $settings_obj The settings object.
	 * @return void
	 */
	public function set_settings_obj( Settings $settings_obj ): void {
		$this->settings_obj = $settings_obj;
	}

	/**
	 * Return the table data array.
	 *
	 * @return array<string,mixed>
	 */
	private function get_table_data(): array {
		return $this->table_data;
	}

	/**
	 * Set the table data.
	 *
	 * @param array<string,mixed> $data The data to use.
	 *
	 * @return void
	 */
	public function set_table_data( array $data ): void {
		$this->table_data = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return array<string,string>
	 */
	public function get_columns(): array {
		// get the translations.
		$translations = $this->settings_obj->get_translations();

		return array(
			'options' => $translations['table_options'],
			'entry'   => $translations['table_entry'],
		);
	}

	/**
	 * Get the log-table for the table-view.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->get_table_data();

		$per_page     = 50;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}

	/**
	 * Define, which columns are hidden
	 *
	 * @return array<string,mixed>
	 */
	public function get_hidden_columns(): array {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array<string,mixed>
	 */
	public function get_sortable_columns(): array {
		return array( 'date' => array( 'date', false ) );
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  mixed  $item        Data for single column.
	 * @param  string $column_name - Current iterated column name.
	 *
	 * @return string
	 * @noinspection PhpMissingReturnTypeInspection
	 */
	public function column_default( $item, $column_name ) {
		return match ( $column_name ) {
			'options' => $this->get_item_options( $item ),
			'entry' => $item,
			default => '',
		};
	}

	/**
	 * Message to be displayed when we have no items.
	 */
	public function no_items(): void {
		// get the translations.
		$translations = $this->settings_obj->get_translations();

		// output.
		echo esc_html( $translations['table_no_entries'] );
	}

	/**
	 * Set options for each entry in the table.
	 *
	 * Format:
	 * array(
	 *     'url' => 'url_to_use',
	 *     'icon' => 'icon_to_use'
	 * )
	 *
	 * @param array<string,mixed> $options List of options.
	 *
	 * @return void
	 */
	public function set_table_options( array $options ): void {
		$this->table_options = $options;
	}

	/**
	 * Show options on entry depending on its entities.
	 *
	 * @param string $item The item.
	 *
	 * @return string
	 */
	private function get_item_options( string $item ): string {
		// collect the output.
		$output = '';

		// loop through the options and add each for the current item.
		foreach ( $this->table_options as $option ) {
			$url = add_query_arg(
				array(
					'item' => $item,
				),
				$option['url']
			);

			// add to the output.
			$output .= '<a href="' . esc_url( $url ) . '">' . wp_kses_post( $option['icon'] ) . '</a>';
		}

		// return resulting list of options.
		return $output;
	}

	/**
	 * Hide any nav elements and nonces.
	 *
	 * @param string $which The necessary parameter.
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ): void {}

	/**
	 * Override columns to hide footer of table.
	 *
	 * @param bool $with_id The necessary parameter.
	 *
	 * @return void
	 */
	public function print_column_headers( $with_id = true ) {
		if ( $with_id ) {
			parent::print_column_headers( $with_id );
		}
	}
}
