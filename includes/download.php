<?php
// load wp core
$path = explode( 'wp-content', __FILE__ );

if ( is_file( reset( $path ) . 'wp-load.php' ) ) {
	include_once( reset( $path ) . 'wp-load.php' );
} else {
	return;
}

if ( ! function_exists( 'Download_Attachments' ) )
	return;

// get options
$options = Download_Attachments()->options;

// check if encryption is enabled
$encryption_enabled = isset( $options['encrypt_urls'] ) && $options['encrypt_urls'] ? true : false;

// decrypt id if needed
if ( $encryption_enabled ) {
	$id = isset( $_GET['id'] ) ? da_decrypt_attachment_id( urldecode( esc_attr( $_GET['id'] ) ) ) : 0;
} else {
	$id = isset( $_GET['id'] ) ? esc_attr( $_GET['id'] ) : 0;
}

if ( ! $id )
	return;

// allow for id customization
$id = apply_filters( 'da_download_attachment_id', $id );

da_download_attachment( (int) $id );