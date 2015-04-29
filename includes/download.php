<?php
// get the path
$path_raw = $_SERVER['SCRIPT_FILENAME'];

// sanitize path
$path_raw = str_replace( "\"", "", $path_raw );
$path_raw = str_replace( "`", "", $path_raw );
$path_raw = str_replace( "..", "", $path_raw );
$path_raw = str_replace( "./", "", $path_raw );
$path_raw = str_replace( ":", "", $path_raw );

$path = explode( 'wp-content', $path_raw );

// load wp core
include_once( $path[0] . 'wp-load.php' );

da_download_attachment( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 );