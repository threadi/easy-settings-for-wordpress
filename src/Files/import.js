/**
 * Import given settings file via AJAX.
 */
function settings_import_file() {
  let file = jQuery('#import_settings_file')[0].files[0];
  if( undefined === file ) {
    let dialog_config = {
      detail: {
        title: settingsImportJsVars.title_settings_import_file_missing,
        texts: [
          '<p>' + settingsImportJsVars.text_settings_import_file_missing + '</p>'
        ],
        buttons: [
          {
            'action': 'closeDialog();',
            'variant': 'primary',
            'text': settingsImportJsVars.lbl_ok
          }
        ]
      }
    }
    esefw_settings_create_dialog( dialog_config );
    return;
  }

  let request = new FormData();
  request.append( 'file', file);
  request.append( 'action', 'settings_import_file' );
  request.append( 'nonce', settingsImportJsVars.settings_import_file_nonce );

  jQuery.ajax({
    url: settingsImportJsVars.ajax_url,
    type: "POST",
    data: request,
    contentType: false,
    processData: false,
    success: function( dialog_config ){
      esefw_settings_create_dialog( dialog_config );
    },
  });
}

/**
 * Helper to create a new dialog with given config.
 *
 * @param config
 */
function esefw_settings_create_dialog( config ) {
	document.body.dispatchEvent(new CustomEvent("easy-dialog-for-wordpress", config));
}
