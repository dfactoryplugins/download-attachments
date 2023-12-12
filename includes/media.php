<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Media class.
 *
 * @class Download_Attachments_Media
 */
class Download_Attachments_Media {

	/**
	 * Constructor class.
	 */
	public function __construct() {
		// actions
		add_action( 'manage_media_custom_column', [ $this, 'custom_media_column_content' ], 10, 2 );
		add_action( 'attachment_submitbox_misc_actions', [ $this, 'submitbox_views' ], 1000 );
		add_action( 'edit_attachment', [ $this, 'save_post' ] );
		add_action( 'edit_attachment', [ $this, 'save_attachment_downloads' ] );

		// filters
		add_filter( 'manage_media_columns', [ $this, 'downloads_media_column_title' ] );
		add_filter( 'manage_upload_sortable_columns', [ $this, 'register_sortable_custom_column' ] );
		add_filter( 'attachment_fields_to_edit', [ $this, 'attachment_fields_to_edit' ], 10, 2 );
		add_filter( 'request', [ $this, 'sort_custom_columns' ] );
	}

	/**
	 * Filter the array of attachment fields that are displayed when editing an attachment.
	 *
	 * @param array $fields Attachment fields
	 * @param object $post Post object
	 * @return array Modified attachment fields
	 */
	function attachment_fields_to_edit( $fields, $post ) {
		if ( wp_doing_ajax() ) {
			$restrict = Download_Attachments()->options['restrict_edit_downloads'];

			if ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'da_restrict_edit_capability', 'manage_options' ) ) ) ) {
				$value = (int) get_post_meta( $post->ID, '_da_downloads', true );

				$fields['attachment_downloads'] = [
					'value'	=> $value,
					'label'	=> esc_html__( 'Downloads', 'download-attachments' ),
					'input'	=> 'html',
					'html'	=> '<input type="text" style="width: 30%;" class="text" id="attachments-' . (int) $post->ID . '-attachment_downloads" name="attachments[' . (int) $post->ID . '][attachment_downloads]" value="' . $value . '" />'
				];
			}
		}

		return $fields;
	}

	/**
	 * Save attachment downloads in modal.
	 *
	 * @param int $attachment_id
	 * @return void
	 */
	function save_attachment_downloads( $attachment_id ) {
		$restrict = Download_Attachments()->options['restrict_edit_downloads'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'da_restrict_edit_capability', 'manage_options' ) ) )
			return;

		if ( ! isset( $_POST['attachment_downloads'] ) && isset( $_REQUEST['attachments'][$attachment_id]['attachment_downloads'] ) ) {
			update_post_meta( $attachment_id, '_da_downloads', (int) $_REQUEST['attachments'][$attachment_id]['attachment_downloads'] );

			do_action( 'da_after_update_attachment_downloads_count', $attachment_id );
		}
	}

	/**
	 * Display attachments download count.
	 *
	 * @param string $column
	 * @param id $id
	 */
	public function custom_media_column_content( $column, $id ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true && $column === 'downloads_count' )
			echo (int) get_post_meta( $id, '_da_downloads', true );
	}

	/**
	 * Add new custom column to Media Library.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function downloads_media_column_title( $columns ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true ) {
			$two_last = array_slice( $columns, -2, 2, true );

			foreach ( $two_last as $column => $name ) {
				unset( $columns[$column] );
			}

			$columns['downloads_count'] = esc_html__( 'Downloads', 'download-attachments' );

			foreach ( $two_last as $column => $name ) {
				$columns[$column] = $name;
			}
		}

		return $columns;
	}

	/**
	 * Sort new custom column in Media Library.
	 *
	 * @param array $vars
	 * @return array
	 */
	public function sort_custom_columns( $vars ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true && isset( $vars['orderby'] ) && $vars['orderby'] === 'downloads' )
			$vars = array_merge(
				$vars,
				[
					'meta_key'	=> '_da_downloads',
					'orderby'	=> 'meta_value_num'
				]
			);

		return $vars;
	}

	/**
	 * Register sortable custom column in Media Library.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function register_sortable_custom_column( $columns ) {
		if ( Download_Attachments()->options['downloads_in_media_library'] === true )
			$columns['downloads_count'] = 'downloads';

		return $columns;
	}

	/**
	 * Output attachment downloads for single attachment.
	 *
	 * @global object $post
	 * @return void
	 */
	public function submitbox_views( $post ) {
		if ( empty( $post ) ) {
			global $post;
		}

		// break if current user can't edit this attachment
		if ( ! current_user_can( 'edit_post', $post->ID ) )
			return;

		// get total attachment downloads
		$downloads = (int) get_post_meta( $post->ID, '_da_downloads', true );
		?>

		<div class="misc-pub-section misc-pub-attachment-downloads" id="attachment-downloads">

			<?php wp_nonce_field( 'download_attachments_downloads', 'da_nonce' ); ?>

			<span id="attachment-downloads-display">
				<?php echo esc_html__( 'Downloads', 'download-attachments' ) . ': <strong>' . esc_html( number_format_i18n( $downloads ) ) . '</strong>'; ?>
			</span>

			<?php
			// restrict editing
			$restrict = (bool) Download_Attachments()->options['restrict_edit_downloads'];

			if ( $restrict === false || ( $restrict === true && current_user_can( apply_filters( 'da_restrict_edit_capability', 'manage_options' ) ) ) ) {
				?>
				<a href="#attachment-downloads" class="edit-attachment-downloads hide-if-no-js"><?php esc_html_e( 'Edit', 'download-attachments' ); ?></a>

				<div id="attachment-downloads-input-container" class="hide-if-js">

					<p><?php esc_html_e( 'Adjust the downloads count for this attachment.', 'download-attachments' ); ?></p>
					<input type="hidden" id="attachment-downloads-current" value="<?php echo $downloads; ?>" />
					<input type="number" min="0" name="attachment_downloads" id="attachment-downloads-input" value="<?php echo $downloads; ?>"/><br />
					<p>
						<a href="#attachment-downloads" class="save-attachment-downloads hide-if-no-js button"><?php esc_html_e( 'OK', 'download-attachments' ); ?></a>
						<a href="#attachment-downloads" class="cancel-attachment-downloads hide-if-no-js"><?php esc_html_e( 'Cancel', 'download-attachments' ); ?></a>
					</p>

				</div>
				<?php
			}
			?>

		</div>
		<?php
	}

	/**
	 * Save attachment downloads data.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function save_post( $post_id ) {
		// break if doing autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		// break if current user can't edit this post
		if ( ! current_user_can( 'edit_post', $post_id ) )
			return;

		// is post views set
		if ( ! isset( $_POST['attachment_downloads'] ) )
			return;

		// break if views editing is restricted
		$restrict = (bool) Download_Attachments()->options['restrict_edit_downloads'];

		if ( $restrict === true && ! current_user_can( apply_filters( 'da_restrict_edit_capability', 'manage_options' ) ) )
			return;

		// validate data
		if ( ! isset( $_POST['da_nonce'] ) || ! wp_verify_nonce( $_POST['da_nonce'], 'download_attachments_downloads' ) )
			return;

		update_post_meta( $post_id, '_da_downloads', (int) $_POST['attachment_downloads'] );

		do_action( 'da_after_update_attachment_downloads_count', $post_id );
	}
}

new Download_Attachments_Media();