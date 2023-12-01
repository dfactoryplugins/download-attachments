( function( $ ) {

	var tableInitialized = false;
	var filesTable = null;

    // ready event
	$( function() {
		var daEditFrame = null;
		var daAddFrame = null;

		// modal window settings
		daAddFrame = wp.media( {
			frame: 'select',
			title: daArgsPost.addTitle,
			multiple: 'add',
			button: {
				text: daArgsPost.buttonAddNewFile
			},
			states: [
				new wp.media.controller.Library( daArgsPost.library == 0 ? {
					multiple: 'add',
					priority: 20,
					filterable: false,
					library: wp.media.query( { post_parent: wp.media.model.settings.post.id } )
				} : {
					multiple: 'add',
					priority: 20,
					filterable: 'all'
				} )
			]
		} );

		// open modal window
		daAddFrame.on( 'open', function() {
			var selection = daAddFrame.state().get( 'selection' );
			var id;
			var attachment;

			selection.reset( [] );

			$.each( $( '#da-files tbody tr[id^="att"]' ), function() {
				id = $( this ).attr( 'id' ).split( '-' )[1];

				if ( id !== '' ) {
					attachment = wp.media.attachment( id );
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				}
			} );
		} );

		// close modal window
		daAddFrame.on( 'close', function() {
			$( '#da-add-new-file input' ).prop( 'disabled', false );
		} );

		// after files were selected in modal window
		daAddFrame.on( 'select', function() {
			var library = daAddFrame.state().get( 'selection' );
			var ids = library.pluck( 'id' );
			var attachments = [];

			$.each( $( '#da-files tbody tr[id^="att"]' ), function( i ) {
				id = $( this ).attr( 'id' ).split( '-' )[1];

				if ( id !== '' )
					attachments[i] = parseInt( id );
			} );

			var attachments_add = $.grep( ids, function( i ) {
				return $.inArray( i, attachments ) < 0
			} );

			var attachments_remove = $.grep( attachments, function( i ) {
				return $.inArray( i, ids ) < 0
			} );

			if ( attachments_add.length > 0 ) {
				$( '#da-spinner' ).fadeIn( 300 );

				$.post( ajaxurl, {
					action: 'da-new-file',
					danonce: daArgsPost.addNonce,
					html: daAddFrame.link,
					post_id: wp.media.view.settings.post.id,
					attachments_ids: ( attachments_add.length > 0 ? attachments_add : [ 'empty' ] )
				} ).done( function( data ) {
					try {
						var json = JSON.parse( data );

						if ( json.status === 'OK' ) {
							var infoRow = $( '#da-files tbody tr#da-info' );

							// if no files
							if ( infoRow.length === 1 ) {
								infoRow.fadeOut( 300, function() {
									// remove info row
									$( this ).remove();

									// add new files
									$( '#da-files tbody' ).append( json.files );

									// display new files
									$( '#da-files tbody tr' ).fadeIn( 300 );

									// initialize datatables
									initDataTable();
								} );
							} else {
								// check all files
								for ( const row of json.files ) {
									// create node from html
									var node = $.parseHTML( row );

									// add it
									filesTable.row.add( node[0] );
								}

								filesTable.on( 'draw', function() {
									// display new added nodes
									$( '#da-files tbody tr' ).fadeIn( 300 );
								} );

								// redraw
								filesTable.draw();
							}

							$( '#da-infobox' ).html( '' ).fadeOut( 300 );
						} else {
							if ( json.info !== '' )
								$( '#da-infobox' ).html( json.info ).fadeIn( 300 );
							else
								$( '#da-infobox' ).html( '' ).fadeOut( 300 );
						}
					} catch ( e ) {
						$( '#da-infobox' ).html( daArgsPost.internalUnknownError ).fadeIn( 300 );
					}

					$( '#da-spinner' ).fadeOut( 300 );
				} ).fail( function() {
					$( '#da-infobox' ).html( daArgsPost.internalUnknownError ).fadeIn( 300 );
					$( '#da-spinner' ).fadeOut( 300 );
				} );
			}

			if ( attachments_remove.length > 0 ) {
				// remove deselected attachments
				$.each( attachments_remove, function( i, id ) {
					var node = $( 'tr#att-' + id );

					node.fadeOut( 300, function() {
						filesTable.row( node ).remove().draw();
					} );
				} );
			}
		} );

		// run modal window with attachments
		$( document ).on( 'click', '#da-add-new-file input', function() {
			if ( $( this ).is( ':disabled' ) )
				return false;

			$( this ).prop( 'disabled', true );

			daAddFrame.open();
		} );

		// edit file
		$( document ).on( 'click', '.da-edit-file', function() {
			if ( daArgsPost.attachmentLink === 'modal' ) {
				var fileID = parseInt( $( this ).closest( 'tr[id^="att"]' ).attr( 'id' ).split( '-' )[1] );
				var attachmentChanged = false;

				if ( daEditFrame !== null ) {
					daEditFrame.detach();
					daEditFrame.dispose();
					daEditFrame = null;
				}

				daEditFrame = wp.media( {
					frame: 'select',
					title: daArgsPost.editTitle,
					multiple: false,
					button: {
						text: daArgsPost.buttonEditFile
					},
					library: {
						post__in: fileID
					}
				} );

				daEditFrame.on( 'open', function() {
					var attachment = wp.media.attachment( fileID );

					daEditFrame.$el.closest( '.media-modal' ).addClass( 'da-edit-modal' );
					attachment.fetch();
					daEditFrame.state().get( 'selection' ).add( attachment );

					daEditFrame.$el.on( 'change', '.setting input, .setting textarea', function() {
						attachmentChanged = true;
					} );
				} );

				daEditFrame.on( 'close', function() {
					daEditFrame.$el.closest( '.media-modal' ).removeClass( 'da-edit-modal' );

					if ( attachmentChanged === true ) {
						var title = daEditFrame.$el.find( '.setting[data-setting="title"] input' ).val();
						var caption = daEditFrame.$el.find( '.setting[data-setting="caption"] textarea' ).val();
						var description = daEditFrame.$el.find( '.setting[data-setting="description"] textarea' ).val();

						$( 'tr#att-' + fileID + ' td.file-title p' ).fadeOut( 100, function() {
							$( this ).find( 'a' ).html( title );
							$( this ).find( 'span[class="description"]' ).html( description );
							$( this ).find( 'span[class="caption"]' ).html( caption );
							$( this ).fadeIn( 300 );
						} );
					}
				} );

				daEditFrame.open();
			}
		} );

		// remove file
		$( document ).on( 'click', '.da-remove-file', function() {
			if ( confirm( daArgsPost.deleteFile ) ) {
				var attId = $( this ).closest( 'tr[id^="att"]' ).attr( 'id' ).split( '-' )[1];
				var node = $( 'tr#att-' + parseInt( attId ) );

				node.fadeOut( 300, function() {
					filesTable.row( node ).remove().draw();
				} );
			}

			return false;
		} );

		// save the files list
		$( document ).on( 'click', '.da-save-files', function() {
			if ( $( this ).find( 'input' ).is( ':disabled' ) )
				return false;

			var attachments = [];
			var postID = parseInt( $( '#da-files' ).attr( 'rel' ) );

			// display spinner
			$( 'p.da-save-files input' ).prop( 'disabled', true );

			// deactivate buttons
			$( '#da-spinner' ).fadeIn( 300 );

			// get attachments data
			$.each( $( '#da-files tr[id^="att"]' ), function( i ) {
				attachments[i] = [ parseInt( $( this ).attr( 'id' ).split( '-' )[1] ), ( $( this ).find( 'td.file-exclude input.exclude-attachment' ).is( ':checked' ) === true ? 1 : 0 ) ];
			} );

			$.post( ajaxurl, {
				action: 'da-save-files',
				attachment_data: ( attachments.length > 0 ? attachments : [ 'empty' ] ),
				post_id: postID,
				danonce: daArgsPost.saveNonce
			} ).done( function( data ) {
				try {
					var json = JSON.parse( data );

					// everything went fine?
					if ( json.status === 'OK' )
						$( '#da-infobox' ).html( '' ).fadeOut( 300 );
					else if ( json.info !== '' )
						$( '#da-infobox' ).html( json.info ).fadeIn( 300 );
				} catch ( e ) {
					// display error
					$( '#da-infobox' ).html( daArgsPost.internalUnknownError ).fadeIn( 300 );
				}

				// hide spinner
				$( '#da-spinner' ).fadeOut( 300 );

				// activate buttons
				$( 'p.da-save-files input' ).prop( 'disabled', false );
			} ).fail( function() {
				// display error
				$( '#da-infobox' ).html( daArgsPost.internalUnknownError ).fadeIn( 300 );

				// hide spinner
				$( '#da-spinner' ).fadeOut( 300 );

				// activate buttons
				$( 'p.da-save-files input' ).prop( 'disabled', false );
			} );

			return false;
		} );

		// make attachments draggable
		$( '#da-files tbody' ).sortable( {
			axis: 'y',
			cursor: 'move',
			delay: 0,
			distance: 0,
			items: 'tr',
			forceHelperSize: false,
			forcePlaceholderSize: false,
			handle: '.file-drag',
			opacity: 0.6,
			revert: true,
			scroll: true,
			tolerance: 'pointer',
			helper: function( e, ui ) {
				var original = ui.children();
				var helper = ui.clone();

				helper.children().each( function( i ) {
					$( this ).width( original.eq( i ).width() );
				} );

				return helper;
			},
			start: function( e, ui ) {
				$( '#da-add-new-file input' ).prop( 'disabled', true );
				$( 'p.da-save-files input' ).prop( 'disabled', true );
			},
			stop: function( e, ui ) {
				$( '#da-add-new-file input' ).prop( 'disabled', false );
				$( 'p.da-save-files input' ).prop( 'disabled', false );
			}
		} );

		var infoRow = $( '#da-files tbody tr#da-info' );

		// any files?
		if ( infoRow.length !== 1 )
			initDataTable();
    } );

	function initDataTable() {
		if ( tableInitialized )
			return;

		// make table sortable by columns
		filesTable = $( '#da-files' ).DataTable( {
			paging: false,
			info: false,
			searching: false,
			ordering: true,
			order: [],
			columns: daArgsPost.columnTypes,
			language: {
				emptyTable: daArgsPost.noFiles
			}
		} );

		tableInitialized = true;
	}

} )( jQuery );
