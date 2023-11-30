<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Get download attachments data.
 *
 * @param int $post_id
 * @param array $args
 * @return array
 */
function da_get_download_attachments( $post_id = 0, $args = [] ) {
	$post_id = $post_id !== null ? ( (int) ( empty( $post_id ) ? get_the_ID() : $post_id ) ) : $post_id;

	$attachments = $attachments_meta = [];

	$defaults = [
		'include'			=> [],
		'exclude'			=> [],
		'orderby'			=> 'downloads',
		'order'				=> 'asc',
		'posts_per_page'	=> -1,
		'offset'			=> 0
	];

	$args = array_merge( $defaults, $args );

	// force args
	$args['post_type'] = 'attachment';
	$args['suppress_filters'] = false;
	$args['meta_key'] = '_da_downloads';
	$args['meta_query'] = [
		'relation' => 'OR',
		[
			'key'		=> '_da_downloads',
			'compare'	=> 'EXISTS'
		]
	];

	// include
	if ( is_array( $args['include'] ) && ! empty( $args['include'] ) ) {
		$ids = [];

		foreach ( $args['include'] as $id ) {
			$ids[] = (int) $id;
		}

		$args['include'] = $ids;
	} elseif ( is_numeric( $args['include'] ) )
		$args['include'] = [ (int) $args['include'] ];
	// shortcode
	elseif ( is_string( $args['include'] ) && ! empty( $args['include'] ) )
		$args['include'] = json_decode( '[' . $args['include'] . ']', true );
	else {
		$args['include'] = $defaults['include'];

		// exclude
		if ( is_array( $args['exclude'] ) && ! empty( $args['exclude'] ) ) {
			$ids = [];

			foreach ( $args['exclude'] as $id ) {
				$ids[] = (int) $id;
			}

			$args['exclude'] = $ids;
		} elseif ( is_numeric( $args['exclude'] ) )
			$args['exclude'] = [ (int) $args['exclude'] ];
		elseif ( is_string( $args['exclude'] ) && ! empty( $args['exclude'] ) )
			$args['exclude'] = json_decode( '[' . $args['exclude'] . ']', true );
		else
			$args['exclude'] = $defaults['exclude'];
	}

	// single post attachments
	if ( $post_id !== null ) {
		$include = $exclude = [];

		// force showposts arg
		$args['showposts'] = $args['posts_per_page'];

		// include / exlude specific attachments
		$attachment_meta = get_post_meta( $post_id, '_da_attachments', true );
		$menu_order = 0;

		if ( is_array( $attachment_meta ) && ! empty( $attachment_meta ) ) {
			foreach ( $attachment_meta as $attachment_id => $attachment_data ) {
				// any attachments to include?
				if ( ! empty( $args['include'] ) ) {
					// included attachment?
					if ( in_array( $attachment_id, $args['include'], true ) ) {
						$include[] = $attachment_data['file_id'];

						// assign attachment meta
						$attachments_meta[$attachment_data['file_id']]['menu_order'] = $menu_order;
						$attachments_meta[$attachment_data['file_id']]['exclude'] = false;

						$menu_order++;
					}
				// any attachments to exclude?
				} elseif ( ! empty( $args['exclude'] ) ) {
					// included attachment?
					if ( ! in_array( $attachment_id, $args['exclude'], true ) ) {
						$include[] = $attachment_data['file_id'];

						// assign attachment meta
						$attachments_meta[$attachment_data['file_id']]['menu_order'] = $menu_order;
						$attachments_meta[$attachment_data['file_id']]['exclude'] = false;

						$menu_order++;
					}
				} else {
					if ( isset( $attachment_data['file_exclude'] ) && $attachment_data['file_exclude'] === true )
						$exclude[] = $attachment_data['file_id'];
					else {
						$include[] = $attachment_data['file_id'];

						// assign attachment meta
						$attachments_meta[$attachment_data['file_id']]['menu_order'] = $menu_order;
						$attachments_meta[$attachment_data['file_id']]['exclude'] = false;

						$menu_order++;
					}
				}
			}
		}

		// assign included and excluded attachments
		$args['include'] = $include;
		$args['exclude'] = $exclude;
	}

	// handle orderby
	switch ( $args['orderby'] ) {
		case 'downloads':
			$args['orderby'] = 'meta_value_num';
			break;

		default:
			break;
	}

	// all posts
	if ( $post_id == null )
		$attachments_data = get_posts( apply_filters( 'da_get_download_attachments_args', $args ) );
	// single post
	elseif ( $post_id !== null && ! empty( $args['include'] ) )
		$attachments_data = get_posts( apply_filters( 'da_get_download_attachments_args', $args ) );
	// no attachments
	else
		$attachments_data = [];

	// assign attachment data
	if ( ! empty( $attachments_data ) ) {
		foreach ( $attachments_data as $attachment ) {
			// merge with attachment meta
			if ( isset( $attachments_meta[$attachment->ID] ) && ! empty( $attachments_meta[$attachment->ID] ) )
				$attachments[$attachment->ID] = $attachments_meta[$attachment->ID];

			$filename = get_attached_file( $attachment->ID );
			$filetype = wp_check_filetype( $filename );

			switch ( $filetype['ext'] ) {
				case 'jpeg':
					$extension = 'jpg';
					break;

				case 'docx':
					$extension = 'doc';
					break;

				case 'xlsx':
					$extension = 'xls';
					break;

				default:
					$extension = $filetype['ext'];
					break;
			}

			$extension = apply_filters( 'da_file_extension_type', $extension );

			$attachments[$attachment->ID]['ID'] = (int) $attachment->ID;
			$attachments[$attachment->ID]['title'] = trim( esc_attr( $attachment->post_title ) );
			$attachments[$attachment->ID]['caption'] = trim( esc_attr( $attachment->post_excerpt ) );
			$attachments[$attachment->ID]['description'] = trim( esc_attr( $attachment->post_content ) );
			$attachments[$attachment->ID]['size'] = ( file_exists( $filename ) ? filesize( $filename ) : 0 );
			$attachments[$attachment->ID]['type'] = $extension;
			$attachments[$attachment->ID]['downloads'] = (int) get_post_meta( $attachment->ID, '_da_downloads', true );
			$attachments[$attachment->ID]['url'] = da_get_download_attachment_url( $attachment->ID );
			$attachments[$attachment->ID]['icon_url'] = ( file_exists( DOWNLOAD_ATTACHMENTS_PATH . 'images/ext/' . $extension . '.gif' ) ? DOWNLOAD_ATTACHMENTS_URL . '/images/ext/' . $extension . '.gif' : DOWNLOAD_ATTACHMENTS_URL . '/images/ext/unknown.gif' );
			$attachments[$attachment->ID]['menu_order'] = isset( $attachments[$attachment->ID]['menu_order'] ) ? $attachments[$attachment->ID]['menu_order'] : $attachment->menu_order;
			$attachments[$attachment->ID]['date_added'] = $attachment->post_date;
			$attachments[$attachment->ID]['timestamp'] = strtotime( $attachment->post_date, 0 );
			$attachments[$attachment->ID]['user_added'] = $attachment->post_author;
		}
	}

	// multiarray sorting
	if ( ! empty( $attachments ) && in_array( $args['orderby'], [ 'menu_order', 'size' ] ) ) {
		$sort_array = [];

		foreach ( $attachments as $key => $row ) {
			$sort_array[$key] = $row[$args['orderby']];
		}

		$order = ( strtolower( $args['order'] ) === 'asc' ? SORT_ASC : SORT_DESC );

		array_multisort( $attachments, SORT_NUMERIC, $order, $sort_array, ( in_array( $args['orderby'], [ 'menu_order', 'size' ], true ) ? SORT_NUMERIC : SORT_STRING ), $order );
	}

	return apply_filters( 'da_get_download_attachments', $attachments, $post_id, $args );
}

