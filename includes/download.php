<?php
// load wp core
$path = explode( 'wp-content', __FILE__ );

// set first element
reset( $path );

// load wp
if ( ! empty( $path[0] ) && is_file( $path[0] . 'wp-load.php' ) )
	include_once( $path[0] . 'wp-load.php' );
else
	return;

// is download attachments enabled?
if ( ! function_exists( 'Download_Attachments' ) )
	return;

// get options
$options = Download_Attachments()->options;

// decrypt id if needed
if ( isset( $options['encrypt_urls'] ) && $options['encrypt_urls'] )
	$id = isset( $_GET['id'] ) ? da_decrypt_attachment_id( preg_replace( '/[^A-Za-z0-9_,-]/', '', $_GET['id'] ) ) : 0;
else
	$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

if ( ! $id )
	return;

// allow for id customization
$id = (int) apply_filters( 'da_download_attachment_id', $id );

da_download_attachment( $id );