/**
 * Create the display field (static text or a read-only value).
 *
 * @param {Object} config The display configuration.
 * @return {Function} The Edit component.
 */
export function createDisplayEdit( { isStatic, staticText } ) {
  return function DisplayEdit( { field, data } ) {
    const content = isStatic ? staticText : field.getValue( { item: data } );

    return (
      <div>
        { field.label && <label>{ field.label }: </label> }
        <div dangerouslySetInnerHTML={ { __html: content ?? '' } } />
      </div>
    );
  };
}