/**
 * Display download attachments for a given post.
 *
 * @param int $post_id
 * @param array $args
 * @return void|string
 */
function da_display_download_attachments( $post_id = 0, $args = [] ) {
	// get post id
	$post_id = $post_id !== null ? ( (int) ( empty( $post_id ) ? get_the_ID() : $post_id ) ) : $post_id;

	// password protected post?
	if ( ! post_password_required( $post_id ) ) {
		// get options and defaults
		$options = Download_Attachments()->options;
		$_defaults = Download_Attachments()->defaults;

		$defaults = [
			'container'				=> 'div',
			'container_class'		=> 'download-attachments',
			'container_id'			=> '',
			'style'					=> isset( $options['display_style'] ) ? esc_attr( $options['display_style'] ) : 'list',
			'link_before'			=> '',
			'link_after'			=> '',
			'content_before'		=> '',
			'content_after'			=> '',
			'display_index'			=> isset( $options['frontend_columns']['index'] ) ? (int) $options['frontend_columns']['index'] : $_defaults['general']['frontend_columns']['index'],
			'display_user'			=> isset( $options['frontend_columns']['author'] ) ? (int) $options['frontend_columns']['author'] : $_defaults['general']['frontend_columns']['author'],
			'display_icon'			=> isset( $options['frontend_columns']['icon'] ) ? (int) $options['frontend_columns']['icon'] : $_defaults['general']['frontend_columns']['icon'],
			'display_count'			=> isset( $options['frontend_columns']['downloads'] ) ? (int) $options['frontend_columns']['downloads'] : $_defaults['general']['frontend_columns']['downloads'],
			'display_size'			=> isset( $options['frontend_columns']['size'] ) ? (int) $options['frontend_columns']['size'] : $_defaults['general']['frontend_columns']['size'],
			'display_date'			=> isset( $options['frontend_columns']['date'] ) ? (int) $options['frontend_columns']['date'] : $_defaults['general']['frontend_columns']['date'],
			'display_caption'		=> isset( $options['frontend_content']['caption'] ) ? (int) $options['frontend_content']['caption'] : $_defaults['general']['frontend_content']['caption'],
			'display_description'	=> isset( $options['frontend_content']['description'] ) ? (int) $options['frontend_content']['description'] : $_defaults['general']['frontend_content']['description'],
			'display_empty'			=> 0,
			'display_option_none'	=> __( 'No attachments to download.', 'download-attachments' ),
			'use_desc_for_title'	=> 0,
			'exclude'				=> '',
			'include'				=> '',
			'title'					=> __( 'Attachments', 'download-attachments' ),
			'title_container'		=> 'h3',
			'title_class'			=> 'attachments-title',
			'posts_per_page'		=> ( isset( $args['number_of_posts'] ) ? (int) $args['number_of_posts'] : -1 ),
			'offset'				=> 0,
			'orderby'				=> 'menu_order',
			'order'					=> 'asc',
			'echo'					=> 1
		];

		$args = apply_filters( 'da_display_attachments_defaults', wp_parse_args( $args, $defaults ), $post_id );

		$args['display_index'] = (int) apply_filters( 'da_display_attachments_index', (int) $args['display_index'] );
		$args['display_user'] = (int) apply_filters( 'da_display_attachments_user', (int) $args['display_user'] );
		$args['display_icon'] = (int) apply_filters( 'da_display_attachments_icon', (int) $args['display_icon'] );
		$args['display_count'] = (int) apply_filters( 'da_display_attachments_count', (int) $args['display_count'] );
		$args['display_size'] = (int) apply_filters( 'da_display_attachments_size', (int) $args['display_size'] );
		$args['display_date'] = (int) apply_filters( 'da_display_attachments_date', (int) $args['display_date'] );
		$args['display_caption'] = (int) apply_filters( 'da_display_attachments_caption', (int) $args['display_caption'] );
		$args['display_description'] = (int) apply_filters( 'da_display_attachments_description', (int) $args['display_description'] );
		$args['display_empty'] = (int) apply_filters( 'da_display_attachments_empty', (int) $args['display_empty'] );
		$args['use_desc_for_title'] = (int) $args['use_desc_for_title'];
		$args['echo'] = (int) $args['echo'];
		$args['style'] = in_array( $args['style'], array_keys( Download_Attachments()->display_styles ), true ) ? $args['style'] : $defaults['style'];
		$args['orderby'] = in_array( $args['orderby'], [ 'menu_order', 'ID', 'date', 'title', 'size', 'downloads' ], true ) ? $args['orderby'] : $defaults['orderby'];
		$args['posts_per_page'] = (int) $args['posts_per_page'];
		$args['offset'] = (int) $args['offset'];
		$args['order'] = in_array( strtolower( $args['order'] ), [ 'asc', 'desc' ], true ) ? $args['order'] : $defaults['order'];
		$args['link_before'] = trim( $args['link_before'] );
		$args['link_after'] = trim( $args['link_after'] );
		$args['display_option_none'] = ( $info = trim( $args['display_option_none'] ) ) !== '' ? $info : $defaults['display_option_none'];
		$args['title'] = apply_filters( 'da_display_attachments_title', trim( $args['title'] ) );

		$args['attachments'] = da_get_download_attachments(
			$post_id,
			apply_filters(
				'da_display_attachments_args',
				[
					'include'			=> $args['include'],
					'exclude'			=> $args['exclude'],
					'orderby'			=> $args['orderby'],
					'order'				=> $args['order'],
					'posts_per_page'	=> $args['posts_per_page'],
					'offset'			=> $args['offset']
				]
			)
		);

		$args['count'] = count( $args['attachments'] );

		if ( $args['style'] === 'dynatable' ) {
			wp_register_script( 'da-frontend-datatables', DOWNLOAD_ATTACHMENTS_URL . '/assets/datatables/datatables' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [], '1.13.8' );
			wp_enqueue_script( 'da-frontend', DOWNLOAD_ATTACHMENTS_URL . '/js/frontend.js', [ 'jquery', 'da-frontend-datatables' ], Download_Attachments()->defaults['version'] );

			$columnTypes = [];

			// index
			if ( $args['display_index'] === 1 )
				$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];

			// title
			$columnTypes[] = [ 'orderable' => true ];

			// description
			if ( $args['display_caption'] === 1 || ( $args['display_description'] === 1 && $args['use_desc_for_title'] === 0 ) )
				$columnTypes[] = [ 'orderable' => false ];

			// date added
			if ( $args['display_date'] === 1 )
				$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];

			// added by
			if ( $args['display_user'] === 1 )
				$columnTypes[] = [ 'orderable' => true ];

			// file size
			if ( $args['display_size'] === 1 )
				$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];

			// downloads
			if ( $args['display_count'] === 1 )
				$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];

			// prepare script data
			$script_data = apply_filters(
				'da_display_attachments_dynatable_args',
				[
					'columnTypes'	=> $columnTypes,
					'features'		=> [
						'paginate'		=> true, // paginate
						'sort'			=> true, // ordering
						'pushState'		=> true, // stateSave
						'search'		=> true, // searching
						'recordCount'	=> true, // info
						'perPageSelect'	=> true	// lengthChange
					],
					'inputs'		=> [
						'recordCountPlacement'		=> 'after',
						'paginationLinkPlacement'	=> 'after',
						'paginationPrev'			=> __( 'Previous', 'download-attachments' ),
						'paginationNext'			=> __( 'Next', 'download-attachments' ),
						'paginationGap'				=> [ 1, 2, 2, 1 ],
						'searchPlacement'			=> 'before',
						'perPagePlacement'			=> 'before',
						'perPageText'				=> __( 'Show', 'download-attachments' ) . ': ',
						'recordCountText'			=> __( 'Showing', 'download-attachments' ) . ' ',
						'processingText'			=> __( 'Processing', 'download-attachments' ). '...'
					],
					'dataset'		=> [
						'perPageDefault'	=> 5, // pageLength
						'perPageOptions'	=> [ [ 1, 10, 25, 50, -1 ], [ 1, 10, 25, 50, esc_html__( 'All', 'download-attachments' ) ] ] // lengthMenu
					]
				]
			);

			wp_add_inline_script( 'da-frontend', 'var daDataTablesArgs = ' . wp_json_encode( $script_data ) . ";\n", 'before' );
		}

		ob_start();

		da_get_template( 'attachments-' . $args['style'] . '.php', $args );

		$html = ob_get_contents();

		ob_end_clean();
	} else
		$html = '';

	if ( $args['echo'] === 1 )
		echo apply_filters( 'da_display_attachments', $html );
	else
		return apply_filters( 'da_display_attachments', $html );
}

