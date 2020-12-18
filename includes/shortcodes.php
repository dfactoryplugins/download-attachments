<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Metabox class.
 * 
 * @class Download_Attachments_Metabox
 */
class Download_Attachments_Shortcodes {

	/**
	 * Constructor class.
	 */
	public function __construct() {
		// actions
		add_action( 'init', array( $this, 'register_download_shortcodes' ) );
	}

	/**
	 * Register download attachments shortcodes.
	 */
	public function register_download_shortcodes() {
		add_shortcode( 'download-attachments', array( $this, 'download_attachments_shortcode' ) );
		add_shortcode( 'download-attachment', array( $this, 'download_attachment_shortcode' ) );
	}

	/**
	 * Handle download-attachments shortcode.
	 * 
	 * @param array $args
	 * @return mixed
	 */
	public function download_attachments_shortcode( $args ) {
		$defaults = array(
			'post_id'				 => 0,
			'container'				 => 'div',
			'container_class'		 => 'download-attachments',
			'container_id'			 => '',
			'style'					 => isset( Download_Attachments()->options['display_style'] ) ? esc_attr( Download_Attachments()->options['display_style'] ) : 'list',
			'link_before'			 => '',
			'link_after'			 => '',
			'display_index'			 => isset( Download_Attachments()->options['frontend_columns']['index'] ) ? (int) Download_Attachments()->options['frontend_columns']['index'] : 0,
			'display_user'			 => (int) Download_Attachments()->options['frontend_columns']['author'],
			'display_icon'			 => (int) Download_Attachments()->options['frontend_columns']['icon'],
			'display_count'			 => (int) Download_Attachments()->options['frontend_columns']['downloads'],
			'display_size'			 => (int) Download_Attachments()->options['frontend_columns']['size'],
			'display_date'			 => (int) Download_Attachments()->options['frontend_columns']['date'],
			'display_caption'		 => (int) Download_Attachments()->options['frontend_content']['caption'],
			'display_description'	 => (int) Download_Attachments()->options['frontend_content']['description'],
			'display_empty'			 => 0,
			'display_option_none'	 => __( 'No attachments to download', 'download-attachments' ),
			'use_desc_for_title'	 => 0,
			'exclude'				 => '',
			'include'				 => '',
			'title'					 => __( 'Attachments', 'download-attachments' ),
			'title_container'		 => 'h3',
			'title_class'			 => 'download-title',
			'orderby'				 => 'menu_order',
			'order'					 => 'asc',
			'echo'					 => 0
		);

		if ( ! isset( $args['title'] ) ) {
			$args['title'] = '';

			if ( Download_Attachments()->options['label'] !== '' )
				$args['title'] = Download_Attachments()->options['label'];
		}

		$args = shortcode_atts( $defaults, $args );

		// we have to force return in shortcodes
		$args['echo'] = 0;

		// reassign post id
		$post_id = (int) ( empty( $args['post_id'] ) ? get_the_ID() : $args['post_id'] );

		// unset from args
		unset( $args['post_id'] );

		return da_display_download_attachments( $post_id, $args );
	}

	/**
	 * Handle download-attachment shortcode.
	 */
	public function download_attachment_shortcode( $args ) {
		$defaults = array(
			'attachment_id'	 => 0, // deprecated
			'id'			 => 0,
			'title'			 => '',
			'class'			 => ''
		);

		$args = shortcode_atts( $defaults, $args );

		// deprecated attachment_id parameter support
		$args['id'] = ! empty( $args['attachment_id'] ) ? (int) $args['attachment_id'] : (int) $args['id'];

		$atts = array();

		if ( ! empty( $args['title'] ) )
			$atts['title'] = $args['title'];

		if ( ! empty( $args['class'] ) )
			$atts['class'] = $args['class'];

		if ( Download_Attachments()->options['download_method'] === 'redirect' )
			$atts['target'] = Download_Attachments()->options['link_target'];

		return da_download_attachment_link( (int) $args['id'], false, $atts );
	}
}

new Download_Attachments_Shortcodes();
