( function( $ ) {

    // ready event
	$( function() {
		// make table sortable by columns
		$( '.da-attachments-dynatable' ).DataTable( {
			paging: daDataTablesArgs.features.paginate,
			info: daDataTablesArgs.features.recordCount,
			searching: daDataTablesArgs.features.search,
			stateSave: daDataTablesArgs.features.pushState,
			ordering: daDataTablesArgs.features.sort,
			order: [],
			pageLength: daDataTablesArgs.dataset.perPageDefault,
			lengthChange: daDataTablesArgs.features.perPageSelect,
			lengthMenu: daDataTablesArgs.dataset.perPageOptions,
			columns: daDataTablesArgs.columnTypes,
			pagingType: 'simple_numbers'
		} );
    } );

} )( jQuery );