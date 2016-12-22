<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Metabox class.
 * 
 * @class Download_Attachments_Metabox
 */
class Download_Attachments_Widgets {

	public function __construct() {
		// actions
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register widgets.
	 */
	public function register_widgets() {
		register_widget( 'Download_Attachments_List_Widget' );
	}

}

/**
 * Download_Attachments_List_Widget class.
 */
class Download_Attachments_List_Widget extends WP_Widget {

	private $da_defaults;
	private $da_order_types;
	private $da_link_types;

	public function __construct() {
		parent::__construct(
		'Download_Attachments_List_Widget', __( 'Most Downloaded Attachments', 'download-attachments' ), array(
			'description'	 => __( 'Displays a list of the most downloaded attachments', 'download-attachments' ),
			'classname'		 => 'widget_download_attachments_list'
		)
		);

		$this->da_defaults = array(
			'title'						 => __( 'Most Downloaded Attachments', 'download-attachments' ),
			'number_of_posts'			 => 5,
			'link_type'					 => 'page',
			'order'						 => 'desc',
			'show_attachment_downloads'	 => true,
			'show_attachment_icon'		 => true,
			'show_attachment_excerpt'	 => false,
			'no_attachments_message'	 => __( 'No Attachments found', 'download-attachments' )
		);

		$this->da_order_types = array(
			'asc'	 => __( 'Ascending', 'download-attachments' ),
			'desc'	 => __( 'Descending', 'download-attachments' )
		);

		$this->da_link_types = array(
			'page'	 => __( 'Attachment page', 'download-attachments' ),
			'url'	 => __( 'Download URL', 'download-attachments' )
		);
	}

	/**
	 * Display widget function.
	 */
	public function widget( $args, $instance ) {
		$instance['title'] = apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base );

		$html = $args['before_widget'] . ( ! empty( $instance['title'] ) ? $args['before_title'] . $instance['title'] . $args['after_title'] : '');
		$html .= da_most_downloaded_attachments( $instance, false );
		$html .= $args['after_widget'];

		echo $html;
	}

