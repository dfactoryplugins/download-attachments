<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Metabox class.
 *
 * @class Download_Attachments_Metabox
 */
class Download_Attachments_Metabox {

	/**
	 * Constructor class.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'add_meta_boxes', [ $this, 'add_download_meta_box' ] );
		add_action( 'delete_attachment', [ $this, 'remove_attachment' ] );
		add_action( 'wp_ajax_da-save-files', [ $this, 'ajax_save_files' ] );
		add_action( 'wp_ajax_da-new-file', [ $this, 'ajax_update_attachments' ] );
		add_action( 'save_post', [ $this, 'save_attachments_data' ], 10, 2 );
	}

	/**
	 * Update files and posts ids when removing.
	 *
	 * @param int $attachment_id
	 * @return void
	 */
	public function remove_attachment( $attachment_id ) {
		$attachment_id = (int) $attachment_id;

		if ( ( $files_meta = get_post_meta( $attachment_id, '_da_posts', true ) ) !== '' && is_array( $files_meta ) && ! empty( $files_meta ) ) {
			foreach ( $files_meta as $id ) {
				$post_id = (int) $id;

				if ( ( $files = get_post_meta( $post_id, '_da_attachments', true ) ) !== '' && is_array( $files ) && ! empty( $files ) ) {
					foreach ( $files as $key => $file ) {
						if ( (int) $file['file_id'] === $attachment_id ) {
							unset( $files[$key] );
							break;
						}
					}

					update_post_meta( $post_id, '_da_attachments', $files );
				}
			}
		}
	}

	/**
	 * Save attachments attached to a post.
	 *
	 * @param int $post_id
	 * @param object $post
	 * @return void
	 */
	public function save_attachments_data( $post_id, $post ) {
		// get post types
		$post_types = Download_Attachments()->options['post_types'];

		// valid post type?
		if ( ! ( array_key_exists( $post->post_type, $post_types ) && $post_types[$post->post_type] ) || ! current_user_can( 'manage_download_attachments' ) )
			return;

		// inline edit?
		if ( isset( $_POST['_inline_edit'] ) && wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) )
			return;

		// autosave?
		if ( wp_is_post_autosave( $post_id ) )
			return;

		// revision?
		if ( wp_is_post_revision( $post_id ) )
			return;

		// no data at all?
		if ( ! array_key_exists( 'da_attachment_data_exists', $_POST ) )
			return;

		// valid data?
		if ( array_key_exists( 'da_attachment_data', $_POST ) && is_array( $_POST['da_attachment_data'] ) ) {
			$attachments = [];

			foreach ( $_POST['da_attachment_data'] as $attachment ) {
				$attachments[] = [ (int) $attachment['id'], (int) array_key_exists( 'exclude', $attachment ) ];
			}

			unset( $_POST['da_attachment_data'] );

			$_POST['attachment_data'] = $attachments;
		} else
			$_POST['attachment_data'] = [ 'empty' ];

