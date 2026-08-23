/**
 * Entry point for the settings dataview.
 *
 * Mounts the React app on the settings container and hands over the
 * configuration provided by PHP via the `data-config` attribute.
 */
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import { SettingsPage } from './components/settings-page';

/**
 * Initialize the settings page if "easy-settings-for-wordpress-settings" exist.
 */
domReady( () => {
  const obj = document.getElementById( 'easy-settings-for-wordpress-settings' );
  if ( ! obj ) {
    return;
  }

  let config = null;
  if ( typeof window.esfwSettingsConfig !== 'undefined' && window.esfwSettingsConfig ) {
    config = window.esfwSettingsConfig;
  } else if ( obj.dataset.config ) {
    try {
      config = JSON.parse( obj.dataset.config );
    } catch ( e ) {
      return;
    }
  }

  if ( ! config ) {
    return;
  }

  createRoot( obj ).render( <SettingsPage config={ config } /> );
} );