/**
 * Attach new file to the post.
 *
 * @param int $attachment_id
 * @param int $post_id
 * @return bool
 */
function da_attach_file( $attachment_id, $post_id ) {
	$attachment_id = (int) $attachment_id;
	$post_id = (int) $post_id;

	$options = Download_Attachments()->options;
	$post_type = get_post_type( $post_id );

	// is it attachment?
	if ( get_post_type( $attachment_id ) === 'attachment' ) {
		// is it supported post type?
		if ( array_key_exists( $post_type, $options['post_types'] ) && $options['post_types'][$post_type] ) {
			// get attachments
			$attachments = get_post_meta( (int) $post_id, '_da_attachments', true );

			// attachment does not exist?
			if ( is_array( $attachments ) && ! array_key_exists( $attachment_id, $attachments ) ) {
				// prepare new attachment
				$attachments[$attachment_id] = [
					'file_id'		=> $attachment_id,
					'file_date'		=> current_time( 'mysql' ),
					'file_exclude'	=> false,
					'file_user_id'	=> get_current_user_id()
				];

				// update attachments
				update_post_meta( $post_id, '_da_attachments', $attachments );

				// check whether any files are already attached to this post
				if ( ( $files_meta = get_post_meta( $attachment_id, '_da_posts', true ) ) !== '' && is_array( $files_meta ) && ! empty( $files_meta ) ) {
					$files_meta[] = $post_id;

					update_post_meta( $attachment_id, '_da_posts', array_unique( $files_meta ) );
				} else
					update_post_meta( $attachment_id, '_da_posts', [ $post_id ] );

				// first time?
				if ( get_post_meta( $attachment_id, '_da_downloads', true ) === '' )
					update_post_meta( $attachment_id, '_da_downloads', 0 );

				return true;
			}
		}
	}

	return false;
}

