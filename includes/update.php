<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

new Download_Attachments_Update( $download_attachments );

class Download_Attachments_Update {

	private $defaults;

	public function __construct( $download_attachments ) {
		// attributes
		$this->defaults = $download_attachments->get_defaults();

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
		if ( version_compare( $current_db_version, $this->defaults['version'], '<' ) ) {
			// update plugin version
			update_option( 'download_attachments_version', $this->defaults['version'] );
		}
	}

}

?>