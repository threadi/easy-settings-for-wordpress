/**
 * Maps field definitions to their custom Edit components.
 */
import { applyFilters } from '@wordpress/hooks';

import { createIsVisible } from '../utils/visibility';
import { createButtonEdit } from './button';
import { createCheckboxListEdit } from './checkbox-list';
import { createDisplayEdit } from './display';
import { createMediaFieldEdit } from './media';
import { createMultiFieldEdit } from './multifield';
import { createMultiSelectEdit } from './multiselect';
import { createPermalinkSlugEdit } from './permalink-slug';
import { createPostSelectEdit } from './post-select';
import { createTableEdit } from './table';

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

    switch ( updatedField.type ) {
      case 'esfw-button':
        return {
          ...updatedField,
          Edit: createButtonEdit( {
            buttonTitle: updatedField.button_title,
            buttonUrl: updatedField.button_url,
            buttonClasses: updatedField.button_classes,
            buttonData: updatedField.button_data,
          } ),
        };
      case 'esfw-checkboxes':
        return {
          ...updatedField,
          Edit: createCheckboxListEdit( updatedField.options ),
        };
      case 'media':
        return {
          ...updatedField,
          Edit: createMediaFieldEdit( {
            multiple: updatedField.multiple,
            allowedTypes: updatedField.allowed_types,
          } ),
        };
      case 'esfw-multiselect':
        return {
          ...updatedField,
          Edit: createMultiSelectEdit( updatedField.options ),
        };
      case 'esfw-permalink-slug':
        return {
          ...updatedField,
          Edit: createPermalinkSlugEdit( {
            options: updatedField.options,
            listTitle: updatedField.list_title,
          } ),
        };
      case 'esfw-display':
        return {
          ...updatedField,
          Edit: createDisplayEdit( {
            isStatic: updatedField.is_static,
            staticText: updatedField.text,
          } ),
        };
      case 'esfw-table':
        return {
          ...updatedField,
          Edit: createTableEdit(),
        };
      case 'esfw-multifield':
        return {
          ...updatedField,
          Edit: createMultiFieldEdit(),
        };
      case 'esfw-post-select':
        return {
          ...updatedField,
          Edit: createPostSelectEdit( {
            endpoint: updatedField.endpoint,
            limit: updatedField.limit,
            placeholder: updatedField.placeholder,
          } ),
        };
      default:
        return updatedField;
    }
  } );
}
