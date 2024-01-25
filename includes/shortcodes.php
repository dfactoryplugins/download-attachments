<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Shortcodes class.
 *
 * @class Download_Attachments_Shortcodes
 */
class Download_Attachments_Shortcodes {

	/**
	 * Constructor class.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'init', [ $this, 'register_download_shortcodes' ] );
	}

	/**
	 * Register download attachments shortcodes.
	 *
	 * @return void
	 */
	public function register_download_shortcodes() {
		add_shortcode( 'download-attachments', [ $this, 'download_attachments_shortcode' ] );
		add_shortcode( 'download-attachment', [ $this, 'download_attachment_shortcode' ] );
	}

	/**
	 * Handle download-attachments shortcode.
	 *
	 * @param array $args
	 * @return string
	 */
	public function download_attachments_shortcode( $atts ) {
		$defaults = [
			'post_id'				=> 0,
			'container'				=> 'div',
			'container_class'		=> 'download-attachments',
			'container_id'			=> '',
			'style'					=> isset( Download_Attachments()->options['display_style'] ) ? sanitize_key( Download_Attachments()->options['display_style'] ) : 'list',
			'link_before'			=> '',
			'link_after'			=> '',
			'display_index'			=> isset( Download_Attachments()->options['frontend_columns']['index'] ) ? (int) Download_Attachments()->options['frontend_columns']['index'] : 0,
			'display_user'			=> (int) Download_Attachments()->options['frontend_columns']['author'],
			'display_icon'			=> (int) Download_Attachments()->options['frontend_columns']['icon'],
			'display_count'			=> (int) Download_Attachments()->options['frontend_columns']['downloads'],
			'display_size'			=> (int) Download_Attachments()->options['frontend_columns']['size'],
			'display_date'			=> (int) Download_Attachments()->options['frontend_columns']['date'],
			'display_caption'		=> (int) Download_Attachments()->options['frontend_content']['caption'],
			'display_description'	=> (int) Download_Attachments()->options['frontend_content']['description'],
			'display_empty'			=> 0,
			'display_option_none'	=> __( 'No attachments to download', 'download-attachments' ),
			'use_desc_for_title'	=> 0,
			'exclude'				=> '',
			'include'				=> '',
			'title'					=> __( 'Attachments', 'download-attachments' ),
			'title_container'		=> 'h3',
			'title_class'			=> 'download-title',
			'orderby'				=> 'menu_order',
			'order'					=> 'asc',
			'echo'					=> 0
		];

		// set title
		if ( ! isset( $atts['title'] ) ) {
			$atts['title'] = Download_Attachments()->options['label'] !== '' ? Download_Attachments()->options['label'] : '';
		}

		$atts = shortcode_atts( $defaults, $atts );
		
		// sanitize atts
		$args = [
			'post_id'				=> (int) $atts['post_id'],
			'container'				=> sanitize_text_field( $atts['container'] ),
			'container_class'		=> sanitize_html_class( $atts['container_class'] ),
			'container_id'			=> sanitize_text_field( $atts['container_id'] ),
			'style'					=> sanitize_key( $atts['style'] ),
			'link_before'			=> wp_kses_post( $atts['link_before'] ),
			'link_after'			=> wp_kses_post( $atts['link_after'] ),
			'display_index'			=> (int) $atts['display_index'],
			'display_user'			=> (int) $atts['display_user'],
			'display_icon'			=> (int) $atts['display_icon'],
			'display_count'			=> (int) $atts['display_count'],
			'display_size'			=> (int) $atts['display_size'],
			'display_date'			=> (int) $atts['display_date'],
			'display_caption'		=> (int) $atts['display_caption'],
			'display_description'	=> (int) $atts['display_description'],
			'display_empty'			=> (int) $atts['display_empty'],
			'display_option_none'	=> (int) $atts['display_option_none'],
			'use_desc_for_title'	=> (int) $atts['use_desc_for_title'],
			'exclude'				=> $atts['exclude'],
			'include'				=> $atts['include'],
			'title'					=> sanitize_text_field( $atts['title'] ),
			'title_container'		=> sanitize_key( $atts['title_container'] ),
			'title_class'			=> sanitize_html_class( $atts['title_class'] ),
			'orderby'				=> sanitize_key( $atts['orderby'] ),
			'order'					=> sanitize_key( $atts['order'] ),
			'echo'					=> (int) $atts['echo'],
		];
		
		// exclude
		if ( is_array( $args['exclude'] ) ) {
			$args['exclude'] = array_map( 'absint', $args['exclude'] );
		} elseif ( is_numeric( $args['exclude'] ) ) {
			$args['exclude'] = (int) $args['exclude'];
		} elseif ( is_string( $args['exclude'] ) ) {
			$args['exclude'] = sanitize_text_field( $args['exclude'] );
		} else
			$args['exclude'] = '';
		
		// include
		if ( is_array( $args['include'] ) ) {
			$args['include'] = array_map( 'absint', $args['include'] );
		} elseif ( is_numeric( $args['include'] ) ) {
			$args['include'] = (int) $args['include'];
		} elseif ( is_string( $args['include'] ) ) {
			$args['include'] = sanitize_text_field( $args['include'] );
		} else
			$args['include'] = '';

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
	 *
	 * @param array $args
	 * @return string
	 */
	public function download_attachment_shortcode( $atts ) {
		$defaults = [
			'attachment_id'	=> 0, // deprecated
			'id'			=> 0,
			'title'			=> '',
			'class'			=> ''
		];

		$atts = shortcode_atts( $defaults, $atts );
		
		// deprecated attachment_id parameter support
		$id = (int) ( ! empty( $atts['attachment_id'] ) ? $atts['attachment_id'] : $atts['id'] );
		
		// sanitize args
		$args = [
			'attachment_id' => $id,
			'title'			=> sanitize_text_field( $atts['title'] ),
			'class'			=> sanitize_html_class( $atts['class'] )
		];

		if ( Download_Attachments()->options['download_method'] === 'redirect' )
			$args['target'] = sanitize_key( Download_Attachments()->options['link_target'] );

		return da_download_attachment_link( $id, false, $args );
	}
}

new Download_Attachments_Shortcodes();