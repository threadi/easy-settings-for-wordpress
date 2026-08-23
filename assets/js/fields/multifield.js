import { BaseControl, Button } from '@wordpress/components';

import { InnerFieldControl, emptyValueFor } from './inner-field';

/**
 * Create the multi-value field.
 *
 * Repeats one inner field so the user can collect a list of entries. The inner
 * field can be any supported type (text, number, select, multiselect, media,
 * post-select, …); its descriptor is provided by PHP.
 *
 * @param {Object} innerDescriptor The descriptor of the repeated inner field.
 * @param {number} [quantity]      The initial number of entries to show.
 * @return {Function} The Edit component.
 */
export function createMultiFieldEdit( innerDescriptor, quantity = 1 ) {
  return function MultiFieldEdit( { field, data, onChange } ) {
    // no (supported) inner field configured: nothing to render.
    if ( ! innerDescriptor ) {
      return (
        <fieldset>
          <legend>
            <BaseControl.VisualLabel>{ field.label }</BaseControl.VisualLabel>
          </legend>
          <p className="components-base-control__help">
            { field.description }
          </p>
        </fieldset>
      );
    }

    const raw = field.getValue( { item: data } ) ?? [];
    const stored = Array.isArray( raw ) ? raw : Object.values( raw );

    // ensure at least the requested quantity (min. one) of visible entries.
    const minEntries = Math.max( 1, parseInt( quantity, 10 ) || 1 );
    const currentValues =
      stored.length >= minEntries
        ? stored
        : [
            ...stored,
            ...Array.from(
              { length: minEntries - stored.length },
              () => emptyValueFor( innerDescriptor )
            ),
          ];

    const commit = ( values ) => onChange( { [ field.id ]: values } );

    const updateEntry = ( index, value ) => {
      const newValues = [ ...currentValues ];
      newValues[ index ] = value;
      commit( newValues );
    };

    const addEntry = () =>
      commit( [ ...currentValues, emptyValueFor( innerDescriptor ) ] );

    const removeEntry = ( index ) =>
      commit( currentValues.filter( ( _, i ) => i !== index ) );

    return (
      <fieldset>
        <legend>
          <BaseControl.VisualLabel>{ field.label }</BaseControl.VisualLabel>
        </legend>
        { currentValues.map( ( value, index ) => (
          <div
            key={ index }
            style={ {
              display: 'flex',
              gap: '8px',
              alignItems: 'flex-start',
              marginBottom: '8px',
            } }
          >
            <div style={ { flex: 1 } }>
              <InnerFieldControl
                descriptor={ innerDescriptor }
                value={ value }
                onChange={ ( newValue ) => updateEntry( index, newValue ) }
                label={ `${ field.label } #${ index + 1 }` }
                hideDescription
              />
            </div>
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
        { field.description && (
          <p className="components-base-control__help">{ field.description }</p>
        ) }
      </fieldset>
    );
  };
}
