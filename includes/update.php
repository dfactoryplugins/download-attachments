<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class Download_Attachments_Update {
	
	public function __construct() {
		// actions
		add_action( 'init', array( &$this, 'check_update' ) );
	}

	/**
	 *
	 */
	public function check_update() {
		if ( ! current_user_can( 'manage_options' ) )
			return;

		// get current database version
		$current_db_version = get_option( 'download_attachments_version', '1.0.0' );

		// new version?
		if ( version_compare( $current_db_version, Download_Attachments()->defaults['version'], '<' ) ) {
			// update plugin version
			update_option( 'download_attachments_version', Download_Attachments()->defaults['version'] );
		}
	}

}

new Download_Attachments_Update();