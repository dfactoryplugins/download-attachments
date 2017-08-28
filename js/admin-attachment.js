( function ( $ ) {

    $( document ).ready( function () {
		// attachment downloads input
		$( '#attachment-downloads .edit-attachment-downloads' ).click( function () {
			if ( $( '#attachment-downloads-input-container' ).is( ":hidden" ) ) {
				$( '#attachment-downloads-input-container' ).slideDown( 'fast' );
				$( this ).hide();
			}

			return false;
		} );

		// save attachment downloads
		$( '#attachment-downloads .save-attachment-downloads' ).click( function () {
			$( '#attachment-downloads-display strong' ).text();
			$( '#attachment-downloads-input-container' ).slideUp( 'fast' );
			$( '#attachment-downloads .edit-attachment-downloads' ).show();

			var downloads = parseInt( $( '#attachment-downloads-input' ).val() );

			// reassign value as integer
			$( '#attachment-downloads-input' ).val( downloads );
			$( '#attachment-downloads-display strong' ).text( downloads );

			return false;
		} );

		// cancel attachment downloads
		$( '#attachment-downloads .cancel-attachment-downloads' ).click( function () {
			$( '#attachment-downloads-display strong' ).text();
			$( '#attachment-downloads-input-container' ).slideUp( 'fast' );
			$( '#attachment-downloads .edit-attachment-downloads' ).show();

			// restore old value
			var downloads = parseInt( $( '#attachment-downloads-current' ).val() );

			$( '#attachment-downloads-display strong' ).text( downloads );
			$( '#attachment-downloads-input' ).val( downloads );

			return false;
		} );
    } );

} )( jQuery );