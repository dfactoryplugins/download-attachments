( function ( $ ) {

    $( document ).ready( function () {

	$( document ).on( 'click', 'input#reset_da_general, input#reset_da_display, input#reset_da_admin', function () {
	    return confirm( daArgs.resetToDefaults );
	} );

	$( document ).on( 'click', 'input#reset_da_downloads', function () {
	    return confirm( daArgs.resetDownloadsToDefaults );
	} );

	$( document ).on( 'change keyup', '#da_general_download_link input', function () {
	    var value = $( this ).val();

	    value = $.trim( value );
	    value = value.replace( /[^a-z0-9\-_\s]/gi, '' );
	    value = value.replace( /[\s]/gi, '-' );

	    if ( value === '' ) {
		$( '#da_general_download_link code strong' ).html( 'download' );
	    } else {
		$( '#da_general_download_link code strong' ).html( value );
	    }
	} );

	$( document ).on( 'change', '#da-general-pretty-urls-no, #da-general-pretty-urls-yes', function () {
	    if ( $( this ).val() === 'no' ) {
		$( '#da_general_download_link' ).slideUp( 'fast' );
	    } else {
		$( '#da_general_download_link' ).slideDown( 'fast' );
	    }
	} )
	    ;
    } );

} )( jQuery );