/**
 * Unattach file from the post.
 *
 * @param int $attachment_id
 * @param int $post_id
 * @return bool
 */
function da_unattach_file( $attachment_id, $post_id ) {
	// cast attachment id
	$attachment_id = (int) $attachment_id;

	// get attachments
	$attachments = get_post_meta( (int) $post_id, '_da_attachments', true );

	// any attachments?
	if ( is_array( $attachments ) && array_key_exists( $attachment_id, $attachments ) ) {
		// unattach attachment
		unset( $attachments[$attachment_id] );

		// update post attachments
		update_post_meta( $post_id, '_da_attachments', $attachments );

		if ( ( $files_meta = get_post_meta( $attachment_id, '_da_posts', true ) ) !== '' && is_array( $files_meta ) && ! empty( $files_meta ) ) {
			foreach ( $files_meta as $key => $id ) {
				if ( $id === $post_id ) {
					unset( $files_meta[$key] );

					// update attachment posts
					update_post_meta( $attachment_id, '_da_posts', $files_meta );

					break;
				}
			}
		}

		return true;
	}

	return false;
}

/**
 * Get other templates (e.g. archives) passing attributes and including the file.
 *
 * @param string $template_name
 * @param array $args
 * @param string $template_path
 * @param string $default_path
 * @return void
 */
