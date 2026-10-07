import { TextControl } from '@wordpress/components';

/**
 * Convert a stored value into the format the HTML input expects.
 *
 * Date and time are stored separated by a space ("2026-12-24 12:00"), the
 * input of type "datetime-local" expects a "T" between them.
 *
 * @param {*} value The stored value.
 * @return {string} The value for the input.
 */
const toInputValue = ( value ) =>
  typeof value === 'string' ? value.replace( ' ', 'T' ) : '';

/**
 * Convert the value of the HTML input into the stored format.
 *
 * @param {*} value The value of the input.
 * @return {string} The value to store.
 */
const fromInputValue = ( value ) =>
  typeof value === 'string' ? value.replace( 'T', ' ' ) : '';

/**
 * Create the field to choose a date, a time or both of them.
 *
 * Uses the native picker of the browser, the same way the classic view does.
 *
 * @param {Object} config             The field configuration.
 * @param {string} config.inputType   The HTML input type ("date", "time" or "datetime-local").
 * @param {string} [config.min]       The min value in the stored format.
 * @param {string} [config.max]       The max value in the stored format.
 * @param {number} [config.step]      The step (seconds for times, days for dates), 0 for the browser default.
 * @return {Function} The Edit component.
 */
export function createDateTimeEdit( { inputType, min, max, step } ) {
  // only set the limits which are configured.
  const limits = {};
  if ( min ) {
    limits.min = toInputValue( min );
  }
  if ( max ) {
    limits.max = toInputValue( max );
  }
  if ( step > 0 ) {
    limits.step = step;
  }

  return function DateTimeEdit( { field, data, onChange } ) {
    const currentValue = field.getValue( { item: data } ) ?? '';

    return (
      <TextControl
        className="esfw-datetime"
        type={ inputType || 'date' }
        label={ field.label }
        help={ field.description }
        value={ toInputValue( currentValue ) }
        onChange={ ( newValue ) =>
          onChange( { [ field.id ]: fromInputValue( newValue ) } )
        }
        disabled={ !! field.readOnly }
        { ...limits }
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    );
  };
}
