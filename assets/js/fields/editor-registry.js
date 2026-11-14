/**
 * Central mapping from a field descriptor to its custom Edit component.
 *
 * Used both by mapFields() for the top-level field list and by the nested
 * renderers (MultiField, FieldTable) so a given field type produces the same
 * editor wherever it appears.
 *
 * Types that rely on the DataViews-native edit control (plain text, textarea,
 * select, radio, number, boolean, nested password) return null here: at the top level
 * the DataForm renders them itself; nested, InnerFieldControl renders an
 * equivalent control.
 */
import { createButtonEdit } from './button';
import { createCardsEdit } from './cards';
import { createCheckboxListEdit } from './checkbox-list';
import { createColorEdit } from './color';
import { createDateTimeEdit } from './datetime';
import { createDisplayEdit } from './display';
import { createFieldTableEdit } from './fieldtable';
import { createMediaFieldEdit } from './media';
import { createMultiFieldEdit } from './multifield';
import { createMultiSelectEdit } from './multiselect';
import { createPasswordEdit } from './password';
import { createPermalinkSlugEdit } from './permalink-slug';
import { createPostSelectEdit } from './post-select';
import { createRangeEdit } from './range';
import { createTableEdit } from './table';

/**
 * Cache of the resolved Edit components per descriptor object.
 *
 * Nested fields (MultiField, FieldTable) resolve their Edit component on every
 * render. Without this cache each render would create a new component type, so
 * React would remount the control (e.g. interrupting a drag on a range slider
 * or losing the focus of an input).
 *
 * @type {WeakMap<Object, Function|null>}
 */
const editComponentCache = new WeakMap();

/**
 * Resolve the custom Edit component for a field descriptor.
 *
 * The same descriptor object always gets the same component.
 *
 * @param {Object} descriptor The field descriptor.
 * @return {Function|null} The Edit component, or null to use the native control.
 */
export function getEditComponent( descriptor ) {
  if ( ! descriptor || 'object' !== typeof descriptor ) {
    return resolveEditComponent( descriptor );
  }

  if ( ! editComponentCache.has( descriptor ) ) {
    editComponentCache.set( descriptor, resolveEditComponent( descriptor ) );
  }

  return editComponentCache.get( descriptor );
}

/**
 * Create the custom Edit component for a field descriptor.
 *
 * @param {Object} descriptor The field descriptor.
 * @return {Function|null} The Edit component, or null to use the native control.
 */
function resolveEditComponent( descriptor ) {
  switch ( descriptor?.type ) {
    case 'esfw-button':
      return createButtonEdit( {
        buttonTitle: descriptor.button_title,
        buttonUrl: descriptor.button_url,
        buttonClasses: descriptor.button_classes,
        buttonData: descriptor.button_data,
      } );
    case 'esfw-cards':
      return createCardsEdit( {
        options: descriptor.options,
        columns: descriptor.columns,
      } );
    case 'esfw-checkboxes':
      return createCheckboxListEdit( descriptor.options );
    case 'esfw-color':
      return createColorEdit( { chooseLabel: descriptor.choose_label } );
    case 'esfw-datetime':
      return createDateTimeEdit( {
        inputType: descriptor.input_type,
        min: descriptor.min,
        max: descriptor.max,
        step: descriptor.step,
      } );
    case 'media':
      return createMediaFieldEdit( {
        multiple: descriptor.multiple,
        allowedTypes: descriptor.allowed_types,
        allowedExtensions: descriptor.allowed_extensions,
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
    case 'esfw-range':
      return createRangeEdit( {
        min: descriptor.min,
        max: descriptor.max,
        step: descriptor.step,
        minGap: descriptor.min_gap,
        precision: descriptor.precision,
        fallback: descriptor.fallback,
        minLabel: descriptor.min_label,
        maxLabel: descriptor.max_label,
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
    case 'esfw-password':
      return createPasswordEdit( {
        hasValue: !! descriptor.has_value,
        placeholder: descriptor.placeholder,
        storedHint: descriptor.stored_hint,
        deleteOption: !! descriptor.delete_option,
        deleteLabel: descriptor.delete_label,
        deleteHint: descriptor.delete_hint,
        deleteToken: descriptor.delete_token,
        showLabel: descriptor.show_label,
        hideLabel: descriptor.hide_label,
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