function da_get_template( $template_name, $args = [], $template_path = '', $default_path = '' ) {
	if ( $args && is_array( $args ) )
		extract( $args );

	$located = da_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) )
		return;

	do_action( 'da_template_part_before', $template_name, $template_path, $located, $args );

	include( $located );

	do_action( 'da_template_part_after', $template_name, $template_path, $located, $args );
}

/**
 * Locate a template and return the path for inclusion.
 *
 * @param string $template_name
 * @param string $template_path
 * @param string $default_path
 * @return string
 */
function da_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path )
		$template_path = TEMPLATEPATH . '/';

	if ( ! $default_path )
		$default_path = DOWNLOAD_ATTACHMENTS_PATH . 'templates/';

	// look within passed path within the theme - this is priority
	$template = locate_template( [ trailingslashit( $template_path ) . $template_name, $template_name ] );

	// get default template
	if ( ! $template )
		$template = $default_path . $template_name;

	// return what we found
	return apply_filters( 'da_locate_template', $template, $template_name, $template_path );
}

/**
 * Get single attachment download link.
 *
 * @param int $attachment_id
 * @param bool $echo
 * @param array $attr
 * @return void|string
 */
function da_download_attachment_link( $attachment_id = 0, $echo = false, $attr = [] ) {
	if ( get_post_type( $attachment_id ) === 'attachment' ) {
		$options = Download_Attachments()->options;

		$attr['title'] = ! empty( $attr['title'] ) ? $attr['title'] : get_the_title( $attachment_id );
		$attr['class'] = ! empty( $attr['class'] ) ? $attr['class'] : "da-download-link da-download-attachment-$attachment_id";

		// redirect to file?
		if ( $options['download_method'] === 'redirect' )
			$attr['target'] = $options['link_target'];

		$attr = apply_filters( 'da_download_attachment_link_attributes', $attr, $attachment_id );
		$attr = array_map( 'esc_attr', $attr );
		$attr_html = '';

		foreach ( $attr as $name => $value ) {
			$attr_html .= ' ' . $name;

			if ( ! empty( $value ) )
				$attr_html .= '="' . $value . '"';
		}

		$link = '<a href="' . da_get_download_attachment_url( $attachment_id ) . '"' . $attr_html . '>' . $attr['title'] . '</a>';
	} else
		$link = '';

	if ( $echo === true )
		echo apply_filters( 'da_download_attachment_link', $link );
	else
		return apply_filters( 'da_download_attachment_link', $link );
}

/**
 * Get single attachment download URL.
 *
 * @param int $attachment_id
 * @return string
 */
function da_get_download_attachment_url( $attachment_id = 0 ) {
	if ( get_post_type( $attachment_id ) != 'attachment' )
		return '';

	$options = Download_Attachments()->options;
	$encrypted_id = isset( $options['encrypt_urls'] ) && $options['encrypt_urls'] ? da_encrypt_attachment_id( $attachment_id ) : $attachment_id;

	$url = untrailingslashit( esc_url( isset( $options['pretty_urls'] ) && $options['pretty_urls'] === true ? home_url( '/' . $options['download_link'] . '/' . $encrypted_id . '/' ) : DOWNLOAD_ATTACHMENTS_URL . '/includes/download.php?id=' . $encrypted_id ) );

	return apply_filters( 'da_get_download_attachment_url', $url, $attachment_id );
}

/**
 * Encrypt attachment ID for the URL.
 *
 * @param int $id
 * @return string
 */
