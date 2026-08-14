/**
 * Hook that loads and saves the plugin settings via the REST API.
 */
import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { applyFilters, doAction } from '@wordpress/hooks';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Load and persist the settings.
 *
 * @param {Object} props        The settings page props.
 * @param {Object} props.config The settings configuration.
 * @return {[Object, Function, Function]} Settings, setter and save handler.
 */
export const useSettings = ( props ) => {
  const [ settings, setSettings ] = useState( {} );

  const { createSuccessNotice, createErrorNotice } = useDispatch( noticesStore );

  // load the settings once on mount.
  useEffect( () => {
    apiFetch( { path: '/wp/v2/settings', method: 'OPTIONS' } ).then( ( schema ) => {
      const loadedFields = schema?.schema?.properties ?? {};

      // filter for the fields carrying our marker.
      const fields = Object.keys( loadedFields ).filter(
        ( key ) => loadedFields[ key ]?.[ props.config.slug ] === true
      );

      // bail if no fields are available.
      if ( fields.length === 0 ) {
        setSettings( {} );
        return;
      }

      // load the actual values of these fields.
      apiFetch( { path: '/wp/v2/settings' } ).then( ( wpSettings ) => {
        const values = {};
        fields.forEach( ( key ) => {
          if ( key in wpSettings ) {
            values[ key ] = wpSettings[ key ];
          }
        } );
        setSettings( values );
      } );
    } );
  }, [] );

  // save handler.
  const saveSettings = () => {
    const settingsToSave = applyFilters(
      'esfw.settingsPage.beforeSave',
      settings,
      props.config
    );

    apiFetch( {
      path: '/wp/v2/settings',
      method: 'POST',
      data: settingsToSave,
    } )
      .then( () => {
        createSuccessNotice( props.config.settings_saved, { type: 'snackbar' } );
        doAction( 'esfw.settingsPage.afterSave', settingsToSave, props.config );
      } )
      .catch( ( error ) => {
        createErrorNotice( props.config.settings_save_error, { type: 'snackbar' } );
        doAction( 'esfw.settingsPage.saveError', error, props.config );
      } );
  };

  return [ settings, setSettings, saveSettings ];
};
