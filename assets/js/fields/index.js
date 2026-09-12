/**
 * Maps field definitions to their custom Edit components.
 */
import { createElement } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { CheckboxControl, SelectControl, RadioControl } from '@wordpress/components';

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

    if ( field.depend && Object.keys( field.depend ).length > 0 ) {
      updatedField.isVisible = createIsVisible( field.depend, fieldsById );
    }

    if ( updatedField.description ) {
      updatedField.description = createElement( 'span', {
        dangerouslySetInnerHTML: { __html: updatedField.description },
      } );
    }

    const Edit = getEditComponent( updatedField );
    if ( Edit ) {
      const isReadOnly = !! updatedField.readOnly;

      if ( isReadOnly ) {
        delete updatedField.readOnly;
      }

      updatedField.Edit = ( props ) =>
        createElement( Edit, {
          ...props,
          field: { ...props.field, readOnly: isReadOnly },
        } );
    } else if ( updatedField.readOnly && updatedField.type === 'boolean' ) {
      const fieldLabel = updatedField.label;
      const fieldDescription = updatedField.description;
      const fieldId = updatedField.id;

      updatedField.render = ( { item } ) => {
        const checked = !! ( item?.[ fieldId ] );
        return createElement( CheckboxControl, {
          label: fieldLabel,
          help: fieldDescription,
          checked,
          disabled: true,
          onChange: () => {},
          __nextHasNoMarginBottom: true,
        } );
      };
    } else if (
      updatedField.readOnly &&
      Array.isArray( updatedField.elements ) &&
      updatedField.elements.length > 0
    ) {
      const fieldId = updatedField.id;
      const elements = updatedField.elements;
      const fieldLabel = updatedField.label;
      const fieldDescription = updatedField.description;
      const useRadio = updatedField.Edit === 'radio';

      updatedField.render = ( { item } ) => {
        const value = item?.[ fieldId ] ?? '';
        const options = elements.map( ( el ) => ( {
          label: el.label,
          value: el.value,
        } ) );

        if ( useRadio ) {
          return createElement( RadioControl, {
            help: fieldDescription,
            selected: value,
            options,
            onChange: () => {},
          } );
        }

        return createElement( SelectControl, {
          help: fieldDescription,
          value,
          options,
          onChange: () => {},
          disabled: true,
          __next40pxDefaultSize: true,
          __nextHasNoMarginBottom: true,
        } );
      };
    }

    return updatedField;
  } );
}
