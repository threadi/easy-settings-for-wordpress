jQuery(document).ready(function($) {
  /**
   * Handle depending settings on the settings page.
   *
   * Get all fields that depend on another.
   * Hide fields where the dependents do not match.
   * Set handler on depending on fields to show or hide the dependent fields.
   *
   * Hint: hide the surrounding "tr"-element.
   */
  $( '.easy-settings-for-wordpress input[type="checkbox"], .easy-settings-for-wordpress input[type="radio"], .easy-settings-for-wordpress input[type="hidden"], .easy-settings-for-wordpress select' ).each( function () {
    let form_field = $( this );

    // check on load to hide some fields.
    $( '.easy-settings-for-wordpress [data-depends]' ).each( function () {
      let depending_field = $( this );
      $.each( $( this ).data( 'depends' ), function (i, v) {
        if (i === form_field.attr( 'name' )
            && (
                (form_field.attr( 'type' ) === 'checkbox' && !form_field.is( ':checked' ))
                || (form_field.attr( 'type' ) !== 'checkbox' && v.toString() !== form_field.val())
            )) {
          depending_field.closest( 'tr' ).addClass( 'hide' );
          depending_field.closest( 'tr' ).removeClass( 'show_with_animation' );
        }
      } );
    } );

    // add event-listener to changed depending fields.
    form_field.on( 'change', function () {
      $( '.easy-settings-for-wordpress [data-depends]' ).each( function () {
        let depending_field = $( this );
        $.each( $( this ).data( 'depends' ), function (i, v) {
          if (i === form_field.attr( 'name' )) {
            if (
                (form_field.attr( 'type' ) !== 'checkbox' && v.toString() === form_field.val())
                || (form_field.attr( 'type' ) === 'checkbox' && form_field.is( ':checked' ))
            ) {
              depending_field.closest( 'tr' ).removeClass( 'hide' );
              depending_field.closest( 'tr' ).addClass( 'show_with_animation' )
            } else {
              depending_field.closest( 'tr' ).addClass( 'hide' );
              depending_field.closest( 'tr' ).removeClass( 'show_with_animation' );
            }
          }
        } );
      } );
    } )
  } );

  // button handling in settings > permalink.
  jQuery('.available-structure-permalinkslug ul button').on( 'click', function(e) {
    e.preventDefault();

    // get target.
    let target = jQuery('#' + jQuery(this).data('target'));

    // get actual value.
    let value = target.val();

    // get placeholder of clicked button.
    let placeholder = jQuery(this).data('placeholder');

    // check if placeholder does not exist in value.
    if( -1 === value.indexOf(placeholder) ) {
      // create new value.
      if ("/" !== value.slice(-1)) {
        value = value + '/';
      }

      // set placeholder.
      value = value + placeholder;

      // set active class on button.
      jQuery(this).addClass('active');
    }
    else {
      // remove placeholder from value.
      value = value.replace('/' + placeholder, '');

      // remove active class on button.
      jQuery(this).removeClass('active');
    }

    // set placeholder.
    target.val(value);
  });

  /**
   * Image handling: on upload button click.
   */
  $('.esfw-settings-image-choose').on( 'click', function(e){
    e.preventDefault();
    let button = $(this),
        custom_uploader = wp.media({
          title: esfwJsVars.title_add_image,
          library : {
            type : 'image'
          },
          button: {
            text: esfwJsVars.button_add_image
          },
          multiple: false
        }).on('select', function() { // it also has "open" and "close" events
          let attachment = custom_uploader.state().get('selection').first().toJSON();
          button.html('<img src="' + attachment.url + '">').next().show().next().val(attachment.id);
        }).open();

  });

  /**
   * Image handling: on remove button click.
   */
  $('.esfw-settings-image-remove').on('click', function(e){
    e.preventDefault();
    let button = $(this);
    button.next().val('');
    button.hide().prev().html(esfwJsVars.lbl_upload_image);
  });

  /**
   * File handling: on upload button click to choose multiple file for setting.
   */
  $('.esfw-settings-files-choose').on('click', function (e) {
    e.preventDefault();
    let button = $(this),
        custom_uploader = wp.media({
          title: esfwJsVars.title_add_files,
          library: {
            type: 'application/pdf' // TODO variabel machen.
          },
          button: {
            text: esfwJsVars.button_add_files
          },
          multiple: true
        }).on('select', function (){
          let file_ids = custom_uploader.state().get('selection').map( function( attachment ) {
            attachment = attachment.toJSON();
            return attachment.id;
          });

          // create params array.
          let params = new FormData();
          params.append( button.data('setting'), file_ids );

          // send request to server to save this value.
          $.ajax(
              {
                type: "POST",
                url: esfwJsVars.rest_settings,
                dataType: 'json',
                data: params,
                processData: false,
                contentType: false,
                beforeSend: function (xhr) {
                  // set header for authentication.
                  xhr.setRequestHeader('X-WP-Nonce', esfwJsVars.rest_nonce);
                },
              }
          );
        }).open();
  });

  /**
   * File handling: remove file from setting via AJAX.
   */
  jQuery('.esfw-settings-files-choose-remove').on('click', function (e) {
    e.preventDefault();

    // get the button object.
    let button = $(this);

    // create params array.
    let params = new FormData();
    params.append( button.data('setting'), button.data('setting-value') );

    // send request to server to save this value.
    $.ajax(
        {
          type: "POST",
          url: esfwJsVars.rest_settings,
          dataType: 'json',
          data: params,
          processData: false,
          contentType: false,
          beforeSend: function (xhr) {
            // set header for authentication.
            xhr.setRequestHeader('X-WP-Nonce', esfwJsVars.rest_nonce);
          },
          success: function() {
            button.parents('li').remove();
          }
        }
    );
  });

    /**
     * Load list of pages.
     */
    $('.esfw-settings-post-type-search').on( 'keyup', function() {
        // get content of the field.
        let search_string = $(this).val();

        // get the target element for the search results.
        let target = $(this).parents('.esfw-settings-overlay').find('.esfw-settings-post-type-listing');

        // get the element where the chosen result should be set.
        let field = $("#" + $(this).data('field'));

        // get the open button.
        let open_button = $(this).parents('td').find('.esfw-settings-open-popup');

        // get the closing button.
        let closing_button = $(this).parents('.esfw-settings-overlay').find('.esfw-settings-overlay-closing');

        // get the chosen title.
        let chosen_title = $(this).data('chosen-title')

        // get the endpoint to use.
        let endpoint = $(this).data('endpoint');

        // get the limit to use.
        let limit = $(this).data('limit');

        // bail if some of the required settings are not available.
        if( search_string.length === 0 || target.length === 0 || field.length === 0 || closing_button.length === 0 || endpoint.length === 0 ) {
            return;
        }

        // set limit to default value of less or equal than 0.
        if( limit <= 0 ) {
            limit = 5;
        }

        // send request to server to save this value.
        $.ajax(
            {
                type: "GET",
                url: endpoint + '?search=' + search_string + '&per_page=' + limit,
                dataType: 'json',
                beforeSend: function (xhr) {
                    // set header for authentication.
                    xhr.setRequestHeader('X-WP-Nonce', esfwJsVars.rest_nonce);
                },
                success: function( data ) {
                    // clear the resulting list.
                    target.html('');

                    // add each result.
                    $.each( data, function( key, obj ) {
                        let element = $('<a>');
                        element.addClass( 'button button-secondary' );
                        element.html( obj.title.rendered );
                        element.attr( 'href', '#' );
                        element.data( 'object-id', obj.id );
                        element.data( 'object-url', obj.url );
                        element.data( 'object-title', obj.title.rendered );
                        element.appendTo( target );
                    });

                    // set event on the links to choose the object.
                    target.find('a').on( 'click', function( e ) {
                        e.preventDefault();

                        // set the value.
                        field.val( $(this).data('object-id') );

                        // get the actual chosen object output.
                        let chosen = $(this).parents('td').find('.esfw-settings-post-type-chosen');

                        // remove existing info.
                        chosen.remove();

                        // add new info.
                        let element = $('<p>');
                        element.addClass( 'esfw-settings-post-type-chosen' );
                        element.html( chosen_title + ': <a href="' + $(this).data( 'object-url' ) + '" target="_blank">' + $(this).data( 'object-title' ) + '</a>' );
                        open_button.after( element );

                        // trigger popup closing.
                        closing_button.trigger( 'click' );
                    });
                }
            }
        );
    })

    /**
     * Add dirty.js
     */
    $('.easy-settings-for-wordpress form').dirty({preventLeaving: true});

    /**
     * Drag & Drop for multiselect-fields.
     */
    $('.easy-settings-for-wordpress select.custom-sortable').each(function() {
        // get select-object.
        let select_obj = $(this);

        // Convert select to ul-list.
        let ul_list = $('<ul class="sortable">');
        ul_list.data( 'depends', select_obj.data( 'depends' ) );

        // Loop through the option-fields of the select-field.
        select_obj.find('option').each(function() {
            let field_id = select_obj.attr('id') + $(this).val();
            let input_field = $('<input>').attr({
                type: 'checkbox',
                id: field_id,
                name: select_obj.attr( 'name' ),
                value: $(this).val(),
                disabled: $(this).attr( 'disabled' )
            });
            if( $(this).is(':selected') ) {
                input_field.prop('checked', true);
            }
            input_field.data( 'depends', select_obj.data( 'depends' ) );
            let label = $('<label>').attr({
                for: field_id
            }).html( $(this).html() );
            let li_item = $('<li>').attr({
                title: esfwJsVars.label_sortable_title
            }).html(label).prepend(input_field);
            ul_list.append(li_item);
        });

        // add list to DOM.
        $(this).parent().append(ul_list);

        // set sortable.
        $('ul.sortable').sortable();

        // remove original select.
        select_obj.remove();
    });
});
