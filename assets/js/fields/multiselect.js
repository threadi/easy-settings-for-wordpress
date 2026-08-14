import { FormTokenField } from '@wordpress/components';

/**
 * Create the custom multiselect field.
 *
 * @param {Array} options The available options.
 * @return {Function} The Edit component.
 */
export function createMultiSelectEdit( options ) {
  const labelByValue = Object.fromEntries( options.map( ( o ) => [ o.value, o.label ] ) );
  const valueByLabel = Object.fromEntries( options.map( ( o ) => [ o.label, o.value ] ) );

  return function MultiSelectEdit( { field, data, onChange } ) {
    const raw = field.getValue( { item: data } ) ?? [];
    const currentValues = Array.isArray( raw ) ? raw : Object.keys( raw );
    const currentLabels = currentValues.map( ( v ) => labelByValue[ v ] ?? v );

    const handleChange = ( newLabels ) => {
      const values = Array.isArray( newLabels )
        ? newLabels
          .map( ( label ) => valueByLabel[ label ] ?? label )
          .filter( ( value ) => value !== '' )
        : [];

      onChange( {
        [ field.id ]: values,
      } );
    };

    return (
      <FormTokenField
        label={ field.label }
        value={ currentLabels }
        suggestions={ options.map( ( o ) => o.label ) }
        onChange={ handleChange }
        help={ field?.description }
        __experimentalExpandOnFocus
        __next40pxDefaultSize
      />
    );
  };
}
