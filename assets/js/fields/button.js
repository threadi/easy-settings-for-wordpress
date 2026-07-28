import { Button } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { doAction } from '@wordpress/hooks';

/**
 * Create the custom button field.
 *
 * @param {Object} config The button configuration.
 * @return {Function} The Edit component.
 */
export function createButtonEdit( { buttonTitle, buttonUrl, buttonClasses, buttonData } ) {
  return function ButtonEdit() {
    // convert data attributes into react props.
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

    return (
      <Button
        variant="primary"
        href={ buttonUrl }
        className={ ( buttonClasses ?? [] ).join( ' ' ) }
        { ...dataProps }
      >
        { buttonTitle }
      </Button>
    );
  };
}
