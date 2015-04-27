<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

$path = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
include_once($path[0] . 'wp-load.php');

da_download_attachment( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 );
?>