function da_encrypt_attachment_id( $id ) {
	$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
	$auth_iv = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';

	// openssl strong encryption
	if ( function_exists( 'openssl_encrypt' ) && defined( 'OPENSSL_RAW_DATA' ) ) {
		$ivsize = openssl_cipher_iv_length( 'aes-128-cbc' );
		$iv = openssl_random_pseudo_bytes( $ivsize );
		$ciphertext = openssl_encrypt( $id, 'aes-128-cbc', $auth_key . $auth_iv, OPENSSL_RAW_DATA, $iv );
		$encrypted_id = strtr( base64_encode( $iv . $ciphertext ), '+/=', '-_,' );
	// mcrypt strong encryption
	} elseif ( function_exists( 'mcrypt_encrypt' ) && defined( 'MCRYPT_BLOWFISH' ) ) {
		// get max key size of the mcrypt mode
		$max_key_size = mcrypt_get_key_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
		$max_iv_size = mcrypt_get_iv_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );

		$encrypt_key = mb_strimwidth( $auth_key, 0, $max_key_size );
		$encrypt_iv = mb_strimwidth( $auth_iv, 0, $max_iv_size );

		$encrypted_id = strtr( base64_encode( mcrypt_encrypt( MCRYPT_BLOWFISH, $encrypt_key, $id, MCRYPT_MODE_CBC, $encrypt_iv ) ), '+/=', '-_,' );
	// simple encryption
	} elseif ( function_exists( 'gzdeflate' ) )
		$encrypted_id = base64_encode( convert_uuencode( gzdeflate( $id ) ) );
	// no encryption
	else
		$encrypted_id = strtr( base64_encode( convert_uuencode( $id ) ), '+/=', '-_,' );

	return apply_filters( 'da_encrypt_attachment_id', $encrypted_id, $id );
}

/**
 * Decrypt attachment ID for the URL.
 *
 * @param string $encrypted_id
 * @return int
 */
function da_decrypt_attachment_id( $encrypted_id ) {
	$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
	$auth_iv = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';

	// openssl strong encryption
	if ( function_exists( 'openssl_decrypt' ) && defined( 'OPENSSL_RAW_DATA' ) ) {
		$ivsize = openssl_cipher_iv_length( 'aes-128-cbc' );
		$iv = mb_substr( base64_decode( strtr( $encrypted_id, '-_,', '+/=' ) ), 0, $ivsize, '8bit' );
		$ciphertext = mb_substr( base64_decode( strtr( $encrypted_id, '-_,', '+/=' ) ), $ivsize, null, '8bit' );
		$id = openssl_decrypt( $ciphertext, 'aes-128-cbc', $auth_key . $auth_iv, OPENSSL_RAW_DATA, $iv );
	// mcrypt strong encryption
	} elseif ( function_exists( 'mcrypt_decrypt' ) && defined( 'MCRYPT_BLOWFISH' ) ) {
		// get max key size of the mcrypt mode
		$max_key_size = mcrypt_get_key_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
		$max_iv_size = mcrypt_get_iv_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );

		$encrypt_key = mb_strimwidth( $auth_key, 0, $max_key_size );
		$encrypt_iv = mb_strimwidth( $auth_iv, 0, $max_iv_size );

		$id = mcrypt_decrypt( MCRYPT_BLOWFISH, $encrypt_key, base64_decode( strtr( $encrypted_id, '-_,', '+/=' ) ), MCRYPT_MODE_CBC, $encrypt_iv );
	// simple encryption
	} elseif ( function_exists( 'gzinflate' ) )
		$id = gzinflate( convert_uudecode( base64_decode( $encrypted_id ) ) );
	// no encryption
	else
		$id = convert_uudecode( base64_decode( strtr( $encrypted_id, '-_,', '+/=' ) ) );

	return (int) apply_filters( 'da_decrypt_attachment_id', $id, $encrypted_id );
}

/**
 * Get single attachment download data.
 *
 * @param int $attachment_id
 * @return false|array
 */
