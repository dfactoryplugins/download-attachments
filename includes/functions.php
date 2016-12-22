<?php

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Get download attachments data for a given post.
 * 
 * @param int $post_id
 * @param array $args
 * @return array
 */
function da_get_download_attachments( $post_id = 0, $args = array() ) {
	$post_id = (int) (empty( $post_id ) ? get_the_ID() : $post_id);

	$defaults = array(
		'include'	 => array(),
		'exclude'	 => array(),
		'orderby'	 => 'menu_order',
		'order'		 => 'asc'
	);

	$args = array_merge( $defaults, $args );

	// include
	if ( is_array( $args['include'] ) && ! empty( $args['include'] ) ) {
		$ids = array();

		foreach ( $args['include'] as $id ) {
			$ids[] = (int) $id;
		}

		$args['include'] = $ids;
	} elseif ( is_numeric( $args['include'] ) ) {
		$args['include'] = array( (int) $args['include'] );
		// shortcode
	} elseif ( is_string( $args['include'] ) && ! empty( $args['include'] ) ) {
		$args['include'] = json_decode( '[' . $args['include'] . ']', true );
	} else {
		$args['include'] = $defaults['include'];

		// exclude
		if ( is_array( $args['exclude'] ) && ! empty( $args['exclude'] ) ) {
			$ids = array();

			foreach ( $args['exclude'] as $id ) {
				$ids[] = (int) $id;
			}

			$args['exclude'] = $ids;
		} elseif ( is_numeric( $args['exclude'] ) ) {
			$args['exclude'] = array( (int) $args['exclude'] );
		} elseif ( is_string( $args['exclude'] ) && ! empty( $args['exclude'] ) ) {
			$args['exclude'] = json_decode( '[' . $args['exclude'] . ']', true );
		} else {
			$args['exclude'] = $defaults['exclude'];
		}
	}

	// order
	$args['orderby'] = (in_array( $args['orderby'], array( 'menu_order', 'attachment_id', 'attachment_date', 'attachment_title', 'attachment_size', 'attachment_downloads' ), true ) ? $args['orderby'] : $defaults['orderby']);
	$args['order'] = (in_array( $args['order'], array( 'asc', 'desc' ), true ) ? $args['order'] : $defaults['order']);

	$files = array();

	if ( ($files_meta = get_post_meta( $post_id, '_da_attachments', true )) !== '' && is_array( $files_meta ) && ! empty( $files_meta ) ) {
		foreach ( $files_meta as $file ) {
			$add = false;

			// all
			if ( empty( $args['include'] ) && empty( $args['exclude'] ) ) {
				$add = true;
				// include
			} elseif ( ! empty( $args['include'] ) && empty( $args['exclude'] ) && in_array( $file['file_id'], $args['include'] ) ) {
				$add = true;
				// exclude
			} elseif ( empty( $args['include'] ) && ! empty( $args['exclude'] ) ) {
				$add = true;

				if ( in_array( $file['file_id'], $args['exclude'] ) )
					$add = false;
			}

			if ( $add ) {
				$files[$file['file_id']] = array(
					'attachment_id'			 => $file['file_id'],
					'attachment_date'		 => $file['file_date'],
					'attachment_user_id'	 => $file['file_user_id'],
					'attachment_exclude'	 => (isset( $file['file_exclude'] ) && $file['file_exclude'] === true ? true : false),
					'attachment_user_name'	 => get_the_author_meta( 'display_name', $file['file_user_id'] ),
					'attachment_downloads'	 => (int) get_post_meta( $file['file_id'], '_da_downloads', true )
				);
			}
		}

		$args['include'] = array_keys( $files );
	}

	if ( ! empty( $args['include'] ) ) {
		$files_data = get_posts(
		apply_filters( 'da_get_download_attachments_args', array(
			'include'		 => $args['include'],
			'posts_per_page' => -1,
			'offset'		 => 0,
			'orderby'		 => 'post_date',
			'order'			 => 'DESC',
			'post_type'		 => 'attachment',
			'post_status'	 => 'any'
		) )
		);

		if ( ! empty( $files_data ) ) {
			foreach ( $files_data as $file ) {
				if ( isset( $files[$file->ID] ) ) {
					$filename = get_attached_file( $file->ID );
					$filetype = wp_check_filetype( $filename );
					
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

					$files[$file->ID]['attachment_title'] = trim( esc_attr( $file->post_title ) );
					$files[$file->ID]['attachment_caption'] = trim( esc_attr( $file->post_excerpt ) );
					$files[$file->ID]['attachment_description'] = trim( esc_attr( $file->post_content ) );
					$files[$file->ID]['attachment_size'] = ( file_exists( $filename ) ? filesize( $filename ) : 0 );
					$files[$file->ID]['attachment_url'] = esc_url( wp_get_attachment_url( $file->ID ) );
					$files[$file->ID]['attachment_type'] = $extension;
					$files[$file->ID]['attachment_icon_url'] = ( file_exists( DOWNLOAD_ATTACHMENTS_PATH . 'images/ext/' . $extension . '.gif' ) ? DOWNLOAD_ATTACHMENTS_URL . '/images/ext/' . $extension . '.gif' : DOWNLOAD_ATTACHMENTS_URL . '/images/ext/unknown.gif' );
				}
			}
		}
	}

	// multiarray sorting
	if ( $args['orderby'] !== 'menu_order' ) {
		$sort_array = array();

		foreach ( $files as $key => $row ) {
			$sort_array[$key] = ($args['orderby'] === 'attachment_title' ? mb_strtolower( $row[$args['orderby']], 'UTF-8' ) : $row[$args['orderby']]);
		}

		$order = ($args['order'] === 'asc' ? SORT_ASC : SORT_DESC);

		array_multisort( $files, SORT_NUMERIC, $order, $sort_array, (in_array( $args['orderby'], array( 'attachment_id', 'attachment_size', 'attachment_downloads' ), true ) ? SORT_NUMERIC : SORT_STRING ), $order );
	}

	// we need to format raw data
	foreach ( $files as $key => $row ) {
		$files[$key]['attachment_date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row['attachment_date'] ) );
		$files[$key]['attachment_size'] = size_format( $row['attachment_size'] );
	}

	// backward compatibility
	$files = apply_filters( 'da_get_attachments', $files );

	return apply_filters( 'da_get_download_attachments', $files, $post_id, $args );
}

/**
 * Display download attachments for a given post.
 * 
 * @param int $post_id
 * @param array $args
 * @return mixed
 */
function da_display_download_attachments( $post_id = 0, $args = array() ) {
	$post_id = (int) (empty( $post_id ) ? get_the_ID() : $post_id);

	$options = Download_Attachments()->options;

	$defaults = array(
		'container'				 => 'div',
		'container_class'		 => 'download-attachments',
		'container_id'			 => '',
		'style'					 => isset( $options['display_style'] ) ? esc_attr( $options['display_style'] ) : 'list',
		'link_before'			 => '',
		'link_after'			 => '',
		'content_before'		 => '',
		'content_after'			 => '',
		'display_index'			 => isset( $options['frontend_columns']['index'] ) ? (int) $options['frontend_columns']['index'] : Download_Attachments()->defaults['general']['frontend_columns']['index'],
		'display_user'			 => isset( $options['frontend_columns']['author'] ) ? (int) $options['frontend_columns']['author'] : Download_Attachments()->defaults['general']['frontend_columns']['author'],
		'display_icon'			 => isset( $options['frontend_columns']['icon'] ) ? (int) $options['frontend_columns']['icon'] : Download_Attachments()->defaults['general']['frontend_columns']['icon'],
		'display_count'			 => isset( $options['frontend_columns']['downloads'] ) ? (int) $options['frontend_columns']['downloads'] : Download_Attachments()->defaults['general']['frontend_columns']['downloads'],
		'display_size'			 => isset( $options['frontend_columns']['size'] ) ? (int) $options['frontend_columns']['size'] : Download_Attachments()->defaults['general']['frontend_columns']['size'],
		'display_date'			 => isset( $options['frontend_columns']['date'] ) ? (int) $options['frontend_columns']['date'] : Download_Attachments()->defaults['general']['frontend_columns']['date'],
		'display_caption'		 => isset( $options['frontend_content']['caption'] ) ? (int) $options['frontend_content']['caption'] : Download_Attachments()->defaults['general']['frontend_content']['caption'],
		'display_description'	 => isset( $options['frontend_content']['description'] ) ? (int) $options['frontend_content']['description'] : Download_Attachments()->defaults['general']['frontend_content']['description'],
		'display_empty'			 => 0,
		'display_option_none'	 => __( 'No attachments to download', 'download-attachments' ),
		'use_desc_for_title'	 => 0,
		'exclude'				 => '',
		'include'				 => '',
		'title'					 => __( 'Download Attachments', 'download-attachments' ),
		'title_container'		 => 'p',
		'title_class'			 => 'download-title',
		'orderby'				 => 'menu_order',
		'order'					 => 'asc',
		'echo'					 => 1
	);

	$args = apply_filters( 'da_display_attachments_defaults', array_merge( $defaults, $args ), $post_id );

	$args['display_index'] = apply_filters( 'da_display_attachments_index', (int) $args['display_index'] );
	$args['display_user'] = apply_filters( 'da_display_attachments_user', (int) $args['display_user'] );
	$args['display_icon'] = apply_filters( 'da_display_attachments_icon', (int) $args['display_icon'] );
	$args['display_count'] = apply_filters( 'da_display_attachments_count', (int) $args['display_count'] );
	$args['display_size'] = apply_filters( 'da_display_attachments_size', (int) $args['display_size'] );
	$args['display_date'] = apply_filters( 'da_display_attachments_date', (int) $args['display_date'] );
	$args['display_caption'] = apply_filters( 'da_display_attachments_caption', (int) $args['display_caption'] );
	$args['display_description'] = apply_filters( 'da_display_attachments_description', (int) $args['display_description'] );
	$args['display_empty'] = apply_filters( 'da_display_attachments_empty', (int) $args['display_empty'] );
	$args['use_desc_for_title'] = (int) $args['use_desc_for_title'];
	$args['echo'] = (int) $args['echo'];
	$args['style'] = (in_array( $args['style'], array( 'list', 'table', 'none', '' ), true ) ? $args['style'] : $defaults['style']);
	$args['orderby'] = (in_array( $args['orderby'], array( 'menu_order', 'attachment_id', 'attachment_date', 'attachment_title', 'attachment_size', 'attachment_downloads' ), true ) ? $args['orderby'] : $defaults['orderby']);
	$args['order'] = (in_array( $args['order'], array( 'asc', 'desc' ), true ) ? $args['order'] : $defaults['order']);
	$args['link_before'] = trim( $args['link_before'] );
	$args['link_after'] = trim( $args['link_after'] );
	$args['display_option_none'] = (($info = trim( $args['display_option_none'] )) !== '' ? $info : $defaults['display_option_none']);

	$args['title'] = apply_filters( 'da_display_attachments_title', trim( $args['title'] ) );

	$attachments = da_get_download_attachments(
	$post_id, apply_filters(
	'da_display_attachments_args', array(
		'include'	 => $args['include'],
		'exclude'	 => $args['exclude'],
		'orderby'	 => $args['orderby'],
		'order'		 => $args['order']
	)
	)
	);

	$count = count( $attachments );
	$html = '';

	if ( ! ($args['display_empty'] === 0 && $count === 0) ) {
		//start container
		if ( $args['container'] !== '' )
			$html .= '<' . $args['container'] . ($args['container_id'] !== '' ? ' id="' . $args['container_id'] . '"' : '') . ($args['container_class'] !== '' ? ' class="' . $args['container_class'] . '"' : '') . '>';

		//title
		if ( $args['title'] !== '' )
			$html .= ($args['title'] !== '' ? '<' . $args['title_container'] . ' class="' . $args['title_class'] . '">' . $args['title'] . '</' . $args['title_container'] . '>' : '');
	}

	$html .= $args['content_before'];

	if ( $count > 0 ) {
		$i = 1;

		if ( $args['style'] === 'list' ) {
			$item_container = 'span';
			$html .= '<ul>';
		} else {
			$item_container = 'td';

			$html .= '<table class="table"><thead>';

			if ( $args['display_index'] === 1 )
				$html .= '<th class="attachment-index">#</th>';

			$html .= '<th class="attachment-title">' . __( 'File', 'download-attachments' ) . '</th>';

			if ( $args['display_caption'] === 1 || ( $args['display_description'] === 1 && $args['use_desc_for_title'] === 0 ) )
				$html .= '<th class="attachment-about">' . __( 'Description', 'download-attachments' ) . '</th>';

			if ( $args['display_date'] === 1 )
				$html .= '<th class="attachment-date">' . __( 'Date added', 'download-attachments' ) . '</th>';

			if ( $args['display_user'] === 1 )
				$html .= '<th class="attachment-user">' . __( 'Added by', 'download-attachments' ) . '</th>';

			if ( $args['display_size'] === 1 )
				$html .= '<th class="attachment-size">' . __( 'File size', 'download-attachments' ) . '</th>';

			if ( $args['display_count'] === 1 )
				$html .= '<th class="attachment-downloads">' . __( 'Downloads', 'download-attachments' ) . '</th>';

			$html .= '
				</thead><body>';
		}

		foreach ( $attachments as $attachment ) {
			if ( $attachment['attachment_exclude'] )
				continue;

			if ( $args['use_desc_for_title'] === 1 && $attachment['attachment_description'] !== '' ) {
				$title = apply_filters( 'da_display_attachment_title', $attachment['attachment_description'] );
			} else {
				$title = apply_filters( 'da_display_attachment_title', $attachment['attachment_title'] );
			}

			// start single attachment style
			if ( $args['style'] === 'list' ) {
				$html .= '<li class="' . $attachment['attachment_type'] . '">';
			} elseif ( $args['style'] === 'table' ) {
				$html .= '<tr class="' . $attachment['attachment_type'] . '">';
			} else {
				$html .= '<span class="' . $attachment['attachment_type'] . '">';
			}

			// index
			if ( $args['display_index'] === 1 )
				$html .= '<' . $item_container . ' class="attachment-index">' . $i . '</' . $item_container . '> ';

			// title
			if ( $args['style'] === 'table' )
				$html .= '<td class="attachment-title">';

			// type
			if ( $args['display_icon'] === 1 )
				$html .= '<img class="attachment-icon" src="' . $attachment['attachment_icon_url'] . '" alt="' . $attachment['attachment_type'] . '" /> ';

			// link before
			if ( $args['link_before'] !== '' )
				$html .= '<span class="attachment-link-before">' . $args['link_before'] . '</span>';

			// link
			$html .= '<a href="' . da_get_download_attachment_url( $attachment['attachment_id'] ) . '" class="attachment-link" title="' . esc_html( $title ) . '">' . $title . '</a>';

			// link after
			if ( $args['link_after'] !== '' )
				$html .= '<span class="attachment-link-after">' . $args['link_after'] . '</span>';

			if ( $args['style'] === 'table' ) {
				$html .= '</td>';
			} else {
				$html .= '<br />';
			}

			if ( $args['style'] === 'table' && ($args['display_caption'] === 1 || ($args['display_description'] === 1 && $args['use_desc_for_title'] === 0)) )
				$html .= '<td class="attachment-about">';

			// caption
			if ( $args['display_caption'] === 1 && $attachment['attachment_caption'] !== '' )
				$html .= '<span class="attachment-caption">' . $attachment['attachment_caption'] . '</span><br />';

			// description
			if ( $args['display_description'] === 1 && $args['use_desc_for_title'] === 0 && $attachment['attachment_description'] !== '' )
				$html .= '<span class="attachment-description">' . $attachment['attachment_description'] . '</span><br />';

			if ( $args['style'] === 'table' && ($args['display_caption'] === 1 || ($args['display_description'] === 1 && $args['use_desc_for_title'] === 0)) )
				$html .= '</td>';

			// date
			if ( $args['display_date'] === 1 )
				$html .= '<' . $item_container . ' class="attachment-date">' . ($args['style'] != 'table' ? '<span class="attachment-label">' . __( 'Date added', 'download-attachments' ) . ':</span> ' : '') . $attachment['attachment_date'] . '</' . $item_container . '> ';

			// user
			if ( $args['display_user'] === 1 )
				$html .= '<' . $item_container . ' class="attachment-user">' . ($args['style'] != 'table' ? '<span class="attachment-label">' . __( 'Added by', 'download-attachments' ) . ':</span> ' : '') . $attachment['attachment_user_name'] . '</' . $item_container . '> ';

			// size
			if ( $args['display_size'] === 1 )
				$html .= '<' . $item_container . ' class="attachment-size">' . ($args['style'] != 'table' ? '<span class="attachment-label">' . __( 'File size', 'download-attachments' ) . ':</span> ' : '') . $attachment['attachment_size'] . '</' . $item_container . '> ';

			// downloads
			if ( $args['display_count'] === 1 )
				$html .= '<' . $item_container . ' class="attachment-downloads">' . ($args['style'] != 'table' ? '<span class="attachment-label">' . __( 'Downloads', 'download-attachments' ) . ':</span> ' : '') . $attachment['attachment_downloads'] . '</' . $item_container . '> ';

			// end single attahcment style
			if ( $args['style'] === 'list' ) {
				$html .= '</li>';
			} elseif ( $args['style'] === 'table' ) {
				$html .= '</tr>';
			} else {
				$html .= '</span>';
			}

			$i ++;
		}

		if ( $args['style'] === 'list' ) {
			$html .= '</ul>';
		} elseif ( $args['style'] === 'table' ) {
			$html .= '</tbody></table>';
		}
	} elseif ( $args['display_empty'] === 1 ) {
		$html .= $args['display_option_none'];
	}

	$html .= $args['content_after'];

	if ( ! ($args['display_empty'] === 0 && $count === 0) && $args['container'] !== '' )
		$html .= '</' . $args['container'] . '>';

	if ( $args['echo'] === 1 ) {
		echo apply_filters( 'da_display_attachments', $html );
	} else {
		return apply_filters( 'da_display_attachments', $html );
	}
}

/**
 * Get single attachment download link.
 * 
 * @param int $attachment_id
 * @param bool $echo
 * @return mixed
 */
function da_download_attachment_link( $attachment_id = 0, $echo = false, $attr = array() ) {
	if ( get_post_type( $attachment_id ) === 'attachment' ) {

		$attr['title'] = ! empty( $attr['title'] ) ? $attr['title'] : get_the_title( $attachment_id );
		$attr['class'] = ! empty( $attr['class'] ) ? $attr['class'] : "da-download-link da-download-attachment-$attachment_id";

		$attr = apply_filters( 'da_download_attachment_link_attributes', $attr, $attachment_id );
		$attr = array_map( 'esc_attr', $attr );
		$attr_html = '';

		foreach ( $attr as $name => $value ) {
			$attr_html .= " $name=" . '"' . $value . '"';
		}

		$link = '<a href="' . da_get_download_attachment_url( $attachment_id ) . '"' . $attr_html . '">' . $attr['title'] . '</a>';
	} else {
		$link = '';
	}

	if ( $echo === true ) {
		echo apply_filters( 'da_download_attachment_link', $link );
	} else {
		return apply_filters( 'da_download_attachment_link', $link );
	}
}

/**
 * Get single attachment download url.
 * 
 * @param int $attachment_id
 * @return mixed
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
 * Encrypt attachment id for the url
 * 
 * @param int $id
 * @return string $encrypted_id
 */
function da_encrypt_attachment_id( $id ) {
	$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
	$auth_iv = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';

	// strong encryption
	if ( function_exists( 'mcrypt_encrypt' ) && defined( 'MCRYPT_BLOWFISH' ) && MCRYPT_BLOWFISH ) {
		// get max key size of the mcrypt mode
		$max_key_size = mcrypt_get_key_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
		$max_iv_size = mcrypt_get_iv_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
		
		$encrypt_key = mb_strimwidth( $auth_key, 0, $max_key_size );
		$encrypt_iv = mb_strimwidth( $auth_iv, 0, $max_iv_size );
		
		$encrypted_id = strtr( base64_encode( mcrypt_encrypt( MCRYPT_BLOWFISH, $encrypt_key, $id, MCRYPT_MODE_CBC, $encrypt_iv ) ), '+/=', '-_,' );
	// simple encryption
	} elseif ( function_exists( 'gzdeflate' ) ) {
		$encrypted_id = base64_encode( convert_uuencode( gzdeflate( $id ) ) );
	// no encryption
	} else {
		$encrypted_id = strtr( base64_encode( convert_uuencode( $id ) ), '+/=', '-_,' );
	}
	
	da_decrypt_attachment_id( $encrypted_id );

	return apply_filters( 'da_encrypt_attachment_id', $encrypted_id, $id );
}

/**
 * Decrypt attachment id for the url
 * 
 * @param int $encrypted_id
 * @return string $id
 */
function da_decrypt_attachment_id( $encrypted_id ) {
	$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
	$auth_iv = defined( 'NONCE_KEY' ) ? NONCE_KEY : '';

	// strong encryption
	if ( function_exists( 'mcrypt_decrypt' ) && defined( 'MCRYPT_BLOWFISH' ) && MCRYPT_BLOWFISH ) {
		// get max key size of the mcrypt mode
		$max_key_size = mcrypt_get_key_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
		$max_iv_size = mcrypt_get_iv_size( MCRYPT_BLOWFISH, MCRYPT_MODE_CBC );
		
		$encrypt_key = mb_strimwidth( $auth_key, 0, $max_key_size );
		$encrypt_iv = mb_strimwidth( $auth_iv, 0, $max_iv_size );

		$id = mcrypt_decrypt( MCRYPT_BLOWFISH, $encrypt_key, base64_decode( strtr( $encrypted_id, '-_,', '+/=' ) ), MCRYPT_MODE_CBC, $encrypt_iv );
	// simple encryption
	} elseif ( function_exists( 'gzinflate' ) ) {
		$id = gzinflate( convert_uudecode( base64_decode( $encrypted_id ) ) );
	// no encryption
	} else {
		$id = convert_uudecode( base64_decode( strtr( $encrypted_id, '-_,', '+/=' ) ) );
	}
	
	return apply_filters( 'da_decrypt_attachment_id', $id, $encrypted_id );
}

/**
 * Get single attachment download data.
 * 
 * @param int $attachment_id
 * @return array
 */
function da_get_download_attachment( $attachment_id = 0 ) {
	$attachment_id = ! empty( $attachment_id ) ? absint( $attachment_id ) : 0;

	// break if there's no attachment ID given
	if ( empty( $attachment_id ) )
		return false;

	// break if given ID is not for attachment
	if ( get_post_type( $attachment_id ) != 'attachment' )
		return false;

	$file = get_post( $attachment_id );
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
	$attachment['caption'] = trim( esc_attr( $file->post_excerpt ) );
	$attachment['description'] = trim( esc_attr( $file->post_content ) );
	$attachment['date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $file->post_date ) );
	$attachment['size'] = size_format( (file_exists( $filename ) ? filesize( $filename ) : 0 ) );
	$attachment['type'] = $extension;
	$attachment['downloads'] = (int) get_post_meta( $attachment_id, '_da_downloads', true );
	$attachment['url'] = da_get_download_attachment_url( $attachment_id );
	$attachment['icon_url'] = ( file_exists( DOWNLOAD_ATTACHMENTS_PATH . 'images/ext/' . $extension . '.gif' ) ? DOWNLOAD_ATTACHMENTS_URL . '/images/ext/' . $extension . '.gif' : DOWNLOAD_ATTACHMENTS_URL . '/images/ext/unknown.gif' );

	return apply_filters( 'da_get_download_attachment', $attachment, $attachment_id );
}

/**
 * Process attachment download function
 * 
 * @param int $attachment_id
 * @return mixed
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
 * @param array	$args Arguments
 * @return array Attachments
 */
if ( ! function_exists( 'da_get_most_downloaded_attachments' ) ) {

	function da_get_most_downloaded_attachments( $args = array() ) {
		$args = array_merge(
		array(
			'posts_per_page' => 10,
			'order'			 => 'desc'
		), $args
		);

		$args = apply_filters( 'da_get_most_downloaded_attachments_args', $args );

		// force important arguments
		$args['suppress_filters'] = false;
		$args['post_type'] = 'attachment';
		$args['orderby'] = 'meta_value_num';
		$args['meta_key'] = '_da_downloads';
		$args['fields'] = '';

		$args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'		 => '_da_downloads',
				'compare'	 => 'EXISTS'
			),
			array(
				'key'		 => '_da_downloads',
				'value'		 => 'something',
				'compare'	 => 'NOT EXISTS'
			)
		);

		return apply_filters( 'da_get_most_downloaded_attachments', get_posts( $args ), $args );
	}

}

