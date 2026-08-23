<?php
/**
 * This file holds an object to display multiple settings in a table.
 *
 * @package easy-settings-for-wordpress
 */

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Field_Base;
use easySettingsForWordPress\Setting;

/**
 * Object to display multiple settings in a table.
 */
class FieldTable extends Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'FieldTable';

	/**
	 * The field to display.
	 *
	 * @var array<int,array<int,array<int,Setting>>>
	 */
	private array $settings = array();

	/**
	 * List of columns.
	 *
	 * @var array<int,string>
	 */
	private array $columns = array();

	/**
	 * Amount of rows.
	 *
	 * @var int
	 */
	private int $rows = 0;

	/**
	 * Return the HTML code to display this field.
	 *
	 * @param array<string,mixed> $attr Attributes for this field.
	 *
	 * @return void
	 */
	public function display( array $attr ): void {
		// bail if no attributes are set.
		if ( empty( $attr ) ) {
			return;
		}

		// get the columns.
		$columns = $this->get_columns();

		// bail if no columns are set.
		if ( empty( $columns ) ) {
			return;
		}

		// show optional description for this checkbox.
		if ( ! empty( $this->get_description() ) ) {
			echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
		}

		// get the rows.
		$rows = $this->get_rows();

		?><table>
		<thead>
		<tr>
			<?php foreach ( $columns as $column ) : ?>
				<th><?php echo esc_html( $column ); ?></th>
			<?php endforeach; ?>
		</tr>
		</thead>
		<tbody>
		<?php
		for ( $i = 0; $i < $rows; $i++ ) {
			?>
			<tr>
				<?php
				foreach ( $columns as $c => $column ) {
					?>
					<td>
					<?php
					foreach ( $this->get_settings( $i, $c ) as $setting ) {
						// get the field.
						$field = $setting->get_field();

						// bail if the field is not an instance of "Field_Base".
						if ( ! $field instanceof Field_Base ) {
							continue;
						}

						// show the field in the new column.
						$field->display(
							array(
								'setting' => $setting,
							)
						);
					}
					?>
					</td>
					<?php
				}
				?>
			</tr>
			<?php
		}
		?>
		</tbody>
		</table>
		<?php
	}

	/**
	 * Return the list of fields.
	 *
	 * @param int $row The row.
	 * @param int $column The column.
	 *
	 * @return array<int,Setting>
	 */
	private function get_settings( int $row, int $column ): array {
		if ( ! isset( $this->settings[ $row ][ $column ] ) ) {
			return array();
		}
		return $this->settings[ $row ][ $column ];
	}

	/**
	 * Add a field to the table.
	 *
	 * @param Setting $setting The setting to add.
	 * @param int     $row The row to add the setting to.
	 * @param int     $column The column to add the setting to.
	 *
	 * @return void
	 */
	public function add_setting( Setting $setting, int $row, int $column ): void {
		// mark this setting as a field-table cell. Cells have no section of
		// their own; the DataView uses this marker to render them inside the
		// table (not as a standalone field) and the "simple" method uses it to
		// still register them for the REST API.
		$setting->add_custom_var( 'esfw_field_table_cell', true );

		$this->settings[ $row ][ $column ][] = $setting;
	}

	/**
	 * Return the columns for the table.
	 *
	 * @return array<int,string>
	 */
	public function get_columns(): array {
		return $this->columns;
	}

	/**
	 * Return the settings placed in a single cell.
	 *
	 * @param int $row The row.
	 * @param int $column The column.
	 *
	 * @return array<int,Setting>
	 */
	public function get_cell_settings( int $row, int $column ): array {
		return $this->get_settings( $row, $column );
	}

	/**
	 * Return all cell settings of this table as a flat list.
	 *
	 * @return array<int,Setting>
	 */
	public function get_cell_settings_flat(): array {
		$result = array();
		foreach ( $this->settings as $columns ) {
			foreach ( $columns as $cell_settings ) {
				foreach ( $cell_settings as $cell_setting ) {
					$result[] = $cell_setting;
				}
			}
		}
		return $result;
	}

	/**
	 * Return the amount of rows.
	 *
	 * @return int
	 */
	public function get_row_count(): int {
		return $this->rows;
	}

	/**
	 * Set the columns for the table.
	 *
	 * @param array<int,string> $columns List of columns.
	 *
	 * @return void
	 */
	public function set_columns( array $columns ): void {
		$this->columns = $columns;
	}

	/**
	 * Add a row to the table.
	 *
	 * @return void
	 */
	public function add_row(): void {
		++$this->rows;
	}

	/**
	 * Return the amount of rows.
	 *
	 * @return int
	 */
	private function get_rows(): int {
		return $this->rows;
	}

	/**
	 * The sanitize callback for this field.
	 *
	 * Hint: this field does not have own values. The values are saved on the field in this field table.
	 *
	 * @param mixed $value The value to save.
	 *
	 * @return string
	 */
	public function default_sanitize_callback( mixed $value ): string {
		return '';
	}
}

