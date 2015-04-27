<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

new Download_Attachments_Settings( $download_attachments );

class Download_Attachments_Settings {

	private $download_attachments;
	private $capabilities;
	private $attachment_links;
	private $download_box_displays;
	private $contents;
	private $columns;
	private $post_types;
	private $libraries;
	private $choices;
	private $defaults;
	private $options;

	/**
	 * Constructor.
	 */
	public function __construct( $download_attachments = '' ) {
		// settings
		$this->options = array_merge(
			array( 'general' => get_option( 'download_attachments_general' ) )
		);

		if ( $download_attachments !== '' ) {
			// passed vars
			$this->download_attachments = $download_attachments;
			$this->defaults = $download_attachments->get_defaults();
			unset( $download_attachments );
		}

		//actions
		add_action( 'admin_menu', array( &$this, 'settings_page' ) );
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		add_action( 'after_setup_theme', array( &$this, 'load_defaults' ) );
		add_action( 'wp_loaded', array( &$this, 'load_post_types' ) );
	}

	/**
	 * Load defaults
	 */
	public function load_defaults() {
		$this->columns = $this->download_attachments->get_columns();

		$this->choices = array(
			'yes'	 => __( 'Enable', 'download-attachments' ),
			'no'	 => __( 'Disable', 'download-attachments' )
		);

		$this->libraries = array(
			'all'	 => __( 'All files', 'download-attachments' ),
			'post'	 => __( 'Attached to a post only', 'download-attachments' )
		);

		$this->capabilities = array(
			'manage_download_attachments' => __( 'Manage download attachments', 'download-attachments' )
		);

		$this->attachment_links = array(
			'media_library'	 => __( 'Media Library', 'download-attachments' ),
			'modal'			 => __( 'Modal', 'download-attachments' )
		);

		$this->download_box_displays = array(
			'before_content' => __( 'before the content', 'download-attachments' ),
			'after_content'	 => __( 'after the content', 'download-attachments' ),
			'manually'		 => __( 'manually', 'download-attachments' )
		);

		$this->display_styles = array(
			'list'	 => __( 'List', 'download-attachments' ),
			'table'	 => __( 'Table', 'download-attachments' )
		);

		$this->contents = array(
			'caption'		 => __( 'caption', 'download-attachments' ),
			'description'	 => __( 'description', 'download-attachments' )
		);
	}

	/**
	 * Load post types.
	 */
	public function load_post_types() {
		$this->post_types = apply_filters( 'da_post_types', array_merge( array( 'post', 'page' ), get_post_types( array( '_builtin' => false, 'public' => true ), 'names' ) ) );
		sort( $this->post_types, SORT_STRING );
	}

	/**
	 * Add options page menu.
	 */
	public function settings_page() {
		add_options_page(
			__( 'Download Attachments', 'download-attachments' ), __( 'Download Attachments', 'download-attachments' ), 'manage_options', 'download-attachments-options', array( &$this, 'options_page' )
		);
	}

