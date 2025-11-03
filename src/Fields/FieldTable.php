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
     * @var array<int,<array<int,array<int,Setting>>>
     */
    private array $settings = array();

    /**
     * List of columns.
     *
     * @var array<int,string>
     */
    private array $columns = array();

    /**
     * Number of rows.
     *
     * @var int
     */
    private int $rows = 0;

    /**
     * Return the HTML code to display this field.
     *
     * @param array $attr Attributes for this field.
     *
     * @return void
     */
    public function display( array $attr ): void {
        // bail if no attributes are set.
        if ( empty( $attr ) ) {
            return;
        }

        // show optional description for this checkbox.
        if ( ! empty( $this->get_description() ) ) {
            echo '<p>' . wp_kses_post( $this->get_description() ) . '</p>';
        }

        ?><table>
        <thead>
        <tr>
            <?php foreach ( $this->get_columns() as $column ) : ?>
                <th><?php echo esc_html( $column ); ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php
        for( $i = 0; $i < $this->get_rows(); $i++ ) {
            ?>
            <tr>
                <?php
                foreach ( $this->get_columns() as $c => $column ) {
                    ?><td><?php
                    foreach ( $this->get_settings( $i, $c ) as $setting ) {
                        // get the field.
                        $field = $setting->get_field();

                        // bail if the field is not an instance of Field_Base.
                        if( ! $field instanceof Field_Base ) {
                            continue;
                        }

                        // show the field in the new column.
                        $field->display( array(
                                'setting' => $setting,
                        ) );
                    }
                    ?></td><?php
                }
                ?>
            </tr>
            <?php
        }
        ?>
        </tbody>
        </table><?php
    }

    /**
     * Return the list of fields.
     *
     * @return array<int,Setting>
     */
    private function get_settings( int $row, int $column ): array {
        if( ! isset( $this->settings[ $row ][ $column ] ) ) {
            return array();
        }
        return $this->settings[ $row ][ $column ];
    }

    /**
     * Add a field to the table.
     *
     * @param Setting $setting The setting to add.
     * @param int $row The row to add the setting to.
     * @param int $column The column to add the setting to.
     *
     * @return void
     */
    public function add_setting( Setting $setting, int $row, int $column ): void {
        $this->settings[ $row ][ $column ][] = $setting;
    }

    /**
     * Set the columns for the table.
     *
     * @return array<int,string>
     */
    private function get_columns(): array {
        return $this->columns;
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
        $this->rows++;
    }

    /**
     * Return the number of rows.
     *
     * @return int
     */
    private function get_rows(): int {
        return $this->rows;
    }
}

