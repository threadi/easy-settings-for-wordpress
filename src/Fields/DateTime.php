<?php
/**
 * This file holds an object for a single date and time field.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Date_Time_Field_Base;

/**
 * Object to handle a date and time field for single setting.
 *
 * The value is stored as string in the format "Y-m-d H:i".
 */
class DateTime extends Date_Time_Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'DateTime';

	/**
	 * The type of the HTML input field.
	 *
	 * @var string
	 */
	protected string $input_type = 'datetime-local';

	/**
	 * Whether the value contains a date.
	 *
	 * @var bool
	 */
	protected bool $has_date = true;

	/**
	 * Whether the value contains a time.
	 *
	 * @var bool
	 */
	protected bool $has_time = true;
}
