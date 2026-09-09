/**
 * Central mapping from a field descriptor to its custom Edit component.
 *
 * Used both by mapFields() for the top-level field list and by the nested
 * renderers (MultiField, FieldTable) so a given field type produces the same
 * editor wherever it appears.
 *
 * Types that rely on the DataViews-native edit control (plain text, textarea,
 * select, radio, number, password, boolean) return null here: at the top level
 * the DataForm renders them itself; nested, InnerFieldControl renders an
 * equivalent control.
 */
import { createButtonEdit } from './button';
import { createCheckboxListEdit } from './checkbox-list';
import { createColorEdit } from './color';
import { createDisplayEdit } from './display';
import { createFieldTableEdit } from './fieldtable';
import { createMediaFieldEdit } from './media';
import { createMultiFieldEdit } from './multifield';
import { createMultiSelectEdit } from './multiselect';
import { createPermalinkSlugEdit } from './permalink-slug';
import { createPostSelectEdit } from './post-select';
import { createTableEdit } from './table';

/**
 * Resolve the custom Edit component for a field descriptor.
 *
 * @param {Object} descriptor The field descriptor.
 * @return {Function|null} The Edit component, or null to use the native control.
 */
export function getEditComponent( descriptor ) {
  switch ( descriptor.type ) {
    case 'esfw-button':
      return createButtonEdit( {
        buttonTitle: descriptor.button_title,
        buttonUrl: descriptor.button_url,
        buttonClasses: descriptor.button_classes,
        buttonData: descriptor.button_data,
      } );
    case 'esfw-checkboxes':
      return createCheckboxListEdit( descriptor.options );
    case 'esfw-color':
      return createColorEdit( descriptor.options );
    case 'media':
      return createMediaFieldEdit( {
        multiple: descriptor.multiple,
        allowedTypes: descriptor.allowed_types,
      } );
    case 'esfw-multiselect':
      return createMultiSelectEdit( descriptor.options, {
        sortable: !! descriptor.sortable,
      } );
    case 'esfw-permalink-slug':
      return createPermalinkSlugEdit( {
        options: descriptor.options,
        listTitle: descriptor.list_title,
      } );
    case 'esfw-display':
      return createDisplayEdit( {
        isStatic: descriptor.is_static,
        staticText: descriptor.text,
      } );
    case 'esfw-table':
      return createTableEdit( descriptor.content );
    case 'esfw-multifield':
      return createMultiFieldEdit( descriptor.field, descriptor.quantity );
    case 'esfw-field-table':
      return createFieldTableEdit( {
        columns: descriptor.columns,
        rows: descriptor.rows,
      } );
    case 'esfw-post-select':
      return createPostSelectEdit( {
        endpoint: descriptor.endpoint,
        limit: descriptor.limit,
        placeholder: descriptor.placeholder,
      } );
    default:
      return null;
  }
}
