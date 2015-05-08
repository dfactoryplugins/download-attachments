<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Get download attachments data for a given post
 * 
 * @param 	int $post_id
 * @param	array $args
 * @return 	array
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
					$extension = ($filetype['ext'] === 'jpeg' ? 'jpg' : $filetype['ext']);

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
 * Display download attachments for a given post
 * 
 * @param 	int $post_id
 * @param	array $args
 * @return 	mixed
 */
function da_display_download_attachments( $post_id = 0, $args = array() ) {
	$post_id = (int) (empty( $post_id ) ? get_the_ID() : $post_id);

	$options = get_option( 'download_attachments_general' );

	$defaults = array(
		'container'				 => 'div',
		'container_class'		 => 'download-attachments',
		'container_id'			 => '',
		'style'					 => isset( $options['display_style'] ) ? esc_attr( $options['display_style'] ) : 'list',
		'link_before'			 => '',
		'link_after'			 => '',
		'content_before'		 => '',
		'content_after'			 => '',
		'display_index'			 => isset( $options['frontend_columns']['index'] ) ? (int) $options['frontend_columns']['index'] : false,
		'display_user'			 => (int) $options['frontend_columns']['author'],
		'display_icon'			 => (int) $options['frontend_columns']['icon'],
		'display_count'			 => (int) $options['frontend_columns']['downloads'],
		'display_size'			 => (int) $options['frontend_columns']['size'],
		'display_date'			 => (int) $options['frontend_columns']['date'],
		'display_caption'		 => (int) $options['frontend_content']['caption'],
		'display_description'	 => (int) $options['frontend_content']['description'],
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

			if ( $args['display_caption'] === 1 || ($args['display_description'] === 1 && $args['use_desc_for_title'] === 0) )
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
			$html .= '<a href="' . ($options['pretty_urls'] === true ? home_url( '/' . $options['download_link'] . '/' . $attachment['attachment_id'] . '/' ) : plugins_url( 'download-attachments/includes/download.php?id=' . $attachment['attachment_id'] )) . '" class="attachment-link" title="' . $title . '">' . $title . '</a>';

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
 * Get single attachment download link
 * 
 * @param 	int $attachment_id
 * @param	bool $echo
 * @return 	mixed
 */
function da_download_attachment_link( $attachment_id = 0, $echo = false ) {
	if ( get_post_type( $attachment_id ) === 'attachment' ) {
		$options = get_option( 'download_attachments_general' );
		$title = get_the_title( $attachment_id );

		$link = '<a href="' . ($options['pretty_urls'] === true ? home_url( '/' . $options['download_link'] . '/' . $attachment_id . '/' ) : plugins_url( 'download-attachments/includes/download.php?id=' . $attachment_id )) . '" title="' . $title . '">' . $title . '</a>';
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
 * Get single attachment download url
 * 
 * @param 	int $attachment_id
 * @return 	mixed
 */
function da_download_attachment_url( $attachment_id = 0 ) {
	if ( get_post_type( $attachment_id ) === 'attachment' ) {
		$options = get_option( 'download_attachments_general' );

		return ($options['pretty_urls'] === true ? home_url( '/' . $options['download_link'] . '/' . $attachment_id . '/' ) : plugins_url( 'download-attachments/includes/download.php?id=' . $attachment_id ));
	} else {
		return '';
	}
}

/**
 * Get single attachment download data
 * 
 * @param 	int $attachment_id
 * @return 	array
 */
function da_get_download_attachment( $attachment_id = 0 ) {
	$attachment_id = ! empty( $attachment_id ) ? absint( $attachment_id ) : 0;

	// break if there's no attachment ID given
	if ( empty( $attachment_id ) )
		return false;

	// break if given ID is not for attachment
	if ( get_post_type( $attachment_id ) != 'attachment' )
		return false;

	$options = get_option( 'download_attachments_general' );

	$file = get_post( $attachment_id );
	$filename = get_attached_file( $attachment_id );
	$filetype = wp_check_filetype( $filename );
	$extension = ($filetype['ext'] === 'jpeg' ? 'jpg' : $filetype['ext']);

	$attachment['title'] = get_the_title( $attachment_id );
	$attachment['caption'] = trim( esc_attr( $file->post_excerpt ) );
	$attachment['description'] = trim( esc_attr( $file->post_content ) );
	$attachment['date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $file->post_date ) );
	$attachment['size'] = size_format( (file_exists( $filename ) ? filesize( $filename ) : 0 ) );
	$attachment['type'] = $extension;
	$attachment['downloads'] = (int) get_post_meta( $attachment_id, '_da_downloads', true );
	$attachment['url'] = ($options['pretty_urls'] === true ? home_url( '/' . $options['download_link'] . '/' . $attachment_id . '/' ) : plugins_url( 'download-attachments/includes/download.php?id=' . $attachment_id ));
	$attachment['icon_url'] = ( file_exists( DOWNLOAD_ATTACHMENTS_PATH . 'images/ext/' . $extension . '.gif' ) ? DOWNLOAD_ATTACHMENTS_URL . '/images/ext/' . $extension . '.gif' : DOWNLOAD_ATTACHMENTS_URL . '/images/ext/unknown.gif' );

	return apply_filters( 'da_get_download_attachment', $attachment, $attachment_id );
}

/**
 * Process attachment download function
 * 
 * @param 	int $attachment_id
 * @return 	mixed
 */
function da_download_attachment( $attachment_id = 0 ) {
	if ( get_post_type( $attachment_id ) === 'attachment' ) {
		$uploads = wp_upload_dir();
		$attachment = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$filepath = $uploads['basedir'] . '/' . $attachment;

		$filepath = apply_filters( 'da_download_attachment_filepath', $filepath, $attachment_id );

		if ( ! file_exists( $filepath ) || ! is_readable( $filepath ) )
			return false;

		// if filename contains folders
		if ( ( $position = strrpos( $attachment, '/', 0 ) ) !== false ) {
			$filename = substr( $attachment, $position + 1 );
		} else {
			$filename = $attachment;
		}

		if ( ini_get( 'zlib.output_compression' ) )
			ini_set( 'zlib.output_compression', 0 );

		header( 'Content-Type: application/download' );
		header( 'Content-Disposition: attachment; filename=' . rawurldecode( $filename ) );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Accept-Ranges: bytes' );
		header( 'Cache-control: private' );
		header( 'Pragma: private' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );
		header( 'Content-Length: ' . filesize( $filepath ) );

		if ( $filepath = fopen( $filepath, 'rb' ) ) {
			while ( ! feof( $filepath ) && ( ! connection_aborted()) ) {
				echo fread( $filepath, 1048576 );
				flush();
			}

			fclose( $filepath );
		} else {
			return false;
		}

		update_post_meta( $attachment_id, '_da_downloads', (int) get_post_meta( $attachment_id, '_da_downloads', true ) + 1 );

		exit;
	} else {
		return false;
	}
}
