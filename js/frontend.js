( function ( $ ) {

    $( document ).ready( function () {
		daDynatableArgs = $.extend( daDynatableArgs, {
			readers: {
				file_size: function(cell, record) {
					var el = $( cell );

					record['size'] = parseInt( el.data( 'size' ) );

					return el.html();
				}
			}
		} );

		$( '.da-attachments-dynatable' ).dynatable( daDynatableArgs );
    } );

} )( jQuery );