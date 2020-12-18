( function( $ ) {

	// ready event
	$( function() {
		// handle reset settings
		$( document ).on( 'click', 'input#reset_da_general, input#reset_da_display, input#reset_da_admin', function() {
			return confirm( daArgs.resetToDefaults );
		} );

		// handle reset download counts
		$( document ).on( 'click', 'input#reset_da_downloads', function() {
			return confirm( daArgs.resetDownloadsToDefaults );
		} );

		// handle pretty URL change
		$( document ).on( 'change keyup', '#da_general_download_link input', function() {
			var value = $( this ).val();

			value = value.trim();
			value = value.replace( /[^a-z0-9\-_\s]/gi, '' );
			value = value.replace( /[\s]/gi, '-' );

			if ( value === '' )
				$( '#da_general_download_link code strong' ).html( 'download' );
			else
				$( '#da_general_download_link code strong' ).html( value );
		} );

		// handle pretty URLs
		$( document ).on( 'change', '#da-general-download-method-force, #da-general-download-method-redirect', function() {
			$( '#da_general_download_method_target' ).slideToggle( 'fast' );
		} );

		// handle redirect to file download method 
		$( document ).on( 'change', '#da-general-pretty-urls-no, #da-general-pretty-urls-yes', function() {
			$( '#da_general_download_link' ).slideToggle( 'fast' );
		} );
	} );

} )( jQuery );