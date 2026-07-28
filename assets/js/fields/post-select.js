import apiFetch from '@wordpress/api-fetch';
import { ComboboxControl } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Create a search-and-select field for a single post type object.
 *
 * @param {{endpoint: string, limit: number, placeholder: string}} config The config.
 * @return {Function} The Edit component.
 */
export function createPostSelectEdit( { endpoint, limit, placeholder } ) {
  return function PostSelectEdit( { field, data, onChange } ) {
    const value = field.getValue( { item: data } ) ?? 0;
    const [ options, setOptions ] = useState( [] );
    const [ search, setSearch ] = useState( '' );

    // load the label of the already selected object once.
    useEffect( () => {
      const id = parseInt( value, 10 );
      if ( ! id || options.some( ( o ) => o.value === id ) ) {
        return;
      }
      apiFetch( { url: `${ endpoint }/${ id }` } )
        .then( ( post ) =>
          setOptions( ( prev ) => [
            { value: id, label: post?.title?.rendered ?? `#${ id }` },
            ...prev.filter( ( o ) => o.value !== id ),
          ] )
        )
        .catch( () => {} );
    }, [ value ] );

    // search while typing (debounced).
    useEffect( () => {
      if ( search.length === 0 ) {
        return;
      }
      const handle = setTimeout( () => {
        const perPage = limit > 0 ? limit : 5;
        apiFetch( {
          url: `${ endpoint }?search=${ encodeURIComponent( search ) }&per_page=${ perPage }`,
        } )
          .then( ( posts ) =>
            setOptions(
              ( posts ?? [] ).map( ( p ) => ( {
                value: p.id,
                label: p.title?.rendered ?? `#${ p.id }`,
              } ) )
            )
          )
          .catch( () => {} );
      }, 300 );

      return () => clearTimeout( handle );
    }, [ search ] );

    return (
      <ComboboxControl
        label={ field.label }
        placeholder={ placeholder }
        value={ value ? parseInt( value, 10 ) : null }
        options={ options }
        onFilterValueChange={ ( input ) => setSearch( input ) }
        onChange={ ( newValue ) =>
          onChange( { [ field.id ]: newValue ? parseInt( newValue, 10 ) : 0 } )
        }
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    );
  };
}
