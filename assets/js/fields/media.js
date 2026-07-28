import { MediaEdit } from '@wordpress/fields';

/**
 * Create the custom media field.
 *
 * @param {Object} config The media configuration.
 * @return {Function} The Edit component.
 */
export function createMediaFieldEdit( { multiple, allowedTypes } ) {
  return function CustomMediaEdit( props ) {
    const { onChange } = props;

    // ensure a cleared selection sends an explicit empty value instead of
    // dropping out of the request (undefined is stripped by JSON.stringify).
    const handleChange = ( edits ) => {
      const empty = multiple ? [] : 0;
      const next = { ...edits };
      Object.keys( next ).forEach( ( key ) => {
        if ( next[ key ] === undefined || next[ key ] === null ) {
          next[ key ] = empty;
        }
      } );
      onChange( next );
    };

    return (
      <div className="esfw-image-choose">
        <MediaEdit
          { ...props }
          onChange={ handleChange }
          multiple={ multiple }
          allowedTypes={ allowedTypes ?? [ 'image' ] }
        />
      </div>
    );
  };
}