function da_get_download_attachment( $attachment_id = 0 ) {
	$attachment_id = ! empty( $attachment_id ) ? absint( $attachment_id ) : 0;

	// break if there's no attachment ID given
	if ( empty( $attachment_id ) )
		return false;

	// break if given ID is not for attachment
	if ( get_post_type( $attachment_id ) != 'attachment' )
		return false;

	$post = get_post( $attachment_id );
	$filename = get_attached_file( $attachment_id );
	$filetype = wp_check_filetype( $filename );

	// adjust extension type
	switch ( $filetype['ext'] ) {
		case 'jpeg' :
			$extension = 'jpg';
			break;
		case 'docx' :
			$extension = 'doc';
			break;
		case 'xlsx' :
			$extension = 'xls';
			break;
		default :
			$extension = $filetype['ext'];
			break;
	}

	$extension = apply_filters( 'da_file_extension_type', $extension );

	$attachment['title'] = get_the_title( $attachment_id );
	$attachment['caption'] = trim( esc_attr( $post->post_excerpt ) );
	$attachment['description'] = trim( esc_attr( $post->post_content ) );
	$attachment['date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $post->post_date ) );
	$attachment['size'] = size_format( (file_exists( $filename ) ? filesize( $filename ) : 0 ) );
	$attachment['type'] = $extension;
	$attachment['downloads'] = (int) get_post_meta( $attachment_id, '_da_downloads', true );
	$attachment['url'] = da_get_download_attachment_url( $attachment_id );
	$attachment['icon_url'] = ( file_exists( DOWNLOAD_ATTACHMENTS_PATH . 'images/ext/' . $extension . '.gif' ) ? DOWNLOAD_ATTACHMENTS_URL . '/images/ext/' . $extension . '.gif' : DOWNLOAD_ATTACHMENTS_URL . '/images/ext/unknown.gif' );

	return apply_filters( 'da_get_download_attachment', $attachment, $attachment_id );
}

/**
 * Process attachment download.
 *
 * @param int $attachment_id
 * @return void|false
 */
function da_download_attachment( $attachment_id = 0 ) {
	if ( get_post_type( $attachment_id ) === 'attachment' ) {
		// get options
		$options = Download_Attachments()->options;

		if ( ! isset( $options['download_method'] ) )
			$options['download_method'] = 'force';

		// get wp upload directory data
		$uploads = wp_upload_dir();

		// get file name
		$attachment = get_post_meta( $attachment_id, '_wp_attached_file', true );

		// get downloads count
		$downloads_count = (int) get_post_meta( $attachment_id, '_da_downloads', true );

		// force download
		if ( $options['download_method'] === 'force' ) {
			// get file path
			$filepath = apply_filters( 'da_download_attachment_filepath', $uploads['basedir'] . '/' . $attachment, $attachment_id );

			// file exists?
			if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) )
				return false;

			// if filename contains folders
			if ( ( $position = strrpos( $attachment, '/', 0 ) ) !== false )
				$filename = substr( $attachment, $position + 1 );
			else
				$filename = $attachment;

			// disable compression
			if ( ini_get( 'zlib.output_compression' ) )
				@ini_set( 'zlib.output_compression', 0 );

			if ( function_exists( 'apache_setenv' ) )
				@apache_setenv( 'no-gzip', 1 );

			// disable max execution time limit
			if ( ! in_array( 'set_time_limit', explode( ',', ini_get( 'disable_functions' ) ) ) && ! ini_get( 'safe_mode' ) )
				@set_time_limit( 0 );

			// disable magic quotes runtime
			if ( function_exists( 'get_magic_quotes_runtime' ) && get_magic_quotes_runtime() && version_compare( phpversion(), '5.4', '<' ) )
				set_magic_quotes_runtime( 0 );

			// set needed headers
			nocache_headers();
			header( 'Robots: none' );
			header( 'Content-Type: application/download' );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . rawurldecode( $filename ) );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Accept-Ranges: bytes' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $filepath ) );

			// increase downloads count
			update_post_meta( $attachment_id, '_da_downloads', $downloads_count + 1, $downloads_count );

			// action hook
			do_action( 'da_process_file_download', $attachment_id );

			// start printing file
			if ( $filepath = fopen( $filepath, 'rb' ) ) {
				while ( ! feof( $filepath ) && ( ! connection_aborted()) ) {
					echo fread( $filepath, 1048576 );
					flush();
				}

				fclose( $filepath );
			} else
				return false;

			exit;
		// redirect to file
		} else {
			// increase downloads count
			update_post_meta( $attachment_id, '_da_downloads', $downloads_count + 1, $downloads_count );

			// action hook
			do_action( 'da_process_file_download', $attachment_id );

			// force file url
			header( 'Location: ' . apply_filters( 'da_download_attachment_filepath', $uploads['baseurl'] . '/' . $attachment, $attachment_id ) );
			exit;
		}
	// not an attachment
	} else
		return false;
}

/**
 * Get most downloaded attachments.
 *
 * @param array	$args
 * @return array
 */
if ( ! function_exists( 'da_get_most_downloaded_attachments' ) ) {
	function da_get_most_downloaded_attachments( $args = [] ) {
		$args = array_merge(
			[
				'posts_per_page'	=> 10,
				'order'				=> 'desc'
			],
			$args
		);

		$args = apply_filters( 'da_get_most_downloaded_attachments_args', $args );

		// force important arguments
		$args['suppress_filters'] = false;
		$args['post_type'] = 'attachment';
		$args['orderby'] = 'meta_value_num';
		$args['meta_key'] = '_da_downloads';
		$args['fields'] = '';

		$args['meta_query'] = [
			'relation' => 'OR',
			[
				'key'		=> '_da_downloads',
				'compare'	=> 'EXISTS'
			],
			[
				'key'		=> '_da_downloads',
				'value'		=> 'something',
				'compare'	=> 'NOT EXISTS'
			]
		];

		return apply_filters( 'da_get_most_downloaded_attachments', get_posts( $args ), $args );
	}
}

