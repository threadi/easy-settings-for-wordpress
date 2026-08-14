/**
 * Build an isVisible() function for a field's "depend" configuration.
 *
 * @param {Object} depend     Mapping setting-id -> required value.
 * @param {Object} fieldsById Lookup of all fields by id.
 * @return {Function|undefined} The visibility predicate, or undefined.
 */
export function createIsVisible( depend, fieldsById ) {
  const entries = Object.entries( depend ?? {} );

  // no dependency configured -> always visible.
  if ( entries.length === 0 ) {
    return undefined;
  }

  return ( item ) =>
    entries.every( ( [ dependsOnId, expectedValue ] ) => {
      const targetField = fieldsById[ dependsOnId ];
      const actualValue = item?.[ dependsOnId ];

      // boolean/checkbox targets: visible whenever checked.
      if ( targetField?.type === 'boolean' ) {
        return !! actualValue;
      }

      // everything else: compare as strings.
      return String( actualValue ?? '' ) === String( expectedValue );
    } );
}

/**
 * Filter a list of field ids down to those currently visible.
 *
 * A hidden field must not be rendered at all: an empty DataForm wrapper still
 * counts as a flex item and would leave a gap behind. Evaluating visibility
 * here lets the caller skip those fields entirely.
 *
 * @param {Array}  fieldIds The field ids to check (in order).
 * @param {Array}  fields   All field definitions.
 * @param {Object} settings The current settings values.
 * @return {Array} The visible field ids.
 */
export function getVisibleFieldIds( fieldIds, fields, settings ) {
  const byId = Object.fromEntries( fields.map( ( f ) => [ f.id, f ] ) );

  return ( fieldIds ?? [] ).filter( ( id ) => {
    const field = byId[ id ];
    if ( ! field ) {
      return false;
    }
    return typeof field.isVisible !== 'function' || field.isVisible( settings );
  } );
}
