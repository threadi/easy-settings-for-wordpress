/**
 * Create the custom multiselect field.
 *
 * @param {Array} options The available options.
 * @return {Function} The Edit component.
 */
import { Button, FormTokenField } from '@wordpress/components';

/**
 * @param {Array}  options
 * @param {Object} [config]
 * @param {boolean} [config.sortable]
 */
export function createMultiSelectEdit( options, { sortable = false } = {} ) {
  const labelByValue = Object.fromEntries( options.map( ( o ) => [ o.value, o.label ] ) );
  const valueByLabel = Object.fromEntries( options.map( ( o ) => [ o.label, o.value ] ) );

  return function MultiSelectEdit( { field, data, onChange } ) {
    const raw = field.getValue( { item: data } ) ?? [];
    const currentValues = Array.isArray( raw ) ? [ ...raw ] : Object.keys( raw );
    const currentLabels = currentValues.map( ( v ) => labelByValue[ v ] ?? v );

    const commit = ( values ) => {
      onChange( { [ field.id ]: values } );
    };

    const handleTokensChange = ( newLabels ) => {
      const values = Array.isArray( newLabels )
        ? newLabels
          .map( ( label ) => valueByLabel[ label ] ?? label )
          .filter( ( value ) => value !== '' )
        : [];
      commit( values );
    };

    const move = ( index, delta ) => {
      const next = index + delta;
      if ( next < 0 || next >= currentValues.length ) {
        return;
      }
      const values = [ ...currentValues ];
      const tmp = values[ index ];
      values[ index ] = values[ next ];
      values[ next ] = tmp;
      commit( values );
    };

    return (
      <div className={ sortable ? 'esfw-multiselect esfw-multiselect--sortable' : 'esfw-multiselect' }>
        <FormTokenField
          label={ field.label }
          value={ currentLabels }
          suggestions={ options.map( ( o ) => o.label ) }
          onChange={ handleTokensChange }
          help={ field?.description }
          __experimentalExpandOnFocus
          __next40pxDefaultSize
        />
        { sortable && currentValues.length > 1 && (
          <ul className="esfw-multiselect__order">
            { currentValues.map( ( value, index ) => (
              <li key={ value } className="esfw-multiselect__order-item">
                <span>{ labelByValue[ value ] ?? value }</span>
                <span className="esfw-multiselect__order-actions">
                  <Button
                    size="small"
                    variant="tertiary"
                    disabled={ index === 0 }
                    onClick={ () => move( index, -1 ) }
                    label="Move up"
                  >
                    ↑
                  </Button>
                  <Button
                    size="small"
                    variant="tertiary"
                    disabled={ index === currentValues.length - 1 }
                    onClick={ () => move( index, 1 ) }
                    label="Move down"
                  >
                    ↓
                  </Button>
                </span>
              </li>
            ) ) }
          </ul>
        ) }
      </div>
    );
  };
}
