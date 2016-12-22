<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Media class.
 * 
 * @class Download_Attachments_Media
 */
class Download_Attachments_Media {

	/**
	 * Constructor class.
	 */
	public function __construct() {
		// actions
		add_action( 'manage_media_custom_column', array( &$this, 'custom_media_column_content' ), 10, 2 );

		// filters
		add_filter( 'manage_media_columns', array( &$this, 'downloads_media_column_title' ) );
		add_filter( 'manage_upload_sortable_columns', array( &$this, 'register_sortable_custom_column' ) );
		add_filter( 'request', array( &$this, 'sort_custom_columns' ) );
	}

	/**
	 * Display attachments download count.
	 * 
	 * @param string $column
	 * @param id $id
	 */
	public function custom_media_column_content( $column, $id ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true && $column === 'downloads_count' )
			echo (int) get_post_meta( $id, '_da_downloads', true );
	}

	/**
	 * Add new custom column to Media Library.
	 * 
	 * @param array $columns
	 * @return array
	 */
	public function downloads_media_column_title( $columns ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true ) {
			$two_last = array_slice( $columns, -2, 2, true );

			foreach ( $two_last as $column => $name ) {
				unset( $columns[$column] );
			}

			$columns['downloads_count'] = __( 'Downloads', 'download-attachments' );

			foreach ( $two_last as $column => $name ) {
				$columns[$column] = $name;
			}
		}

		return $columns;
	}

	/**
	 * Sort new custom column in Media Library.
	 * 
	 * @param array $vars
	 * @return array
	 */
	public function sort_custom_columns( $vars ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true && isset( $vars['orderby'] ) && $vars['orderby'] === 'downloads' )
			$vars = array_merge(
			$vars, array(
				'meta_key'	 => '_da_downloads',
				'orderby'	 => 'meta_value_num'
			)
			);

		return $vars;
	}

	/**
	 * Register sortable custom column in Media Library.
	 * 
	 * @param array $columns
	 * @return array
	 */
	public function register_sortable_custom_column( $columns ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true )
			$columns['downloads_count'] = 'downloads';

		return $columns;
	}

}

new Download_Attachments_Media();
