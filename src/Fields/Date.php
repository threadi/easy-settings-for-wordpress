<?php
/**
 * This file holds an object for a single date field.
 *
 * @package easy-settings-for-wordpress
 */

declare(strict_types=1);

namespace easySettingsForWordPress\Fields;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use easySettingsForWordPress\Date_Time_Field_Base;

/**
 * Object to handle a date field for single setting.
 *
 * The value is stored as string in the format "Y-m-d".
 */
class Date extends Date_Time_Field_Base {
	/**
	 * The type name.
	 *
	 * @var string
	 */
	protected string $type_name = 'Date';

	/**
	 * The type of the HTML input field.
	 *
	 * @var string
	 */
	protected string $input_type = 'date';

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
	protected bool $has_time = false;
}