/**
 * Get attachment downloads.
 *
 * @param int $post_id Attachment ID
 * @return int Number of attachment downloads
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
 * @param array	$post_id Post ID
 * @param boolean $display Whether to display or return output
 * @return mixed HTML output or void
 */
if ( ! function_exists( 'da_most_downloaded_attachments' ) ) {

	function da_most_downloaded_attachments( $args = array(), $display = true ) {
		$defaults = array(
			'number_of_posts'			 => 5,
			'link_type'					 => 'page',
			'order'						 => 'desc',
			'show_attachment_downloads'	 => true,
			'show_attachment_icon'		 => true,
			'show_attachment_excerpt'	 => false,
			'no_attachments_message'	 => __( 'No Attachments', 'download-attachments' )
		);

		$args = apply_filters( 'da_most_downloaded_attachments_args', wp_parse_args( $args, $defaults ) );

		$args['show_attachment_downloads'] = (bool) $args['show_attachment_downloads'];
		$args['show_attachment_icon'] = (bool) $args['show_attachment_icon'];
		$args['show_attachment_excerpt'] = (bool) $args['show_attachment_excerpt'];

		$attachments = da_get_most_downloaded_attachments(
		array(
			'posts_per_page' => ( isset( $args['number_of_posts'] ) ? (int) $args['number_of_posts'] : $defaults['number_of_posts'] ),
			'order'			 => ( isset( $args['order'] ) ? $args['order'] : $defaults['order'] )
		)
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