	/**
	 * Admin widget function.
	 */
	public function form( $instance ) {
		$show_attachment_icon = isset( $instance['show_attachment_icon'] ) ? $instance['show_attachment_icon'] : $this->da_defaults['show_attachment_icon'];

		$html = '
	<p>
	    <label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title', 'download-attachments' ) . ':</label>
	    <input id="' . $this->get_field_id( 'title' ) . '" class="widefat" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( isset( $instance['title'] ) ? $instance['title'] : $this->da_defaults['title']  ) . '" />
	</p>
	<p>
	    <label for="' . $this->get_field_id( 'number_of_posts' ) . '">' . __( 'Number of attachments to show', 'download-attachments' ) . ':</label>
	    <input id="' . $this->get_field_id( 'number_of_posts' ) . '" name="' . $this->get_field_name( 'number_of_posts' ) . '" type="text" size="3" value="' . esc_attr( isset( $instance['number_of_posts'] ) ? $instance['number_of_posts'] : $this->da_defaults['number_of_posts']  ) . '" />
	</p>
	<p>
	    <label for="' . $this->get_field_id( 'no_attachments_message' ) . '">' . __( 'No attachments message', 'download-attachments' ) . ':</label>
	    <input id="' . $this->get_field_id( 'no_attachments_message' ) . '" class="widefat" type="text" name="' . $this->get_field_name( 'no_attachments_message' ) . '" value="' . esc_attr( isset( $instance['no_attachments_message'] ) ? $instance['no_attachments_message'] : $this->da_defaults['no_attachments_message']  ) . '" />
	</p>
	<p>
	    <label for="' . $this->get_field_id( 'link_type' ) . '">' . __( 'Link to', 'download-attachments' ) . ':</label>
	    <select id="' . $this->get_field_id( 'link_type' ) . '" name="' . $this->get_field_name( 'link_type' ) . '">';

		foreach ( $this->da_link_types as $type => $link ) {
			$html .= '
		<option value="' . esc_attr( $type ) . '" ' . selected( $type, ( isset( $instance['link_type'] ) ? $instance['link_type'] : $this->da_defaults['link_type'] ), false ) . '>' . $link . '</option>';
		}

		$html .= '
	    </select>
	</p>
	<p>
	    <label for="' . $this->get_field_id( 'order' ) . '">' . __( 'Order', 'download-attachments' ) . ':</label>
	    <select id="' . $this->get_field_id( 'order' ) . '" name="' . $this->get_field_name( 'order' ) . '">';

		foreach ( $this->da_order_types as $id => $order ) {
			$html .= '
		<option value="' . esc_attr( $id ) . '" ' . selected( $id, ( isset( $instance['order'] ) ? $instance['order'] : $this->da_defaults['order'] ), false ) . '>' . $order . '</option>';
		}

		$html .= '
	    </select>
	</p>
	<p>
	    <input id="' . $this->get_field_id( 'show_attachment_downloads' ) . '" type="checkbox" name="' . $this->get_field_name( 'show_attachment_downloads' ) . '" ' . checked( true, (isset( $instance['show_attachment_downloads'] ) ? $instance['show_attachment_downloads'] : $this->da_defaults['show_attachment_downloads'] ), false ) . ' /> <label for="' . $this->get_field_id( 'show_attachment_downloads' ) . '">' . __( 'Display attachment downloads?', 'download-attachments' ) . '</label>
	    <br />
	    <input id="' . $this->get_field_id( 'show_attachment_excerpt' ) . '" type="checkbox" name="' . $this->get_field_name( 'show_attachment_excerpt' ) . '" ' . checked( true, (isset( $instance['show_attachment_excerpt'] ) ? $instance['show_attachment_excerpt'] : $this->da_defaults['show_attachment_excerpt'] ), false ) . ' /> <label for="' . $this->get_field_id( 'show_attachment_excerpt' ) . '">' . __( 'Display attachment description?', 'download-attachments' ) . '</label>
	    <br />
	    <input id="' . $this->get_field_id( 'show_attachment_icon' ) . '" type="checkbox" name="' . $this->get_field_name( 'show_attachment_icon' ) . '" ' . checked( true, $show_attachment_icon, false ) . ' /> <label for="' . $this->get_field_id( 'show_attachment_icon' ) . '">' . __( 'Display attachment icon?', 'download-attachments' ) . '</label>
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
		// number of posts
		$old_instance['number_of_posts'] = (int) (isset( $new_instance['number_of_posts'] ) ? $new_instance['number_of_posts'] : $this->da_defaults['number_of_posts']);

		// link type
		$old_instance['link_type'] = isset( $new_instance['link_type'] ) && in_array( $new_instance['link_type'], array_keys( $this->da_link_types ), true ) ? $new_instance['link_type'] : $this->da_defaults['link_type'];

		// order
		$old_instance['order'] = isset( $new_instance['order'] ) && in_array( $new_instance['order'], array_keys( $this->da_order_types ), true ) ? $new_instance['order'] : $this->da_defaults['order'];

		// booleans
		$old_instance['show_attachment_downloads'] = isset( $new_instance['show_attachment_downloads'] );
		$old_instance['show_attachment_icon'] = isset( $new_instance['show_attachment_icon'] );
		$old_instance['show_attachment_excerpt'] = isset( $new_instance['show_attachment_excerpt'] );

		// texts
		$old_instance['title'] = sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : $this->da_defaults['title']  );
		$old_instance['no_attachments_message'] = sanitize_text_field( isset( $new_instance['no_attachments_message'] ) ? $new_instance['no_attachments_message'] : $this->da_defaults['no_attachments_message']  );

		return $old_instance;
	}

}

new Download_Attachments_Widgets();
