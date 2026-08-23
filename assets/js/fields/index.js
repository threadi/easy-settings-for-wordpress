/**
 * Maps field definitions to their custom Edit components.
 */
import { createElement } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';

import { createIsVisible } from '../utils/visibility';
import { getEditComponent } from './editor-registry';

/**
 * Resolve the field list into DataView field definitions with custom editors.
 *
 * @param {Object} config The settings configuration.
 * @return {Array} The mapped field definitions.
 */
export function mapFields( config ) {
  // allow custom fields.
  const filteredFields = applyFilters(
    'esfw.settingsPage.fields',
    config.fields,
    config
  );

  // lookup for the visibility check.
  const fieldsById = Object.fromEntries(
    filteredFields.map( ( f ) => [ f.id, f ] )
  );

  return filteredFields.map( ( field ) => {
    const updatedField = { ...field };

    // add the visibility marker based on "depend".
    if ( field.depend && Object.keys( field.depend ).length > 0 ) {
      updatedField.isVisible = createIsVisible( field.depend, fieldsById );
    }

    if ( updatedField.description ) {
      updatedField.description = createElement( 'span', {
        dangerouslySetInnerHTML: { __html: updatedField.description },
      } );
    }

    // resolve a custom Edit component; native types (text, select, …) keep the
    // DataViews-native control and return null here.
    const Edit = getEditComponent( updatedField );
    if ( Edit ) {
      updatedField.Edit = Edit;
    }

    return updatedField;
  } );
}
