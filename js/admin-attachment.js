( function( $ ) {

    // ready event
	$( function() {
		var container = $( '#attachment-downloads-input-container' );
		var counter = $( '#attachment-downloads-display strong' );
		var editLink = $( '#attachment-downloads .edit-attachment-downloads' );
		var counterInput = $( '#attachment-downloads-input' );

		// attachment downloads input
		editLink.on( 'click', function() {
			if ( container.is( ":hidden" ) ) {
				container.slideDown( 'fast' );

				$( this ).hide();
			}

			return false;
		} );

		// save attachment downloads
		$( '#attachment-downloads .save-attachment-downloads' ).on( 'click', function() {
			// clear downloads
			counter.text();

			// hide container
			container.slideUp( 'fast' );

			// show edit link
			editLink.show();

			// get number of downloads
			var downloads = parseInt( counterInput.val() );

			// reassign value as integer
			counterInput.val( downloads );

			// update number of downloads
			counter.text( downloads );

			return false;
		} );

		// cancel attachment downloads
		$( '#attachment-downloads .cancel-attachment-downloads' ).on( 'click', function() {
			// clear downloads
			counter.text();

			// hide container
			container.slideUp( 'fast' );

			// show edit link
			editLink.show();

			// get number of downloads
			var downloads = parseInt( $( '#attachment-downloads-current' ).val() );

			// update number of downloads
			counter.text( downloads );

			// restore old value
			counterInput.val( downloads );

			return false;
		} );
    } );

} )( jQuery );