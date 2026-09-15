import { BaseControl, __experimentalVStack as VStack, CheckboxControl } from '@wordpress/components';

/**
 * Create the custom checkboxes field.
 *
 * @param {Array} options The checkbox options.
 * @return {Function} The Edit component.
 */
export function createCheckboxListEdit( options ) {
  return function CheckboxListEdit( { field, data, onChange } ) {
    const raw = field.getValue( { item: data } ) ?? {};
    const selected = Array.isArray( raw )
      ? raw.map( String )
      : Object.keys( raw ).filter( ( k ) => raw[ k ] );

    const toggleOption = ( optionValue, isChecked ) => {
      const next = isChecked
        ? [ ...selected, optionValue ]
        : selected.filter( ( v ) => v !== optionValue );
      const map = {};
      next.forEach( ( k ) => {
        map[ k ] = 1;
      } );
      onChange( { [ field.id ]: map } );
    };

    return (
      <fieldset>
        <VStack spacing={ 2 }>
          <legend><BaseControl.VisualLabel>{ field.label }</BaseControl.VisualLabel></legend>
          { options.map( ( option ) => (
            <CheckboxControl
              key={ option.value }
              label={ option.label }
              checked={ selected.includes( option.value ) }
              onChange={ ( isChecked ) => toggleOption( option.value, isChecked ) }
              disabled={ !! field.readOnly }
            />
          ) ) }
        </VStack>
      </fieldset>
    );
  };
}