/**
 * Get attachment downloads.
 *
 * @param int $post_id
 * @return int
 */
if ( ! function_exists( 'da_get_attachment_downloads' ) ) {
	function da_get_attachment_downloads( $post_id = 0 ) {
		$post_id = (int) $post_id;
		$downloads = ( $post_id > 0 ? (int) get_post_meta( $post_id, '_da_downloads', true ) : 0 );

		return apply_filters( 'da_get_attachment_downloads', $downloads, $post_id );
	}
}

/**
 * Display a list of most downloaded attachments.
 *
 * @param array	$args
 * @param bool $display
 * @return void|string
 */
if ( ! function_exists( 'da_most_downloaded_attachments' ) ) {
	function da_most_downloaded_attachments( $args = [], $display = true ) {
		$defaults = [
			'number_of_posts'			=> 5,
			'link_type'					=> 'page',
			'order'						=> 'desc',
			'show_attachment_downloads'	=> true,
			'show_attachment_icon'		=> true,
			'show_attachment_excerpt'	=> false,
			'no_attachments_message'	=> __( 'No Attachments', 'download-attachments' )
		];

		$args = apply_filters( 'da_most_downloaded_attachments_args', wp_parse_args( $args, $defaults ) );

		$args['show_attachment_downloads'] = (bool) $args['show_attachment_downloads'];
		$args['show_attachment_icon'] = (bool) $args['show_attachment_icon'];
		$args['show_attachment_excerpt'] = (bool) $args['show_attachment_excerpt'];

		$attachments = da_get_most_downloaded_attachments(
			[
				'posts_per_page'	=> ( isset( $args['number_of_posts'] ) ? (int) $args['number_of_posts'] : $defaults['number_of_posts'] ),
				'order'				=> ( isset( $args['order'] ) ? $args['order'] : $defaults['order'] )
			]
		);

		if ( ! empty( $attachments ) ) {
			$html = '
		<ul>';

			foreach ( $attachments as $attachment ) {
				setup_postdata( $attachment );

				$filetype = wp_check_filetype( get_attached_file( $attachment->ID ) );

				// adjust extension type
				switch ( $filetype['ext'] ) {
					case 'jpeg' :
						$extension = 'jpg';
						break;
					case 'docx' :
						$extension = 'doc';
						break;
					case 'xlsx' :
						$extension = 'xls';
						break;
					default :
						$extension = $filetype['ext'];
						break;
				}

				$extension = apply_filters( 'da_file_extension_type', $extension );

				$html .= '
			<li>';

				if ( $args['show_attachment_icon'] ) {
					$html .= '
			<span class="post-icon">
				<img class="attachment-icon" src="' . ( file_exists( DOWNLOAD_ATTACHMENTS_PATH . 'images/ext/' . $extension . '.gif' ) ? DOWNLOAD_ATTACHMENTS_URL . '/images/ext/' . $extension . '.gif' : DOWNLOAD_ATTACHMENTS_URL . '/images/ext/unknown.gif' ) . '" alt="" />
			</span>';
				}

				$html .= '
			<a class="post-title" href="' . ( $args['link_type'] === 'page' ? get_permalink( $attachment->ID ) : da_get_download_attachment_url( $attachment->ID ) ) . '">' . esc_html( get_the_title( $attachment->ID ) ) . '</a>' . ( $args['show_attachment_downloads'] ? ' <span class="count">(' . number_format_i18n( da_get_attachment_downloads( $attachment->ID ) ) . ')</span>' : '' );

				$excerpt = '';

				if ( $args['show_attachment_excerpt'] ) {
					if ( empty( $attachment->post_excerpt ) )
						$text = $attachment->post_content;
					else
						$text = $attachment->post_excerpt;

					if ( ! empty( $text ) )
						$excerpt = wp_trim_words( str_replace( ']]>', ']]&gt;', strip_shortcodes( $text ) ), apply_filters( 'excerpt_length', 55 ), apply_filters( 'excerpt_more', ' ' . '[&hellip;]' ) );
				}

				if ( ! empty( $excerpt ) )
					$html .= '
			<div class="post-excerpt">' . esc_html( $excerpt ) . '</div>';

				$html .= '
			</li>';
			}

			wp_reset_postdata();

			$html .= '
		</ul>';
		} else
			$html = $args['no_attachments_message'];

		$html = apply_filters( 'da_most_downloaded_attachments_html', $html, $args );

		if ( $display )
			echo $html;
		else
			return $html;
	}
}