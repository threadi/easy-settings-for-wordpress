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
