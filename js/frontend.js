( function( $ ) {

    // ready event
	$( function() {
		var after = '';
		var before = '';

		// per page element
		if ( daDataTablesArgs.inputs.perPagePlacement === 'after' )
			after += 'l';
		else
			before += 'l';

		// search element
		if ( daDataTablesArgs.inputs.searchPlacement === 'after' )
			after += 'f';
		else
			before += 'f';

		// entries counter element
		if ( daDataTablesArgs.inputs.recordCountPlacement === 'after' )
			after += 'i';
		else
			before += 'i';

		// pagination element
		if ( daDataTablesArgs.inputs.paginationLinkPlacement === 'after' )
			after += 'p';
		else
			before += 'p';

		// set pagination gap
		$.fn.DataTable.ext.pager.numbers_length = daDataTablesArgs.inputs.paginationGap.reduce( ( a, b ) => a + b, 0 ) + 3;

		// make table sortable by columns
		$( '.da-attachments-dynatable' ).DataTable( {
			'paging': daDataTablesArgs.features.paginate,
			'info': daDataTablesArgs.features.recordCount,
			'searching': daDataTablesArgs.features.search,
			'stateSave': daDataTablesArgs.features.pushState,
			'ordering': daDataTablesArgs.features.sort,
			'order': [],
			'pageLength': daDataTablesArgs.dataset.perPageDefault,
			'lengthChange': daDataTablesArgs.features.perPageSelect,
			'lengthMenu': daDataTablesArgs.dataset.perPageOptions,
			'columns': daDataTablesArgs.columnTypes,
			'dom': '<' + before + '<rt>' + after + '>',
			'pagingType': 'simple_numbers',
			'language': {
				'info': daDataTablesArgs.inputs.recordCountText,
				'lengthMenu': daDataTablesArgs.inputs.perPageText,
				'loadingRecords': daDataTablesArgs.inputs.processingText,
				'paginate': {
					'next': daDataTablesArgs.inputs.paginationNext,
					'previous': daDataTablesArgs.inputs.paginationPrev
				}
			}
		} );
		
		
    } );

} )( jQuery );