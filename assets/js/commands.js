/**
 * Search for settings in the command palette of WordPress.
 *
 * Each settings object, which enabled it via Settings::set_search_in_command_palette(), adds its configuration
 * (name and REST path) to window.esfwCommandLoaders.
 */
import { store as commandsStore } from '@wordpress/commands';
import { dispatch } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { cog } from '@wordpress/icons';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Create the hook for a command loader of the given configuration.
 *
 * @param {Object} config The configuration (name, path).
 * @return {Function} The hook.
 */
function createHook( config ) {
  return function useSettingsSearch( { search } ) {
    const [ records, setRecords ] = useState( [] );
    const [ isLoading, setIsLoading ] = useState( false );

    useEffect( () => {
      // bail without search.
      if ( ! search ) {
        setRecords( [] );
        return;
      }

      let cancelled = false;
      setIsLoading( true );
      apiFetch( { path: addQueryArgs( config.path, { search } ) } )
        .then( ( result ) => {
          if ( ! cancelled ) {
            setRecords( Array.isArray( result ) ? result : [] );
          }
        } )
        .catch( () => {
          if ( ! cancelled ) {
            setRecords( [] );
          }
        } )
        .finally( () => {
          if ( ! cancelled ) {
            setIsLoading( false );
          }
        } );

      return () => {
        cancelled = true;
      };
    }, [ search ] );

    const commands = useMemo(
      () =>
        records.map( ( record ) => ( {
          name: `${ config.name }/${ record.name }`,
          label: record.label,
          // the records are already filtered by the server: keep them visible for this search.
          searchLabel: `${ record.label } ${ search }`,
          icon: cog,
          category: 'view',
          callback: ( { close } ) => {
            close();
            document.location = record.url;
          },
        } ) ),
      [ records, search ]
    );

    return { commands, isLoading };
  };
}

// register a loader for each settings object (only once per name, also if several plugins load this script).
window.esfwCommandLoadersRegistered = window.esfwCommandLoadersRegistered ?? {};
( window.esfwCommandLoaders ?? [] ).forEach( ( config ) => {
  if ( ! config?.name || ! config?.path || window.esfwCommandLoadersRegistered[ config.name ] ) {
    return;
  }
  window.esfwCommandLoadersRegistered[ config.name ] = true;
  dispatch( commandsStore ).registerCommandLoader( {
    name: config.name,
    hook: createHook( config ),
  } );
} );