	/**
	 * Options pgae output callback.
	 */
	public function options_page() {
		echo '
		<div class="wrap">' . screen_icon() . '
			<h2>' . __( 'Download Attachments', 'download-attachments' ) . '</h2>
			<div class="download-attachments-settings">
				<div class="df-sidebar">
					<div class="df-credits">
						<h3 class="hndle">' . __( 'Download Attachments', 'download-attachments' ) . ' ' . $this->defaults['version'] . '</h3>
						<div class="inside">
							<h4 class="inner">' . __( 'Need support?', 'download-attachments' ) . '</h4>
							<p class="inner">' . __( 'If you are having problems with this plugin, please talk about them in the', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/support/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=support" target="_blank" title="' . __( 'Support forum', 'download-attachments' ) . '">' . __( 'Support forum', 'download-attachments' ) . '</a></p>
							<hr/>
							<h4 class="inner">' . __( 'Do you like this plugin?', 'download-attachments' ) . '</h4>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" class="inner">
								<input type="hidden" name="cmd" value="_s-xclick">
								<input type="hidden" name="hosted_button_id" value="5KXY2JTQXDQEG">
								<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
								<img alt="" border="0" src="https://www.paypalobjects.com/pl_PL/i/scr/pixel.gif" width="1" height="1">
							</form>
							<p class="inner"><a href="http://wordpress.org/support/view/plugin-reviews/download-attachments" target="_blank" title="' . __( 'Rate it 5', 'download-attachments' ) . '">' . __( 'Rate it 5', 'download-attachments' ) . '</a> ' . __( 'on WordPress.org', 'download-attachments' ) . '<br/>' .
		__( 'Blog about it & link to the', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/plugins/download-attachments/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=blog-about" target="_blank" title="' . __( 'plugin page', 'download-attachments' ) . '">' . __( 'plugin page', 'download-attachments' ) . '</a><br/>' .
		__( 'Check out our other', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/plugins/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=other-plugins" target="_blank" title="' . __( 'WordPress plugins', 'download-attachments' ) . '">' . __( 'WordPress plugins', 'download-attachments' ) . '</a>
							</p>
							<hr/>
							<p class="df-link inner">' . __( 'Created by', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=created-by" target="_blank" title="dFactory - Quality plugins for WordPress"><img src="' . DOWNLOAD_ATTACHMENTS_URL . '/images/logo-dfactory.png' . '" title="dFactory - Quality plugins for WordPress" alt="dFactory - Quality plugins for WordPress"/></a></p>
						</div>
					</div>';
		/*
		  <div class="df-ads">
		  <a href="http://www.dfactory.eu/plugins/frontend-users/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=banner" target="_blank" title="Frontend Users by dFactory"><img src="'.DOWNLOAD_ATTACHMENTS_URL.'/images/ad-frontend-users.png'.'" alt="Frontend Users by dFactory" /></a>
		  </div>
		 */
		echo ' 
				</div>
				<form action="options.php" method="post">';

		wp_nonce_field( 'update-options' );
		settings_fields( 'download_attachments_general' );
		do_settings_sections( 'download_attachments_general' );

		echo '
					<p class="submit">';

		submit_button( '', 'primary', 'save_da_general', false );

		echo ' ';

		submit_button( __( 'Reset to defaults', 'download-attachments' ), 'secondary', 'reset_da_general', false );

		echo ' ';

		submit_button( __( 'Reset downloads', 'download-attachments' ), 'secondary', 'reset_da_downloads', false );

		echo '
					</p>
				</form>
			</div>
			<div class="clear"></div>
		</div>';
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		// general section
		register_setting( 'download_attachments_general', 'download_attachments_general', array( &$this, 'validate_general' ) );
		add_settings_section( 'download_attachments_general', __( 'General settings', 'download-attachments' ), '', 'download_attachments_general' );
		add_settings_field( 'da_general_label', __( 'Label', 'download-attachments' ), array( &$this, 'da_general_label' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_capabilities', __( 'Capabilities', 'download-attachments' ), array( &$this, 'da_general_capabilities' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_post_types', __( 'Supported Post Types', 'download-attachments' ), array( &$this, 'da_general_post_types' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_deactivation_delete', __( 'Deactivation', 'download-attachments' ), array( &$this, 'da_general_deactivation_delete' ), 'download_attachments_general', 'download_attachments_general' );

		// admin section
		add_settings_section( 'download_attachments_general_admin', __( 'Admin settings', 'download-attachments' ), '', 'download_attachments_general' );
		add_settings_field( 'da_general_backend_display', __( 'Backend Display', 'download-attachments' ), array( &$this, 'da_general_backend_display' ), 'download_attachments_general', 'download_attachments_general_admin' );
		add_settings_field( 'da_general_backend_content', __( 'Backend Downloads Description', 'download-attachments' ), array( &$this, 'da_general_backend_content' ), 'download_attachments_general', 'download_attachments_general_admin' );
		add_settings_field( 'da_general_attachment_link', __( 'Edit attachment link', 'download-attachments' ), array( &$this, 'da_general_attachment_link' ), 'download_attachments_general', 'download_attachments_general_admin' );
		add_settings_field( 'da_general_libraries', __( 'Media Library', 'download-attachments' ), array( &$this, 'da_general_libraries' ), 'download_attachments_general', 'download_attachments_general_admin' );
		add_settings_field( 'da_general_downloads_in_media_library', __( 'Downloads in Media Library', 'download-attachments' ), array( &$this, 'da_general_downloads_in_media_library' ), 'download_attachments_general', 'download_attachments_general_admin' );

		// frontend section
		add_settings_section( 'download_attachments_general_frontend', __( 'Frontend settings', 'download-attachments' ), '', 'download_attachments_general' );
		add_settings_field( 'da_general_frontend_display', __( 'Frontend Display', 'download-attachments' ), array( &$this, 'da_general_frontend_display' ), 'download_attachments_general', 'download_attachments_general_frontend' );
		add_settings_field( 'da_general_display_style', __( 'Display Style', 'download-attachments' ), array( &$this, 'da_general_display_style' ), 'download_attachments_general', 'download_attachments_general_frontend' );
		add_settings_field( 'da_general_frontend_content', __( 'Frontend Downloads Description', 'download-attachments' ), array( &$this, 'da_general_frontend_content' ), 'download_attachments_general', 'download_attachments_general_frontend' );
		add_settings_field( 'da_general_css_style', __( 'Use CSS style', 'download-attachments' ), array( &$this, 'da_general_css_style' ), 'download_attachments_general', 'download_attachments_general_frontend' );
		add_settings_field( 'da_general_pretty_urls', __( 'Pretty URLs', 'download-attachments' ), array( &$this, 'da_general_pretty_urls' ), 'download_attachments_general', 'download_attachments_general_frontend' );
		add_settings_field( 'da_general_download_box_display', __( 'Downloads List Display', 'download-attachments' ), array( &$this, 'da_general_download_box_display' ), 'download_attachments_general', 'download_attachments_general_frontend' );
	}

	/**
	 * 
	 */
	public function da_general_label() {
		echo '
		<div id="da_general_label">
			<fieldset>
				<input type="text" class="regular-text" name="download_attachments_general[label]" value="' . esc_attr( $this->options['general']['label'] ) . '"/>
				<br/>
				<span class="description">' . esc_html__( 'Enter download attachments list label.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_post_types() {
		echo '
		<div id="da_general_post_types">
			<fieldset>';

		foreach ( $this->post_types as $val ) {
			echo '
				<input id="da-general-post-types-' . $val . '" type="checkbox" name="download_attachments_general[post_types][]" value="' . esc_attr( $val ) . '" ' . checked( true, (isset( $this->options['general']['post_types'][$val] ) ? $this->options['general']['post_types'][$val] : false ), false ) . '/><label for="da-general-post-types-' . $val . '">' . $val . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select which post types would you like to enable for your downloads.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_backend_display() {
		echo '
		<div id="da_general_backend_display">
			<fieldset>';

		foreach ( $this->columns as $val => $trans ) {
			if ( ! in_array( $val, array( 'title', 'index', 'icon', 'exclude' ), true ) )
				echo '
				<input id="da-general-backend-display-' . $val . '" type="checkbox" name="download_attachments_general[backend_columns][]" value="' . esc_attr( $val ) . '" ' . checked( true, $this->options['general']['backend_columns'][$val], false ) . '/><label for="da-general-backend-display-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select which columns would you like to enable on backend for your downloads.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_frontend_display() {
		echo '
		<div id="da_general_frontend_display">
			<fieldset>';

		foreach ( $this->columns as $val => $trans ) {
			if ( ! in_array( $val, array( 'id', 'type', 'title', 'exclude' ), true ) )
				echo '
				<input id="da-general-frontend-display-' . $val . '" type="checkbox" name="download_attachments_general[frontend_columns][]" value="' . esc_attr( $val ) . '" ' . checked( true, $this->options['general']['frontend_columns'][$val], false ) . '/><label for="da-general-frontend-display-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select which columns would you like to enable on frontend for your downloads.', 'download-attachments' ) . '</span>
			<fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_css_style() {
		echo '
		<div id="da_general_css_style">
			<fieldset>';

		foreach ( $this->choices as $val => $trans ) {
			echo '
				<input id="da-general-css-style-' . $val . '" type="radio" name="download_attachments_general[use_css_style]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), $this->options['general']['use_css_style'], false ) . '/><label for="da-general-css-style-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select if you\'d like to use bultin CSS style.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_pretty_urls() {
		echo '
		<div id="da_general_pretty_urls">
			<fieldset>';

		foreach ( $this->choices as $val => $trans ) {
			echo '
				<input id="da-general-pretty-urls-' . $val . '" type="radio" name="download_attachments_general[pretty_urls]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), $this->options['general']['pretty_urls'], false ) . '/><label for="da-general-pretty-urls-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Enable if you want to use pretty URLs.', 'download-attachments' ) . '</span>
				<div id="da_general_download_link"' . ( ! $this->options['general']['pretty_urls'] ? ' style="display: none;"' : '') . '>
					<label for="da_general_download_link_label">' . __( 'Slug', 'download-attachments' ) . '</label>: <input id="da_general_download_link_label" type="text" name="download_attachments_general[download_link]" value="' . esc_attr( $this->options['general']['download_link'] ) . '"/>
					<p class="description">
						<code>' . site_url() . '/<strong>' . $this->options['general']['download_link'] . '</strong>/123/</code>
						<br/>
						' . __( 'Download link slug.', 'download-attachments' ) . '
					</p>
				</div>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_download_box_display() {
		echo '
		<div id="da_general_download_box_display">
			<fieldset>';

		foreach ( $this->download_box_displays as $val => $trans ) {
			echo '
				<input id="da-general-download-box-display-' . $val . '" type="radio" name="download_attachments_general[download_box_display]" value="' . esc_attr( $val ) . '" ' . checked( $val, $this->options['general']['download_box_display'], false ) . '/><label for="da-general-download-box-display-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select where you would like your download attachments to be displayed.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_display_style() {
		echo '
		<div id="da_general_display_style">
			<fieldset>';

		foreach ( $this->display_styles as $val => $trans ) {
			echo '
				<input id="da-general-display-style-' . $val . '" type="radio" name="download_attachments_general[display_style]" value="' . esc_attr( $val ) . '" ' . checked( $val, isset( $this->options['general']['display_style'] ) ? esc_attr( $this->options['general']['display_style'] ) : $this->defaults['general']['display_style'], false ) . '/><label for="da-general-display-style-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select display style for file attachments.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_backend_content() {
		echo '
		<div id="da_general_backend_content">
			<fieldset>';

		foreach ( $this->contents as $val => $trans ) {
			echo '
				<input id="da-general-backend-content-' . $val . '" type="checkbox" name="download_attachments_general[backend_content][]" value="' . esc_attr( $val ) . '" ' . checked( true, (isset( $this->options['general']['backend_content'][$val] ) ? $this->options['general']['backend_content'][$val] : false ), false ) . '/><label for="da-general-backend-content-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select what fields to use on backend for download attachments description.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_frontend_content() {
		echo '
		<div id="da_general_frontend_content">
			<fieldset>';

		foreach ( $this->contents as $val => $trans ) {
			echo '
				<input id="da-general-frontend-content-' . $val . '" type="checkbox" name="download_attachments_general[frontend_content][]" value="' . esc_attr( $val ) . '" ' . checked( true, (isset( $this->options['general']['frontend_content'][$val] ) ? $this->options['general']['frontend_content'][$val] : false ), false ) . '/><label for="da-general-frontend-content-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select what fields to use on frontend for download attachments description.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_attachment_link() {
		echo '
		<div id="da_general_attachment_link">
			<fieldset>';

		foreach ( $this->attachment_links as $val => $trans ) {
			echo '
				<input id="da-general-attachment-link-' . $val . '" type="radio" name="download_attachments_general[attachment_link]" value="' . esc_attr( $val ) . '" ' . checked( $val, $this->options['general']['attachment_link'], false ) . '/><label for="da-general-attachment-link-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br />
				<span class="description">' . __( 'Select where you would like to edit download attachments.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_libraries() {
		echo '
		<div id="da_general_libraries">
			<fieldset>';

		foreach ( $this->libraries as $val => $trans ) {
			echo '
				<input id="da-general-libraries-' . $val . '" type="radio" name="download_attachments_general[library]" value="' . esc_attr( $val ) . '" ' . checked( $val, $this->options['general']['library'], false ) . '/><label for="da-general-libraries-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Select which attachments should be visible in Media Library window.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_downloads_in_media_library() {
		echo '
		<div id="da_general_downloads_in_media_library">
			<fieldset>';

		foreach ( $this->choices as $val => $trans ) {
			echo '
				<input id="da-general-downloads-in-media-library-' . $val . '" type="radio" name="download_attachments_general[downloads_in_media_library]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), $this->options['general']['downloads_in_media_library'], false ) . '/><label for="da-general-downloads-in-media-library-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Enable if you want to display downloads count in your Media Library columns.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * 
	 */
	public function da_general_capabilities() {
		global $wp_roles;

		$editable_roles = get_editable_roles();

		$html = '
		<div id="da_general_capabilities">
			<fieldset>
				<table class="widefat">
					<thead>
						<tr>
							<th>' . __( 'Role', 'download-attachments' ) . '</th>';

		foreach ( $editable_roles as $role_name => $role_info ) {
			$html .= '
							<th>' . esc_html( (isset( $wp_roles->role_names[$role_name] ) ? translate_user_role( $wp_roles->role_names[$role_name] ) : __( 'None', 'download-attachments' ) ) ) . '</th>';
		}

		$html .= '
						</tr>
					</thead>
					<tbody id="the-list">';

		$i = 0;

		foreach ( $this->capabilities as $em_role => $role_display ) {
			$html .= '
						<tr' . (($i ++ % 2 === 0) ? ' class="alternate"' : '') . '>
							<td>' . __( $role_display, 'download-attachments' ) . '</td>';

			foreach ( $editable_roles as $role_name => $role_info ) {
				$role = $wp_roles->get_role( $role_name );
				$html .= '
							<td>
								<input type="checkbox" name="download_attachments_general[capabilities][' . esc_attr( $role->name ) . '][' . esc_attr( $em_role ) . ']" value="1" ' . checked( '1', $role->has_cap( $em_role ), false ) . ' ' . disabled( $role->name, 'administrator', false ) . '/>
							</td>';
			}

			$html .= '
						</tr>';
		}

		$html .= '
					</tbody>
				</table>
				<br/>
				<span class="description">' . __( 'Select user roles allowed to add, remove and manage Download Attachments.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';

		echo $html;
	}

	/**
	 * 
	 */
	public function da_general_deactivation_delete() {
		echo '
		<div id="da_general_deactivation_delete">
			<fieldset>';

		foreach ( $this->choices as $val => $trans ) {
			echo '
				<input id="da-general-deactivation-delete-' . $val . '" type="radio" name="download_attachments_general[deactivation_delete]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), $this->options['general']['deactivation_delete'], false ) . '/><label for="da-general-deactivation-delete-' . $val . '">' . $trans . '</label>';
		}

		echo '
				<br/>
				<span class="description">' . __( 'Enable if you want all plugin data to be deleted on deactivation.', 'download-attachments' ) . '</span>
			</fieldset>
		</div>';
	}

	/**
	 * Validate general settings, reset general settings, reset download counts.
	 */
	public function validate_general( $input ) {
		global $wp_roles;

		if ( isset( $_POST['save_da_general'] ) ) {
			// capabilities
			foreach ( $wp_roles->roles as $role_name => $role_text ) {
				$role = $wp_roles->get_role( $role_name );

				if ( ! $role->has_cap( 'manage_options' ) ) {
					foreach ( $this->defaults['general']['capabilities'] as $capability ) {
						if ( isset( $input['capabilities'][$role_name][$capability] ) && $input['capabilities'][$role_name][$capability] === '1' )
							$role->add_cap( $capability );
						else
							$role->remove_cap( $capability );
					}
				}
			}

			$input['capabilities'] = $this->defaults['general']['capabilities'];

			// backend columns
			$columns = array();
			$input['backend_columns'] = (isset( $input['backend_columns'] ) ? $input['backend_columns'] : array());

			foreach ( $this->columns as $column => $text ) {
				if ( in_array( $column, array( 'index', 'icon', 'exclude' ), true ) )
					continue;
				if ( $column === 'title' )
					$columns[$column] = true;
				else
					$columns[$column] = (in_array( $column, $input['backend_columns'], true ) ? true : false);
			}

			$input['backend_columns'] = $columns;

			// frontend columns
			$columns = array();
			$input['frontend_columns'] = (isset( $input['frontend_columns'] ) ? $input['frontend_columns'] : array());

			foreach ( $this->columns as $column => $text ) {
				if ( in_array( $column, array( 'id', 'type', 'exclude' ), true ) )
					continue;
				elseif ( $column === 'title' )
					$columns[$column] = true;
				else
					$columns[$column] = (in_array( $column, $input['frontend_columns'], true ) ? true : false);
			}

			$input['frontend_columns'] = $columns;

			// post types
			$post_types = array();
			$input['post_types'] = (isset( $input['post_types'] ) ? $input['post_types'] : array());

			foreach ( $this->post_types as $post_type ) {
				$post_types[$post_type] = (in_array( $post_type, $input['post_types'], true ) ? true : false);
			}

			$input['post_types'] = $post_types;

			// backend content
			$contents = array();
			$input['backend_content'] = (isset( $input['backend_content'] ) ? $input['backend_content'] : array());

			foreach ( $this->contents as $content => $trans ) {
				$contents[$content] = (in_array( $content, $input['backend_content'], true ) ? true : false);
			}

			$input['backend_content'] = $contents;

			// frontend content
			$contents = array();
			$input['frontend_content'] = (isset( $input['frontend_content'] ) ? $input['frontend_content'] : array());

			foreach ( $this->contents as $content => $trans ) {
				$contents[$content] = (in_array( $content, $input['frontend_content'], true ) ? true : false);
			}

			$input['frontend_content'] = $contents;

			// pretty urls
			$input['pretty_urls'] = (isset( $input['pretty_urls'] ) && in_array( $input['pretty_urls'], array_keys( $this->choices ), true ) ? ($input['pretty_urls'] === 'yes' ? true : false) : $this->defaults['general']['pretty_urls']);

			// display style
			$input['display_style'] = (isset( $input['display_style'] ) && in_array( $input['display_style'], array_keys( $this->display_styles ), true ) ? $input['display_style'] : $this->defaults['general']['display_style']);

			// use css style
			$input['use_css_style'] = (isset( $input['use_css_style'] ) && in_array( $input['use_css_style'], array_keys( $this->choices ), true ) ? ($input['use_css_style'] === 'yes' ? true : false) : $this->defaults['general']['use_css_style']);

			// label
			$input['label'] = sanitize_text_field( $input['label'] );

			// downloads in media library
			$input['downloads_in_media_library'] = (isset( $input['downloads_in_media_library'] ) && in_array( $input['downloads_in_media_library'], array_keys( $this->choices ), true ) ? ($input['downloads_in_media_library'] === 'yes' ? true : false) : $this->defaults['general']['downloads_in_media_library']);

			// download link
			if ( $input['pretty_urls'] ) {
				$input['download_link'] = sanitize_title( $input['download_link'] );

				if ( $input['download_link'] === '' )
					$input['download_link'] = $this->defaults['general']['download_link'];
			} else
				$input['download_link'] = $this->defaults['general']['download_link'];

			// deactivation delete
			$input['deactivation_delete'] = (isset( $input['deactivation_delete'] ) && in_array( $input['deactivation_delete'], array_keys( $this->choices ), true ) ? ($input['deactivation_delete'] === 'yes' ? true : false) : $this->defaults['general']['deactivation_delete']);

			// download box display
			$input['download_box_display'] = (isset( $input['download_box_display'] ) && in_array( $input['download_box_display'], array_keys( $this->download_box_displays ), true ) ? $input['download_box_display'] : $this->defaults['general']['download_box_display']);

			// attachment link
			$input['attachment_link'] = (isset( $input['attachment_link'] ) && in_array( $input['attachment_link'], array_keys( $this->attachment_links ), true ) ? $input['attachment_link'] : $this->defaults['general']['attachment_link']);

			// library
			$input['library'] = (isset( $input['library'] ) && in_array( $input['library'], array_keys( $this->libraries ), true ) ? $input['library'] : $this->defaults['general']['library']);
		}
		elseif ( isset( $_POST['reset_da_general'] ) ) {
			// capabilities
			foreach ( $wp_roles->roles as $role_name => $display_name ) {
				$role = $wp_roles->get_role( $role_name );

				foreach ( $this->defaults['general']['capabilities'] as $capability ) {
					if ( $role->has_cap( 'upload_files' ) )
						$role->add_cap( $capability );
					else
						$role->remove_cap( $capability );
				}
			}

			$input = $this->defaults['general'];

			add_settings_error( 'reset_general_settings', 'reset_general_settings', __( 'Settings restored to defaults.', 'download-attachments' ), 'updated' );
		}
		elseif ( isset( $_POST['reset_da_downloads'] ) ) {
			global $wpdb;

			// lets use wpdb to reset downloads a lot faster then normal update_post_meta for each post_id
			$result = $wpdb->update(
				$wpdb->postmeta, array( 'meta_value' => 0 ), array( 'meta_key' => '_da_downloads' ), '%d', '%s'
			);

			$input = $this->options['general'];

			if ( $result === false )
				add_settings_error( 'reset_downloads', 'reset_downloads_error', __( 'An error occurred while resetting the number of downloads.', 'download-attachments' ), 'error' );
			else
				add_settings_error( 'reset_downloads', 'reset_downloads_updated', __( 'Number of downloads of all attachments has been reset.', 'download-attachments' ), 'updated' );
		}

		return $input;
	}

}

?>