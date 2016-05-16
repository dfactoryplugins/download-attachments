<?php
// load wp core
$path = explode( 'wp-content', __FILE__ );
include_once( reset( $path ) . 'wp-load.php' );

da_download_attachment( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 );