		$this->save_files( $post_id, $_POST );
	}

	/**
	 * Save attachments using AJAX.
	 *
	 * @return void
	 */
	public function ajax_save_files() {
		if ( isset( $_POST['danonce'], $_POST['post_id'], $_POST['attachment_data'], $_POST['action'] ) && ( $post_id = (int) $_POST['post_id'] ) > 0 && $_POST['action'] === 'da-save-files' && is_array( $_POST['attachment_data'] ) && current_user_can( 'manage_download_attachments' ) && wp_verify_nonce( $_POST['danonce'], 'da-save-files-nonce-' . $post_id ) !== false ) {
			$this->save_files( $post_id, $_POST );

			echo json_encode( [ 'status' => 'OK', 'info' => '' ] );
		} else
			echo json_encode( [ 'status' => 'ERROR', 'info' => __( 'Unexpected error occured. Please refresh the page and try again.', 'download-attachments' ) ] );

		exit;
	}

	/**
	 * Save attachments.
	 *
	 * @param int $post_id
	 * @param array $post
	 * @return void
	 */
	public function save_files( $post_id, $post ) {
		$new_files = [];

		// get already added attachments
		$files = get_post_meta( $post_id, '_da_attachments', true );

		// empty array?
		if ( isset( $post['attachment_data'][0] ) && $post['attachment_data'][0] === 'empty' )
			$post['attachment_data'] = [];

		// any data?
		if ( ! empty( $post['attachment_data'] ) ) {
			// get current user id
			$current_user_id = get_current_user_id();

			// create array of new files
			foreach ( $post['attachment_data'] as $attachment ) {
				$att_id = (int) $attachment[0];

				// is it attachment?
				if ( get_post_type( $att_id ) !== 'attachment' )
					continue;

				// old file is new file
				if ( isset( $files[$att_id] ) ) {
					$new_files[$att_id] = $files[$att_id];
					$new_files[$att_id]['file_exclude'] = (bool) (int) $attachment[1];
				// new file
				} else {
					$new_files[$att_id] = [
						'file_id'		=> $att_id,
						'file_date'		=> current_time( 'mysql' ),
						'file_exclude'	=> (bool) (int) $attachment[1],
						'file_user_id'	=> $current_user_id
					];

					// check whether any files are already attached to this post
					if ( ( $files_meta = get_post_meta( $att_id, '_da_posts', true ) ) !== '' && is_array( $files_meta ) && ! empty( $files_meta ) ) {
						$files_meta[] = $post_id;

						update_post_meta( $att_id, '_da_posts', array_unique( $files_meta ) );
					} else
						update_post_meta( $att_id, '_da_posts', [ $post_id ] );

					// first time?
					if ( get_post_meta( $att_id, '_da_downloads', true ) === '' )
						update_post_meta( $att_id, '_da_downloads', 0 );
				}
			}
		}

		// check whether old files were removed
		if ( ! empty( $files ) ) {
			$keys = array_keys( $new_files );

			foreach ( $files as $att_id => $file ) {
				// file no longer exists on the list
				if ( ! in_array( $att_id, $keys, true ) ) {
					if ( ( $files_meta = get_post_meta( $att_id, '_da_posts', true ) ) !== '' && is_array( $files_meta ) && ! empty( $files_meta ) ) {
						foreach ( $files_meta as $key => $post_file_id ) {
							if ( $post_file_id === $post_id ) {
								unset( $files_meta[$key] );
								break;
							}
						}

						// update post ids of the attached file
						update_post_meta( $att_id, '_da_posts', $files_meta );
					}
				}
			}
		}

		update_post_meta( $post_id, '_da_attachments', $new_files );
	}

	/**
	 * Update attachments using AJAX.
	 *
	 * @return void
	 */
	public function ajax_update_attachments() {
		if ( isset( $_POST['danonce'], $_POST['post_id'], $_POST['attachments_ids'], $_POST['action'] ) && ( $post_id = (int) $_POST['post_id'] ) > 0 && $_POST['action'] === 'da-new-file' && is_array( $_POST['attachments_ids'] ) && ! empty( $_POST['attachments_ids'] ) && current_user_can( 'manage_download_attachments' ) && wp_verify_nonce( $_POST['danonce'], 'da-add-file-nonce-' . $post_id ) !== false ) {
			$rows = [];

			if ( isset( $_POST['attachments_ids'][0] ) && $_POST['attachments_ids'][0] === 'empty' )
				$_POST['attachments_ids'] = [];

			if ( ! empty( $_POST['attachments_ids'] ) ) {
				$attachments = array_unique( array_map( 'intval', $_POST['attachments_ids'] ) );

				if ( ! empty( $attachments ) ) {
					$files = $this->prepare_files_data( $post_id, $attachments );

					foreach ( $attachments as $attachment_id ) {
						// is it attachment?
						if ( get_post_type( $attachment_id ) !== 'attachment' )
							continue;

						$rows[] = $this->get_table_row( $post_id, true, $files[$attachment_id] );
					}
				}
			}

			echo json_encode( [ 'status' => 'OK', 'files' => $rows, 'info' => '' ] );
		} else
			echo json_encode( [ 'status' => 'ERROR', 'files' => [], 'info' => esc_html__( 'Unexpected error occured. Please refresh the page and try again.', 'download-attachments' ) ] );

		exit;
	}

	/**
	 * Add metabox.
	 *
	 * @return void
	 */
	public function add_download_meta_box() {
		if ( ! current_user_can( 'manage_download_attachments' ) )
			return;

		// filterable metabox settings
		$context = apply_filters( 'da_metabox_context', 'normal' );
		$priority = apply_filters( 'da_metabox_priority', 'high' );

		foreach ( Download_Attachments()->options['post_types'] as $post_type => $bool ) {
			if ( $bool === true ) {
				if ( isset( $_GET['post'] ) )
					$post_id = (int) $_GET['post'];
				elseif ( isset( $_POST['post_ID'] ) )
					$post_id = (int) $_POST['post_ID'];

				if ( ! isset( $post_id ) )
					$post_id = false;

				if ( apply_filters( 'da_metabox_limit', true, $post_id ) )
					add_meta_box( 'download_attachments_metabox', esc_html__( 'Attachments', 'download-attachments' ), [ $this, 'display_metabox' ], $post_type, $context, $priority );
			}
		}
	}

	/**
	 * Display metabox.
	 *
	 * @param object $post
	 * @return void
	 */
	public function display_metabox( $post ) {
		echo '
		<div id="download-attachments">
			<input type="hidden" name="da_attachment_data_exists" value="true">
			<p class="da-save-files">
				<input type="button" class="button button-primary" value="' . esc_attr__( 'Save', 'download-attachments' ) . '"/>
			</p>
			<p id="da-add-new-file">
				<input type="button" class="button button-secondary" value="' . esc_attr__( 'Add new attachment', 'download-attachments' ) . '"/>
			</p>
			<p id="da-spinner"></p>
			<table id="da-files" class="widefat" rel="' . (int) $post->ID . '">
				<thead>
					<tr>
						<th class="file-drag"></th>';

			foreach ( Download_Attachments()->columns as $column => $name ) {
				if (  $column === 'exclude' || ( ! in_array( $column, [ 'index', 'icon' ] ) && isset( Download_Attachments()->options['backend_columns'][$column] ) && Download_Attachments()->options['backend_columns'][$column] === true ) ) {
					echo '
						<th class="file-' . esc_attr( $column ) . '">' . esc_html( $name ) . '</th>';
				}
			}

			echo '
						<th class="file-actions">' . esc_html__( 'Actions', 'download-attachments' ) . '</th>
					</tr>
				</thead>';

			$files = $this->prepare_files_data( $post->ID );

			if ( ! empty( $files ) ) {
				echo '
				<tbody>';

				foreach ( $files as $file ) {
					echo $this->get_table_row( $post->ID, false, $file );
				}

				echo '
				</tbody>';
			} else {
				$columns = 0;

				foreach ( Download_Attachments()->options['backend_columns'] as $column => $bool ) {
					if ( $bool )
						$columns++;
				}

				echo '
				<tbody>
					<tr id="da-info">
					<td colspan="' . (int) ( $columns + 3 ) . '">' . esc_html__( 'No attachments added yet.', 'download-attachments' ) . '</td>
					</tr>
				</tbody>';
			}

			echo '
			</table>
			<p class="da-save-files">
				<input type="button" class="button button-primary" value="' . esc_attr__( 'Save', 'download-attachments' ) . '"/>
			</p>
			<br class="clear"/>
			<p id="da-infobox" style="display: none;"></p>
		</div>';
	}

	/**
	 * Prepare attachments data for output.
	 *
	 * @param int $post_id
	 * @param array $file_ids
	 * @return array
	 */
	public function prepare_files_data( $post_id = 0, $file_ids = [] ) {
		$post_id = (int) $post_id;
		$files = [];

		if ( ( $files_meta = get_post_meta( $post_id, '_da_attachments', true ) ) !== '' && is_array( $files_meta ) && ! empty( $files_meta ) ) {
			$empty_file_ids = empty( $file_ids );

			foreach ( $files_meta as $file ) {
				if ( ! $empty_file_ids && ! in_array( $file['file_id'], $file_ids, true ) )
					continue;

				$files[$file['file_id']] = [
					'file_id'			=> $file['file_id'],
					'file_date'			=> $file['file_date'],
					'file_exclude'		=> '<input class="exclude-attachment" id="att-exclude-' . (int) $file['file_id'] . '" type="checkbox" name="da_attachment_data[' . (int) $file['file_id'] . '][exclude]" value="true" ' . checked( ( isset( $file['file_exclude'] ) && $file['file_exclude'] === true ), true, false ) . '/><input type="hidden" name="da_attachment_data[' . (int) $file['file_id'] . '][id]" value="' . (int) $file['file_id'] . '" />',
					'file_user_id'		=> $file['file_user_id'],
					'file_downloads'	=> (int) get_post_meta( $file['file_id'], '_da_downloads', true )
				];
			}
		}

		$new_file_ids = array_unique( array_merge( array_keys( $files ), $file_ids ) );

		if ( ! empty( $new_file_ids ) ) {
			$files_data = get_posts(
				[
					'include'			=> $new_file_ids,
					'posts_per_page'	=> -1,
					'offset'			=> 0,
					'orderby'			=> 'post_date',
					'order'				=> 'DESC',
					'post_type'			=> 'attachment',
					'post_status'		=> 'any'
				]
			);

			if ( ! empty( $files_data ) ) {
				$user_id = get_current_user_id();
				$date = current_time( 'timestamp' );

				foreach ( $files_data as $file ) {
					$title = '';
					$real_title = $file->post_title;
					$filename = get_attached_file( $file->ID );
					$filetype = wp_check_filetype( $filename );

					if ( Download_Attachments()->options['backend_content']['caption'] === true )
						$title .= '<span class="caption">' . esc_attr( $file->post_excerpt ) . '</span>';

					if ( Download_Attachments()->options['backend_content']['description'] === true )
						$title .= '<span class="description">' . esc_attr( $file->post_content ) . '</span>';

					// old file, was on the list already
					if ( isset( $files[$file->ID] ) ) {
						$display_name = get_the_author_meta( 'display_name', $files[$file->ID]['file_user_id'] );
						$full_name = get_avatar( $files[$file->ID]['file_user_id'], 16 ) . ' ' . esc_attr( $display_name );
						$timestamp = strtotime( $files[$file->ID]['file_date'] );

						$files[$file->ID]['file_date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp, false );
						$files[$file->ID]['file_date_timestamp'] = $timestamp;
						$files[$file->ID]['file_author'] = ( current_user_can( 'edit_users' ) ? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $files[$file->ID]['file_user_id'] ) ) . '">' . $full_name . '</a>' : $full_name );
						$files[$file->ID]['file_author_string'] = $display_name;
					} else {
						$display_name = get_the_author_meta( 'display_name', $user_id );
						$full_name = get_avatar( $user_id, 16 ) . ' ' . esc_attr( $display_name );

						$files[$file->ID]['file_id'] = $file->ID;
						$files[$file->ID]['file_exclude'] = '<input class="exclude-attachment" id="att-exclude-' . (int) $file->ID . '" type="checkbox" name="da_attachment_data[' . (int) $file->ID . '][exclude]" value="true"/><input type="hidden" name="da_attachment_data[' . (int) $file->ID . '][id]" value="' . (int) $file->ID . '" />';
						$files[$file->ID]['file_user_id'] = $user_id;
						$files[$file->ID]['file_downloads'] = 0;
						$files[$file->ID]['file_date'] = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $date, false );
						$files[$file->ID]['file_date_timestamp'] = $date;
						$files[$file->ID]['file_author'] = ( current_user_can( 'edit_users' ) ? '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ) . '">' . $full_name . '</a>' : $full_name );
						$files[$file->ID]['file_author_string'] = $display_name;
					}

					if ( file_exists( $filename ) ) {
						$size_bytes = filesize( $filename );
						$size = size_format( $size_bytes );
					} else {
						$size_bytes = 0;
						$size = '0 B';
					}

					$files[$file->ID]['file_size'] = $size;
					$files[$file->ID]['file_size_bytes'] = $size_bytes;
					$files[$file->ID]['file_type'] = ( $filetype['ext'] === 'jpeg' ? 'jpg' : $filetype['ext'] );
					$files[$file->ID]['file_title'] = '<p><a target="_blank" href="' . esc_url( wp_get_attachment_url( $file->ID ) ) . '">' . esc_html( $real_title ) . '</a>' . $title . '</p>';
					$files[$file->ID]['file_title_string'] = $real_title;
				}
			}
		}

		return $files;
	}

	/**
	 * Display table's row.
	 *
	 * @param int $post_id
	 * @param bool $ajax
	 * @param array $file
	 * @return mixed
	 */
	public function get_table_row( $post_id = 0, $ajax = false, $file = [] ) {
		$html = '<tr' . ( $ajax === true ? ' style="display: none;"' : '' ) . ' id="att-' . (int) $file['file_id'] . '"><td class="file-drag"><span class="dashicons dashicons-menu"></span></td>';

		foreach ( Download_Attachments()->columns as $column => $name ) {
			if ( $column === 'exclude' || ( ! in_array( $column, [ 'index', 'icon' ], true ) && isset( Download_Attachments()->options['backend_columns'][$column] ) && Download_Attachments()->options['backend_columns'][$column] === true ) ) {
				// whether value should be escaped
				$esc_value = true;

				if ( $column === 'size' )
					$data_order_value = $file['file_size_bytes'];
				elseif ( $column === 'date' )
					$data_order_value = (int) $file['file_date_timestamp'];
				elseif ( $column === 'author' ) {
					$data_order_value = $file['file_author_string'];

					// author value is already escaped
					$esc_value = false;
				} elseif ( $column === 'title' ) {
					$data_order_value = $file['file_title_string'];

					// title value is already escaped
					$esc_value = false;
				} elseif ( $column === 'exclude' ) {
					// exclude value is already escaped
					$esc_value = false;
				} else
					$data_order_value = '';

				$html .= '<td class="file-' . esc_attr( $column ) . '"' . ( $data_order_value !== '' ? ' data-order="' . esc_attr( $data_order_value ) . '"' : '' ) . '>' . ( $esc_value ? esc_html( $file['file_' . $column] ) : $file['file_' . $column] ) . '</td>';
			}
		}

		$html .= '<td class="file-actions">';

		if ( current_user_can( 'edit_post', $file['file_id'] ) )
			$html .= '<a href="' . ( Download_Attachments()->options['attachment_link'] === 'modal' ? '#' : esc_url( admin_url( 'post.php?post=' . (int) $file['file_id'] . '&action=edit' ) ) ) . '"><span title="' . esc_attr__( 'Edit', 'download-attachments' ) . '" class="dashicons dashicons-edit da-edit-file"></span></a> ';
		else
			$html .= '<span title="' . esc_attr__( 'Edit', 'download-attachments' ) . '" class="dashicons dashicons-edit disabled"></span> ';

		$html .= '<a href="#"><span title="' . esc_attr__( 'Remove', 'download-attachments' ) . '" class="dashicons dashicons-trash da-remove-file remove"></span></a></td></tr>';

		return $html;
	}
}

new Download_Attachments_Metabox();