<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Widgets class.
 * 
 * @class Download_Attachments_Widgets
 */
class Download_Attachments_Widgets {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// actions
		add_action( 'widgets_init', [ $this, 'register_widgets' ] );
	}

	/**
	 * Register widgets.
	 *
	 * @return void
	 */
	public function register_widgets() {
		register_widget( 'Download_Attachments_List_Widget' );
	}

}

/**
 * Download_Attachments_List_Widget class.
 * 
 * @class Download_Attachments_List_Widget
 */
class Download_Attachments_List_Widget extends WP_Widget {

	private $da_defaults;
	private $da_attached_to_types;
	private $da_orderby_types;
	private $da_order_types;
	private $da_link_types;
	private $da_style_types;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			'Download_Attachments_List_Widget', __( 'Attachments', 'download-attachments' ),
			[
				'description'	=> __( 'Displays a list of attachments.', 'download-attachments' ),
				'classname'		=> 'widget_download_attachments_list'
			]
		);

		$this->da_defaults = [
			'title'						=> __( 'Attachments', 'download-attachments' ),
			'attached_to'				=> '',
			'style'						=> 'posts',
			'link_type'					=> 'url',
			'orderby'					=> 'downloads',
			'order'						=> 'desc',
			'number_of_posts'			=> 5,
			'no_attachments_message'	=> __( 'No attachments found', 'download-attachments' ), // no_attachments_message
			'display_index'				=> false,
			'display_user'				=> false,
			'display_icon'				=> true, // show_attachment_icon
			'display_count'				=> true, // show_attachment_downloads
			'display_size'				=> false,
			'display_date'				=> false,
			'display_caption'			=> false, // show_attachment_excerpt
			'display_description'		=> false
		];
		
		$this->da_attached_to_types = [
			''			=> __( 'All posts', 'download-attachments' ),
			'current'	=> __( 'Current post', 'download-attachments' )
		];

		$this->da_orderby_types = [
			'downloads'		=> __( 'Downloads count', 'download-attachments' ),
			'menu_order'	=> __( 'Menu order', 'download-attachments' ),
			'date'			=> __( 'Date', 'download-attachments' ),
			'title'			=> __( 'Title', 'download-attachments' ),
			'size'			=> __( 'File size', 'download-attachments' ),
			'ID'			=> __( 'ID', 'download-attachments' )
		];

		$this->da_order_types = [
			'asc'	=> __( 'Ascending', 'download-attachments' ),
			'desc'	=> __( 'Descending', 'download-attachments' )
		];

		$this->da_link_types = [
			'page'	=> __( 'Attachment page', 'download-attachments' ),
			'url'	=> __( 'Download URL', 'download-attachments' )
		];

		$this->da_style_types = Download_Attachments()->display_styles;
	}

	/**
	 * Display widget function.
	 *
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$html = '';
		$instance['title'] = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );
		$instance['echo'] = false;
		// display all posts on 0
		$instance['number_of_posts'] = $instance['number_of_posts'] == 0 ? -1 : $instance['number_of_posts'];

		$attachments_html = trim( da_display_download_attachments( ( ! empty( $instance['attached_to'] ) ? 0 : null ), $instance ) );

		// hide widgets if no attachments for the current post
		if ( ! empty( $attachments_html ) ) {
			$html = $args['before_widget'] . ( ! empty( $instance['title'] ) ? $args['before_title'] . $instance['title'] . $args['after_title'] : '');
			$html .= $attachments_html;
			$html .= $args['after_widget'];
		}

		echo $html;
	}

	/**
	 * Admin widget.
	 *
	 * @param array $instance
	 * @return void
	 */
	public function form( $instance ) {
		$html = '
		<p>
			<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'download-attachments' ) . ':</label>
			<input id="' . $this->get_field_id( 'title' ) . '" class="widefat" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( isset( $instance['title'] ) ? $instance['title'] : $this->da_defaults['title']  ) . '" />
		</p>
		<p>
			<label for="' . $this->get_field_id( 'attached_to' ) . '">' . __( 'Display attached to', 'download-attachments' ) . ':</label>
			<select id="' . $this->get_field_id( 'attached_to' ) . '" name="' . $this->get_field_name( 'attached_to' ) . '">';

		foreach ( $this->da_attached_to_types as $type => $name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, ( isset( $instance['attached_to'] ) ? $instance['attached_to'] : $this->da_defaults['attached_to'] ), false ) . '>' . $name . '</option>';
		}

		$html .= '
			</select>
		</p>
		<p>
			<label for="' . $this->get_field_id( 'style' ) . '">' . __( 'Display style', 'download-attachments' ) . ':</label>
			<select id="' . $this->get_field_id( 'style' ) . '" name="' . $this->get_field_name( 'style' ) . '">';

		foreach ( $this->da_style_types as $type => $name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, ( isset( $instance['style'] ) ? $instance['style'] : $this->da_defaults['style'] ), false ) . '>' . $name . '</option>';
		}

		$html .= '
			</select>
		</p>
		<p>
			<label for="' . $this->get_field_id( 'link_type' ) . '">' . __( 'Link to', 'download-attachments' ) . ':</label>
			<select id="' . $this->get_field_id( 'link_type' ) . '" name="' . $this->get_field_name( 'link_type' ) . '">';

		foreach ( $this->da_link_types as $type => $name ) {
			$html .= '
			<option value="' . esc_attr( $type ) . '" ' . selected( $type, ( isset( $instance['link_type'] ) ? $instance['link_type'] : $this->da_defaults['link_type'] ), false ) . '>' . $name . '</option>';
		}

		$html .= '
			</select>
		</p>
		<p>
			<label for="' . $this->get_field_id( 'orderby' ) . '">' . __( 'Orderby', 'download-attachments' ) . ':</label>
			<select id="' . $this->get_field_id( 'orderby' ) . '" name="' . $this->get_field_name( 'orderby' ) . '">';

		foreach ( $this->da_orderby_types as $id => $name ) {
			$html .= '
			<option value="' . esc_attr( $id ) . '" ' . selected( $id, ( isset( $instance['orderby'] ) ? $instance['orderby'] : $this->da_defaults['orderby'] ), false ) . '>' . $name . '</option>';
		}

		$html .= '
			</select>
		</p>
		<p>
			<label for="' . $this->get_field_id( 'order' ) . '">' . __( 'Order', 'download-attachments' ) . ':</label>
			<select id="' . $this->get_field_id( 'order' ) . '" name="' . $this->get_field_name( 'order' ) . '">';

		foreach ( $this->da_order_types as $id => $name ) {
			$html .= '
			<option value="' . esc_attr( $id ) . '" ' . selected( $id, ( isset( $instance['order'] ) ? $instance['order'] : $this->da_defaults['order'] ), false ) . '>' . $name . '</option>';
		}

		$html .= '
			</select>
		</p>
		<p>
			<label for="' . $this->get_field_id( 'number_of_posts' ) . '">' . __( 'Number of attachments to show', 'download-attachments' ) . ':</label>
			<input id="' . $this->get_field_id( 'number_of_posts' ) . '" class="tiny-text" step="1" min="0" name="' . $this->get_field_name( 'number_of_posts' ) . '" type="number" size="3" value="' . esc_attr( isset( $instance['number_of_posts'] ) ? $instance['number_of_posts'] : $this->da_defaults['number_of_posts']  ) . '" />
		</p>
		<p>
			<label for="' . $this->get_field_id( 'no_attachments_message' ) . '">' . __( 'No attachments message', 'download-attachments' ) . ':</label>
			<input id="' . $this->get_field_id( 'no_attachments_message' ) . '" class="widefat" type="text" name="' . $this->get_field_name( 'no_attachments_message' ) . '" value="' . esc_attr( isset( $instance['no_attachments_message'] ) ? $instance['no_attachments_message'] : $this->da_defaults['no_attachments_message']  ) . '" />
		</p>
		<p>
			<input id="' . $this->get_field_id( 'display_index' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_index' ) . '" ' . checked( true, isset( $instance['display_index'] ) ? $instance['display_index'] : $this->da_defaults['display_index'], false ) . ' /> <label for="' . $this->get_field_id( 'display_index' ) . '">' . __( 'Display index?', 'download-attachments' ) . '</label><br />
			<input id="' . $this->get_field_id( 'display_user' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_user' ) . '" ' . checked( true, isset( $instance['display_user'] ) ? $instance['display_user'] : $this->da_defaults['display_user'], false ) . ' /> <label for="' . $this->get_field_id( 'display_user' ) . '">' . __( 'Display attachment user?', 'download-attachments' ) . '</label><br />
			<input id="' . $this->get_field_id( 'display_icon' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_icon' ) . '" ' . checked( true, isset( $instance['display_icon'] ) ? $instance['display_icon'] : $this->da_defaults['display_icon'], false ) . ' /> <label for="' . $this->get_field_id( 'display_icon' ) . '">' . __( 'Display attachment icon?', 'download-attachments' ) . '</label><br />
			<input id="' . $this->get_field_id( 'display_count' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_count' ) . '" ' . checked( true, (isset( $instance['display_count'] ) ? $instance['display_count'] : $this->da_defaults['display_count'] ), false ) . ' /> <label for="' . $this->get_field_id( 'display_count' ) . '">' . __( 'Display attachment downloads?', 'download-attachments' ) . '</label><br />
			<input id="' . $this->get_field_id( 'display_size' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_size' ) . '" ' . checked( true, isset( $instance['display_size'] ) ? $instance['display_size'] : $this->da_defaults['display_size'], false ) . ' /> <label for="' . $this->get_field_id( 'display_size' ) . '">' . __( 'Display file size?', 'download-attachments' ) . '</label><br />
			<input id="' . $this->get_field_id( 'display_date' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_date' ) . '" ' . checked( true, isset( $instance['display_date'] ) ? $instance['display_date'] : $this->da_defaults['display_date'], false ) . ' /> <label for="' . $this->get_field_id( 'display_date' ) . '">' . __( 'Display file date?', 'download-attachments' ) . '</label><br />
			<input id="' . $this->get_field_id( 'display_caption' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_caption' ) . '" ' . checked( true, (isset( $instance['display_caption'] ) ? $instance['display_caption'] : $this->da_defaults['display_caption'] ), false ) . ' /> <label for="' . $this->get_field_id( 'display_caption' ) . '">' . __( 'Display attachment caption?', 'download-attachments' ) . '</label><br />
			<input id="' . $this->get_field_id( 'display_description' ) . '" type="checkbox" name="' . $this->get_field_name( 'display_description' ) . '" ' . checked( true, (isset( $instance['display_description'] ) ? $instance['display_description'] : $this->da_defaults['display_description'] ), false ) . ' /> <label for="' . $this->get_field_id( 'display_description' ) . '">' . __( 'Display attachment description?', 'download-attachments' ) . '</label><br />
		</p>';

		echo $html;
	}

	/**
	 * Save widget function.
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		// attached to
		$old_instance['attached_to'] = isset( $new_instance['attached_to'] ) && in_array( $new_instance['attached_to'], array_keys( $this->da_attached_to_types ), true ) ? $new_instance['attached_to'] : $this->da_defaults['attached_to'];
		
		// style
		$old_instance['style'] = isset( $new_instance['style'] ) && in_array( $new_instance['style'], array_keys( $this->da_style_types ), true ) ? $new_instance['style'] : $this->da_defaults['style'];

		// link type
		$old_instance['link_type'] = isset( $new_instance['link_type'] ) && in_array( $new_instance['link_type'], array_keys( $this->da_link_types ), true ) ? $new_instance['link_type'] : $this->da_defaults['link_type'];
		
		// orderby
		$old_instance['orderby'] = isset( $new_instance['orderby'] ) && in_array( $new_instance['orderby'], array_keys( $this->da_orderby_types ), true ) ? $new_instance['orderby'] : $this->da_defaults['orderby'];

		// order
		$old_instance['order'] = isset( $new_instance['order'] ) && in_array( $new_instance['order'], array_keys( $this->da_order_types ), true ) ? $new_instance['order'] : $this->da_defaults['order'];

		// booleans
		$old_instance['display_index'] = isset( $new_instance['display_index'] );
		$old_instance['display_user'] = isset( $new_instance['display_user'] );
		$old_instance['display_icon'] = isset( $new_instance['display_icon'] );
		$old_instance['display_count'] = isset( $new_instance['display_count'] );
		$old_instance['display_size'] = isset( $new_instance['display_size'] );
		$old_instance['display_date'] = isset( $new_instance['display_date'] );
		$old_instance['display_caption'] = isset( $new_instance['display_caption'] );
		$old_instance['display_description'] = isset( $new_instance['display_description'] );
		
		// number of posts
		$old_instance['number_of_posts'] = (int) (isset( $new_instance['number_of_posts'] ) ? $new_instance['number_of_posts'] : $this->da_defaults['number_of_posts']);
		
		// texts
		$old_instance['title'] = sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : $this->da_defaults['title']  );
		$old_instance['no_attachments_message'] = sanitize_text_field( isset( $new_instance['no_attachments_message'] ) ? $new_instance['no_attachments_message'] : $this->da_defaults['no_attachments_message']  );

		return $old_instance;
	}
}

new Download_Attachments_Widgets();