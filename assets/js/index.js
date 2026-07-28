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
  // get the container.
  const obj = document.getElementById( 'easy-settings-for-wordpress-settings' );

  // bail if config is not set.
  if ( ! obj || ! obj.dataset.config ) {
    return;
  }

  // parse the configuration and render.
  const config = JSON.parse( obj.dataset.config );
  createRoot( obj ).render( <SettingsPage config={ config } /> );
} );
