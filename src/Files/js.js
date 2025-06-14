jQuery(document).ready(function($) {
  /**
   * Handle depending settings on settings page.
   *
   * Get all fields which depends from another.
   * Hide fields where the dependends does not match.
   * Set handler on depending fields to show or hide the dependend fields.
   *
   * Hint: hide the surrounding "tr"-element.
   */
  $( '.easy-settings-for-wordpress input[type="checkbox"], .easy-settings-for-wordpress input[type="hidden"], .easy-settings-for-wordpress select' ).each( function () {
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
});
