import { DataForm } from '@wordpress/dataviews';
import domReady from '@wordpress/dom-ready';
import { createRoot, useState, useEffect } from '@wordpress/element';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';
import { Notices } from './notices';
import { useDispatch } from '@wordpress/data';
import {
  __experimentalHeading as Heading,
  Button,
  CheckboxControl
} from '@wordpress/components';

/**
 * Object to handle the settings read and save tasks.
 *
 * @returns {[*,*,saveSettings]}
 */
const useSettings = ( props ) => {
  // prepare the settings handler.
  const [ settings, setSettings ] = useState( {} )

  // prepare notice.
  const { createSuccessNotice } = useDispatch( noticesStore );

  // get the settings.
  useEffect( () => {
    // get only the field settings, not their actual values.
    apiFetch( { path: '/wp/v2/settings', method: 'OPTIONS' } ).then( ( schema ) => {
      const loaded_fields = schema?.schema?.properties ?? {};

      // filter for the fields with the given marker.
      const fields = Object.keys( loaded_fields ).filter(
        ( key ) => loaded_fields[ key ]?.[props.config.slug] === true
      );

      // bail if list of fields is empty.
      if ( fields.length === 0 ) {
        setSettings( {} );
        return;
      }

      // get the settings from this list.
      apiFetch( {
        path: `/wp/v2/settings?_fields=${ fields.join( ',' ) }`,
      } ).then( ( wpSettings ) => {
        setSettings( wpSettings );
      } );
    } );
  }, [] );

  // create the object, which saves the settings.
  const saveSettings = () => {
    apiFetch( {
      path: '/wp/v2/settings',
      method: 'POST',
      data: {
        unadorned_announcement_bar: settings,
      },
    } ).then( () => {
      createSuccessNotice(
        'Settings saved.'
      );
    } );
  };

  // return the prepared settings.
  return [ settings, setSettings, saveSettings ];
};
const SettingsTitle = () => {
  return (
    <Heading level={ 1 }>
      { 'Settings Title' }
    </Heading>
  );
};

const SaveButton = ( { onClick } ) => {
  return (
    <div>
      <Button variant="primary" onClick={ onClick } __next40pxDefaultSize>
        { 'Save' }
      </Button>
    </div>
  );
};

/**
 * Create the custom button.
 *
 * @param buttonTitle
 * @param buttonUrl
 * @returns {function({field: *, onChange: *, data: *}): *}
 */
function createButtonEdit( buttonTitle, buttonUrl ) {
  return function ButtonEdit() {
    return (
      <Button variant="primary" href={ buttonUrl }>
        { buttonTitle }
      </Button>
    );
  };
}

/**
 * Create our custom checkboxes field.
 *
 * @param options
 * @returns {function({field: *, data: *, onChange: *}): *}
 */
function createCheckboxListEdit( options ) {
  return function CheckboxListEdit( { field, data, onChange } ) {
    // aktueller Wert ist ein Array, z. B. [ 'email', 'push' ]
    const currentValue = field.getValue( { item: data } ) ?? [];

    const toggleOption = ( optionValue, isChecked ) => {
      const newValue = isChecked
        ? [ ...currentValue, optionValue ]
        : currentValue.filter( ( v ) => v !== optionValue );

      onChange( { [ field.id ]: newValue } );
    };

    return (
      <fieldset>
        <legend>{ field.label }</legend>
        { options.map( ( option ) => (
          <CheckboxControl
            key={ option.value }
            label={ option.label }
            checked={ currentValue.includes( option.value ) }
            onChange={ ( isChecked ) =>
              toggleOption( option.value, isChecked )
            }
          />
        ) ) }
      </fieldset>
    );
  };
}

/**
 * Define the settings page.
 *
 * @param props
 * @returns {JSX.Element}
 * @constructor
 */
const SettingsPage = ( props ) => {
  // get the settings handler.
  const [ settings, setSettings, saveSettings ] = useSettings( props );

  // get the configuration.
  let config = props.config;

  // map our custom fields.
  const fields = config.fields.map( ( field ) => {
    if ( field.type === 'esfw-button' ) {
      return {
        ...field,
        Edit: createButtonEdit( field.button_title, field.button_url ),
      };
    }
    if ( field.type === 'esfw-checkboxes' ) {
      return {
        ...field,
        Edit: createCheckboxListEdit( field.options ),
      };
    }
    return field;
  } );

  // define the fields.
  /*const fields = [
    {
      id: 'message',
      label: 'Message',
      type: 'text',
      Edit: 'textarea',
    },
    {
      id: 'display',
      label: 'Display',
      type: 'boolean',
      Edit: 'toggle',
    },
    {
      id: 'size',
      label: 'Font size',
      type: 'text',
      elements: [
        {
          value: 'small',
          label: 'Small',
        },
        {
          value: 'medium',
          label: 'Medium',
        },
        {
          value: 'large',
          label: 'Large',
        },
        {
          value: 'x-large',
          label: 'Extra Large',
        },
      ],
      Edit: 'toggleGroup',
    }
  ];

  // assign the fields to the form.
  const form = {
    fields: [
      {
        id: 'bar',
        label: 'Bar',
        children: [ 'message', 'display' ],
        layout: { type: 'card', withHeader: false },
      },
      {
        id: 'appearance',
        label: 'Appearance',
        children: [ 'size' ],
        layout: { type: 'card', isOpened: false },
      },
    ]
  };*/

  return (
    <>
      <SettingsTitle />
      <Notices />
      <DataForm
        data={ settings }
        fields={ fields }
        form={ config.form }
        onChange={ ( edits ) =>
          setSettings( ( current ) => ( {
            ...current,
            ...edits,
          } ) )
        }
      />
      <SaveButton onClick={ saveSettings } />
    </>
  );
};

/**
 * Initialize the dataviews on the element with the given ID and with the configuration.
 */
domReady( () => {
  // get the object.
  let obj = document.getElementById( 'easy-settings-for-wordpress-settings' );

  // bail if config is not set.
  if( ! obj || ! obj.dataset.config ) {
    return;
  }

  // get the configuration.
  let config = JSON.parse(obj.dataset.config);

  // render the dataviews.
  createRoot( obj ).render( <SettingsPage config={config} /> );
} );
