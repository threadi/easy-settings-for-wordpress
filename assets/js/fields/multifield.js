import { Button, TextControl } from '@wordpress/components';

/**
 * Create the multi-value text field.
 *
 * @return {Function} The Edit component.
 */
export function createMultiFieldEdit() {
  return function MultiFieldEdit( { field, data, onChange } ) {
    const raw = field.getValue( { item: data } ) ?? [ '' ];
    const currentValues = Array.isArray( raw ) ? raw : Object.values( raw );

    const updateEntry = ( index, value ) => {
      const newValues = [ ...currentValues ];
      newValues[ index ] = value;
      onChange( { [ field.id ]: newValues } );
    };

    const addEntry = () =>
      onChange( { [ field.id ]: [ ...currentValues, '' ] } );

    const removeEntry = ( index ) =>
      onChange( {
        [ field.id ]: currentValues.filter( ( _, i ) => i !== index ),
      } );

    return (
      <fieldset>
        <legend>{ field.label }</legend>
        { currentValues.map( ( value, index ) => (
          <div
            key={ index }
            style={ { display: 'flex', gap: '8px', marginBottom: '8px' } }
          >
            <TextControl
              value={ value }
              onChange={ ( newValue ) => updateEntry( index, newValue ) }
              __next40pxDefaultSize
              __nextHasNoMarginBottom
            />
            <Button
              variant="secondary"
              isDestructive
              onClick={ () => removeEntry( index ) }
            >
              &times;
            </Button>
          </div>
        ) ) }
        <Button variant="secondary" onClick={ addEntry }>
          +
        </Button>
      </fieldset>
    );
  };
}
