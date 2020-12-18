( function( $ ) {

    var open_media_window = function() {
		// window params
		var window = wp.media( {
			frame: 'select',
			title: daArgs.selectTitle,
			multiple: false,
			filterable: true,
			button: {
				text: daArgs.buttonInsertLink
			}
		} );

		// select action
		window.on( 'select', function() {
			var selected_file = window.state().get( 'selection' ).first().toJSON();
			var title = selected_file.title != '' ? selected_file.title : selected_file.filename;

			wp.media.editor.insert( '[download-attachment id="' + selected_file.id + '" title="' + title + '"]' );
		} );

		// open window
		window.open();

		return false;
    };

    // tinymce button
    tinymce.create( 'tinymce.plugins.download_attachments', {
		init: function( ed, url ) {
			// register buttons
			ed.addButton( 'download_attachments', {
				title: daArgs.selectTitle,
				icon: 'icon dashicons-arrow-down-alt',
				onclick: function() {
					// opens window
					open_media_window();
				}
			} );
		},
		createControl: function( n, cm ) {
			return null;
		},
		getInfo: function() {
			return {
				longname: 'Download Attachments',
				author: 'Digital Factory',
				authorurl: 'https://dfactory.eu/',
				infourl: 'https://dfactory.eu/',
				version: tinymce.majorVersion + '.' + tinymce.minorVersion
			};
		}
    } );

    // initlalize button
    tinymce.PluginManager.add( 'download_attachments', tinymce.plugins.download_attachments );

} )( jQuery );