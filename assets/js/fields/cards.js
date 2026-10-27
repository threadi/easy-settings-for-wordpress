import { BaseControl } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';

/**
 * Create the custom cards field.
 *
 * A choice of one option like a radio field, where each option is shown as
 * card with label, icon, badge and description. The markup is the same as in
 * the classic view (see Fields/Cards.php), so both views share the styles in
 * assets/style.css.
 *
 * The description of each card is HTML, which has been filtered with
 * wp_kses_post() in Setting::build_field_descriptor().
 *
 * @param {Object} props         The configuration.
 * @param {Array}  props.options The cards (value, label, description, icon, badge, disabled).
 * @param {number} props.columns The number of columns, 0 for automatic.
 * @return {Function} The Edit component.
 */
export function createCardsEdit( { options = [], columns = 0 } = {} ) {
  return function CardsEdit( { field, data, onChange } ) {
    const ref = useRef( null );
    const value = String( field.getValue( { item: data } ) ?? '' );
    const isReadOnly = !! field.readOnly;
    const name = String( field.id );

    // re-bind click handlers in case a link in a description opens a dialog.
    useEffect( () => {
      if ( ! ref.current ) {
        return;
      }
      document.body.dispatchEvent(
        new Event( 'easy-dialog-for-wordpress-reinit' )
      );
    }, [] );

    // attributes for the number of columns.
    const listProps = {};
    if ( columns > 0 ) {
      listProps[ 'data-columns' ] = columns;
      listProps.style = { '--esfw-cards-columns': columns };
    }

    return (
      <div className="esfw-cards-field" ref={ ref }>
        <fieldset
          className={ `esfw-cards esfw-cards-${ name }` }
          { ...listProps }
        >
          <legend className="esfw-cards__legend">
            <BaseControl.VisualLabel>{ field.label }</BaseControl.VisualLabel>
          </legend>
          { options.map( ( option ) => {
            const id = name + option.value;
            const descriptionId = id + '-description';
            const isSelected = option.value === value;
            const isDisabled = isReadOnly || !! option.disabled;

            let classes = 'esfw-card';
            if ( isSelected ) {
              classes += ' is-selected';
            }
            if ( option.disabled ) {
              classes += ' is-disabled';
            }

            return (
              <div className={ classes } key={ option.value }>
                <input
                  type="radio"
                  id={ id }
                  name={ name }
                  value={ option.value }
                  className="esfw-card__input"
                  checked={ isSelected }
                  disabled={ isDisabled }
                  aria-describedby={ option.description ? descriptionId : undefined }
                  onChange={ () => onChange( { [ field.id ]: option.value } ) }
                />
                { option.badge && (
                  <span className="esfw-card__badge">{ option.badge }</span>
                ) }
                <div className="esfw-card__header">
                  <label className="esfw-card__label" htmlFor={ id }>{ option.label }</label>
                  { option.icon && (
                    <img className="esfw-card__icon" src={ option.icon } alt="" />
                  ) }
                </div>
                { option.description && (
                  <div
                    className="esfw-card__description"
                    id={ descriptionId }
                    dangerouslySetInnerHTML={ { __html: option.description } }
                  />
                ) }
              </div>
            );
          } ) }
        </fieldset>
        { field.description && (
          <p className="components-base-control__help esfw-cards__help">{ field.description }</p>
        ) }
      </div>
    );
  };
}
