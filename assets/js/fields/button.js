import { BaseControl, Button } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';

/**
 * Create the custom button field.
 *
 * @param {Object} config The button configuration.
 * @return {Function} The Edit component.
 */
export function createButtonEdit( { buttonTitle, buttonUrl, buttonClasses, buttonData } ) {
  return function ButtonEdit( { field } ) {
    // convert data attributes into React props.
    const dataProps = Object.fromEntries(
      Object.entries( buttonData ?? {} ).map( ( [ key, value ] ) => [
        `data-${ key }`,
        value,
      ] )
    );

    // (re-)initialize the dialog bindings whenever this button mounts.
    useEffect( () => {
      document.body.dispatchEvent(
        new Event( 'easy-dialog-for-wordpress-reinit' )
      );
      doAction( 'esfw.settingsPage.button.mounted', {
        buttonTitle,
        buttonUrl,
        buttonClasses,
        buttonData,
      } );
    }, [] );

    const buttonId = `esfw-button-${ field?.id }`;

    // render the field label and description ourselves (like the other custom
    // fields do); the DataForm does not render them for custom Edit components.
    return (
      <BaseControl
        __nextHasNoMarginBottom
        id={ buttonId }
        label={ field?.label }
        help={ field?.description }
      >
        <div>
          <Button
            id={ buttonId }
            variant="primary"
            href={ buttonUrl }
            className={ ( buttonClasses ?? [] ).join( ' ' ) }
            { ...dataProps }
          >
            { buttonTitle }
          </Button>
        </div>
      </BaseControl>
    );
  };
}
