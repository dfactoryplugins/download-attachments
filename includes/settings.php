<?php
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Download_Attachments_Metabox class.
 * 
 * @class Download_Attachments_Metabox
 */
class Download_Attachments_Settings {

	private $capabilities;
	private $attachment_links;
	private $download_box_displays;
	private $contents;
	private $download_methods;
	private $libraries;
	private $choices;
	private $tabs;
	public $post_types;

	/**
	 * Constructor class.
	 */
	public function __construct() {
		//actions
		add_action( 'admin_menu', array( $this, 'settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'after_setup_theme', array( $this, 'load_defaults' ) );
		add_action( 'wp_loaded', array( $this, 'load_post_types' ) );
	}

	/**
	 * Load defaults
	 */
	public function load_defaults() {
		$this->tabs = array(
			'general' => array(
				'name'	 => __( 'General', 'download-attachments' ),
				'key'	 => 'download_attachments_general',
				'submit' => 'save_da_general',
				'reset'	 => 'reset_da_general'
			),
			'display' => array(
				'name'	 => __( 'Display', 'download-attachments' ),
				'key'	 => 'download_attachments_display',
				'submit' => 'save_da_display',
				'reset'	 => 'reset_da_display'
			),
			'admin' => array(
				'name'	 => __( 'Admin', 'download-attachments' ),
				'key'	 => 'download_attachments_admin',
				'submit' => 'save_da_admin',
				'reset'	 => 'reset_da_admin'
			)
		);
		
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

		$this->download_methods = array(
			'force'		 => __( 'Force download', 'download-attachments' ),
			'redirect'	 => __( 'Redirect to file', 'download-attachments' )
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
		__( 'Attachments', 'download-attachments' ), __( 'Attachments', 'download-attachments' ), 'manage_options', 'download-attachments', array( $this, 'options_page' )
		);
	}

	/**
	 * Options pgae output callback.
	 * 
	 * @return mixed
	 */
	public function options_page() {
		$tab_key = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'general' );
		$options_page = 'options-general.php';
		$form_page = 'options.php';
		
		echo '
		<div class="wrap">' . screen_icon() . '
			<h1>' . __( 'Download Attachments', 'download-attachments' ) . '</h1>';

			echo '
			<h2 class="nav-tab-wrapper">';

		foreach ( $this->tabs as $key => $name ) {
			echo '
			<a class="nav-tab ' . ( $tab_key == $key ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( $options_page . '?page=download-attachments&tab=' . $key ) ) . '">' . $name['name'] . '</a>';
		}

		echo '
			</h2>
			<div class="download-attachments-settings">';
		
		echo '
				<div class="df-sidebar">
					<div class="df-credits">
						<h3 class="hndle">' . __( 'Download Attachments', 'download-attachments' ) . ' ' . Download_Attachments()->defaults['version'] . '</h3>
						<div class="inside">
							<h4 class="inner">' . __( 'Need support?', 'download-attachments' ) . '</h4>
							<p class="inner">' . __( 'If you are having problems with this plugin, please talk about them in the', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/support/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=support" target="_blank" title="' . __( 'Support forum', 'download-attachments' ) . '">' . __( 'Support forum', 'download-attachments' ) . '</a></p>
							<hr/>
							<h4 class="inner">' . __( 'Do you like this plugin?', 'download-attachments' ) . '</h4>
							<p class="inner"><a href="http://wordpress.org/support/view/plugin-reviews/download-attachments" target="_blank" title="' . __( 'Rate it 5', 'download-attachments' ) . '">' . __( 'Rate it 5', 'download-attachments' ) . '</a> ' . __( 'on WordPress.org', 'download-attachments' ) . '<br/>' .
					__( 'Blog about it & link to the', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/plugins/download-attachments/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=blog-about" target="_blank" title="' . __( 'plugin page', 'download-attachments' ) . '">' . __( 'plugin page', 'download-attachments' ) . '</a><br/>' .
					__( 'Check out our other', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/plugins/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=other-plugins" target="_blank" title="' . __( 'WordPress plugins', 'download-attachments' ) . '">' . __( 'WordPress plugins', 'download-attachments' ) . '</a>
							</p>
							<hr/>
							<p class="df-link inner">' . __( 'Created by', 'download-attachments' ) . ' <a href="http://www.dfactory.eu/?utm_source=download-attachments-settings&utm_medium=link&utm_campaign=created-by" target="_blank" title="dFactory - Quality plugins for WordPress"><img src="' . DOWNLOAD_ATTACHMENTS_URL . '/images/logo-dfactory.png' . '" title="dFactory - Quality plugins for WordPress" alt="dFactory - Quality plugins for WordPress"/></a></p>
						</div>
					</div>
				</div>';
		
		echo '
				<form action="' . $form_page . '" method="post" >';
		
		wp_nonce_field( 'update-options' );

		settings_fields( $this->tabs[$tab_key]['key'] );
		do_settings_sections( $this->tabs[$tab_key]['key'] );

		echo '
					<p class="submit">';
		submit_button( '', 'primary ' . $this->tabs[$tab_key]['submit'], $this->tabs[$tab_key]['submit'], false );
		echo ' ';
		submit_button( __( 'Reset to defaults', 'download-attachments' ), 'secondary ' . $this->tabs[$tab_key]['reset'], $this->tabs[$tab_key]['reset'], false );
		
		echo '
					</p>';

		echo '
				</form>
			</div>
			<div class="clear"></div>
		</div>';
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		global $pagenow;
		
		// general section
		register_setting( 'download_attachments_general', 'download_attachments_general', array( $this, 'validate_general' ) );
		add_settings_section( 'download_attachments_general', __( 'General settings', 'download-attachments' ), '', 'download_attachments_general' );
		add_settings_field( 'da_general_label', __( 'Label', 'download-attachments' ), array( $this, 'da_general_label' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_user_roles', __( 'User roles', 'download-attachments' ), array( $this, 'da_general_user_roles' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_post_types', __( 'Supported post types', 'download-attachments' ), array( $this, 'da_general_post_types' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_download_method', __( 'Download method', 'download-attachments' ), array( $this, 'da_general_download_method' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_pretty_urls', __( 'Pretty URLs', 'download-attachments' ), array( $this, 'da_general_pretty_urls' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_encrypt_urls', __( 'Encrypt URLs', 'download-attachments' ), array( $this, 'da_general_encrypt_urls' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_reset_downloads', __( 'Reset count', 'download-attachments' ), array( $this, 'da_general_reset_downloads' ), 'download_attachments_general', 'download_attachments_general' );
		add_settings_field( 'da_general_deactivation_delete', __( 'Deactivation', 'download-attachments' ), array( $this, 'da_general_deactivation_delete' ), 'download_attachments_general', 'download_attachments_general' );

		// frontend section
		register_setting( 'download_attachments_display', 'download_attachments_general', array( $this, 'validate_general' ) );
		add_settings_section( 'download_attachments_display', __( 'Display settings', 'download-attachments' ), '', 'download_attachments_display' );
		add_settings_field( 'da_general_frontend_display', __( 'Fields display', 'download-attachments' ), array( $this, 'da_general_frontend_display' ), 'download_attachments_display', 'download_attachments_display' );
		add_settings_field( 'da_general_display_style', __( 'Display style', 'download-attachments' ), array( $this, 'da_general_display_style' ), 'download_attachments_display', 'download_attachments_display' );
		add_settings_field( 'da_general_frontend_content', __( 'Downloads description', 'download-attachments' ), array( $this, 'da_general_frontend_content' ), 'download_attachments_display', 'download_attachments_display' );
		add_settings_field( 'da_general_css_style', __( 'Use CSS style', 'download-attachments' ), array( $this, 'da_general_css_style' ), 'download_attachments_display', 'download_attachments_display' );
		add_settings_field( 'da_general_download_box_display', __( 'Display position', 'download-attachments' ), array( $this, 'da_general_download_box_display' ), 'download_attachments_display', 'download_attachments_display' );
		
		// admin section
		register_setting( 'download_attachments_admin', 'download_attachments_general', array( $this, 'validate_general' ) );
		add_settings_section( 'download_attachments_admin', __( 'Admin settings', 'download-attachments' ), '', 'download_attachments_admin' );
		add_settings_field( 'da_general_backend_display', __( 'Fields display', 'download-attachments' ), array( $this, 'da_general_backend_display' ), 'download_attachments_admin', 'download_attachments_admin' );
		add_settings_field( 'da_general_backend_content', __( 'Downloads description', 'download-attachments' ), array( $this, 'da_general_backend_content' ), 'download_attachments_admin', 'download_attachments_admin' );
		add_settings_field( 'da_general_attachment_link', __( 'Edit attachment link', 'download-attachments' ), array( $this, 'da_general_attachment_link' ), 'download_attachments_admin', 'download_attachments_admin' );
		add_settings_field( 'da_general_libraries', __( 'Media Library', 'download-attachments' ), array( $this, 'da_general_libraries' ), 'download_attachments_admin', 'download_attachments_admin' );
		add_settings_field( 'da_general_downloads_in_media_library', __( 'Downloads count', 'download-attachments' ), array( $this, 'da_general_downloads_in_media_library' ), 'download_attachments_admin', 'download_attachments_admin' );
	}

	public function da_general_label() {
		echo '
		<div id="da_general_label">
			<input type="text" class="regular-text" name="download_attachments_general[label]" value="' . esc_attr( Download_Attachments()->options['label'] ) . '"/>
			<br/>
			<p class="description">' . esc_html__( 'Enter download attachments list label.', 'download-attachments' ) . '</p>
		</div>';
	}

	public function da_general_post_types() {
		echo '
		<div id="da_general_post_types">
			<fieldset>';

			foreach ( $this->post_types as $val ) {
				echo '
			<input id="da-general-post-types-' . $val . '" type="checkbox" name="download_attachments_general[post_types][]" value="' . esc_attr( $val ) . '" ' . checked( true, (isset( Download_Attachments()->options['post_types'][$val] ) ? Download_Attachments()->options['post_types'][$val] : false ), false ) . '/><label for="da-general-post-types-' . $val . '">' . $val . '</label>';
			}

			echo '
				<p class="description">' . __( 'Select which post types would you like to enable for your downloads.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_download_method() {
		echo '
		<div id="da_general_download_method">
			<fieldset>';

			foreach ( $this->download_methods as $val => $trans ) {
				echo '
			<input id="da-general-download-method-' . $val . '" type="radio" name="download_attachments_general[download_method]" value="' . esc_attr( $val ) . '" ' . checked( $val, Download_Attachments()->options['download_method'], false ) . '/><label for="da-general-download-method-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select download method.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_backend_display() {
		echo '
		<div id="da_general_backend_display">
			<fieldset>';

			foreach ( Download_Attachments()->columns as $val => $trans ) {
				if ( ! in_array( $val, array( 'title', 'index', 'icon', 'exclude' ), true ) )
					echo '
			<input id="da-general-backend-display-' . $val . '" type="checkbox" name="download_attachments_general[backend_columns][]" value="' . esc_attr( $val ) . '" ' . checked( true, Download_Attachments()->options['backend_columns'][$val], false ) . '/><label for="da-general-backend-display-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select which columns would you like to enable on backend for your downloads.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_frontend_display() {
		echo '
		<div id="da_general_frontend_display">
			<fieldset>';

			foreach ( Download_Attachments()->columns as $val => $trans ) {
				if ( ! in_array( $val, array( 'id', 'type', 'title', 'exclude' ), true ) )
					echo '
			<input id="da-general-frontend-display-' . $val . '" type="checkbox" name="download_attachments_general[frontend_columns][]" value="' . esc_attr( $val ) . '" ' . checked( true, Download_Attachments()->options['frontend_columns'][$val], false ) . '/><label for="da-general-frontend-display-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select which columns would you like to enable on frontend for your downloads.', 'download-attachments' ) . '</p>
			<fieldset>
		</div>';
	}

	public function da_general_css_style() {
		echo '
		<div id="da_general_css_style">
			<fieldset>';

			foreach ( $this->choices as $val => $trans ) {
				echo '
			<input id="da-general-css-style-' . $val . '" type="radio" name="download_attachments_general[use_css_style]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), Download_Attachments()->options['use_css_style'], false ) . '/><label for="da-general-css-style-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select if you\'d like to use bultin CSS style.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_pretty_urls() {
		echo '
		<div id="da_general_pretty_urls">
			<fieldset>';

			foreach ( $this->choices as $val => $trans ) {
				echo '
			<input id="da-general-pretty-urls-' . $val . '" type="radio" name="download_attachments_general[pretty_urls]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), Download_Attachments()->options['pretty_urls'], false ) . '/><label for="da-general-pretty-urls-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Enable if you want to use pretty URLs.', 'download-attachments' ) . '</p>
			<div id="da_general_download_link"' . ( ! Download_Attachments()->options['pretty_urls'] ? ' style="display: none;"' : '') . '>
				<label for="da_general_download_link_label">' . __( 'Slug', 'download-attachments' ) . '</label>: <input id="da_general_download_link_label" type="text" name="download_attachments_general[download_link]" class="regular-text" value="' . esc_attr( Download_Attachments()->options['download_link'] ) . '"/>
				<p class="description"><code>' . site_url() . '/<strong>' . Download_Attachments()->options['download_link'] . '</strong>/123/</code></p>
				<p class="description">' . __( 'Download link slug.', 'download-attachments' ) . '</p>	
			</div>
			</fieldset>
		</div>';
	}
	
	public function da_general_encrypt_urls() {
		echo '
		<div id="da_general_pretty_urls">
			<fieldset>';

			foreach ( $this->choices as $val => $trans ) {
				echo '
			<input id="da-general-encrypt-urls-' . $val . '" type="radio" name="download_attachments_general[encrypt_urls]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), isset( Download_Attachments()->options['encrypt_urls'] ) ? Download_Attachments()->options['encrypt_urls'] : Download_Attachments()->defaults['general']['encrypt_urls'] , false ) . '/><label for="da-general-encrypt-urls-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Enable if you want to encrypt the attachment ids in generated URL\'s', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_download_box_display() {
		echo '
		<div id="da_general_download_box_display">
			<fieldset>';

			foreach ( $this->download_box_displays as $val => $trans ) {
				echo '
			<input id="da-general-download-box-display-' . $val . '" type="radio" name="download_attachments_general[download_box_display]" value="' . esc_attr( $val ) . '" ' . checked( $val, Download_Attachments()->options['download_box_display'], false ) . '/><label for="da-general-download-box-display-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select where you would like your download attachments to be displayed.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_display_style() {
		echo '
		<div id="da_general_display_style">
			<fieldset>';

			foreach ( $this->display_styles as $val => $trans ) {
				echo '
			<input id="da-general-display-style-' . $val . '" type="radio" name="download_attachments_general[display_style]" value="' . esc_attr( $val ) . '" ' . checked( $val, isset( Download_Attachments()->options['display_style'] ) ? esc_attr( Download_Attachments()->options['display_style'] ) : Download_Attachments()->defaults['general']['display_style'], false ) . '/><label for="da-general-display-style-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select display style for file attachments.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_backend_content() {
		echo '
		<div id="da_general_backend_content">
			<fieldset>';

			foreach ( $this->contents as $val => $trans ) {
				echo '
			<input id="da-general-backend-content-' . $val . '" type="checkbox" name="download_attachments_general[backend_content][]" value="' . esc_attr( $val ) . '" ' . checked( true, (isset( Download_Attachments()->options['backend_content'][$val] ) ? Download_Attachments()->options['backend_content'][$val] : false ), false ) . '/><label for="da-general-backend-content-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select what fields to use on backend for download attachments description.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_frontend_content() {
		echo '
		<div id="da_general_frontend_content">
			<fieldset>';

			foreach ( $this->contents as $val => $trans ) {
				echo '
			<input id="da-general-frontend-content-' . $val . '" type="checkbox" name="download_attachments_general[frontend_content][]" value="' . esc_attr( $val ) . '" ' . checked( true, (isset( Download_Attachments()->options['frontend_content'][$val] ) ? Download_Attachments()->options['frontend_content'][$val] : false ), false ) . '/><label for="da-general-frontend-content-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select what fields to use on frontend for download attachments description.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_attachment_link() {
		echo '
		<div id="da_general_attachment_link">
			<fieldset>';

			foreach ( $this->attachment_links as $val => $trans ) {
				echo '
			<input id="da-general-attachment-link-' . $val . '" type="radio" name="download_attachments_general[attachment_link]" value="' . esc_attr( $val ) . '" ' . checked( $val, Download_Attachments()->options['attachment_link'], false ) . '/><label for="da-general-attachment-link-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select where you would like to edit download attachments.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_libraries() {
		echo '
		<div id="da_general_libraries">
			<fieldset>';

			foreach ( $this->libraries as $val => $trans ) {
				echo '
			<input id="da-general-libraries-' . $val . '" type="radio" name="download_attachments_general[library]" value="' . esc_attr( $val ) . '" ' . checked( $val, Download_Attachments()->options['library'], false ) . '/><label for="da-general-libraries-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select which attachments should be visible in Media Library window.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_downloads_in_media_library() {
		echo '
		<div id="da_general_downloads_in_media_library">
			<fieldset>';

			foreach ( $this->choices as $val => $trans ) {
				echo '
			<input id="da-general-downloads-in-media-library-' . $val . '" type="radio" name="download_attachments_general[downloads_in_media_library]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), Download_Attachments()->options['downloads_in_media_library'], false ) . '/><label for="da-general-downloads-in-media-library-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Enable if you want to display downloads count in your Media Library columns.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_user_roles() {
		global $wp_roles;

		$editable_roles = get_editable_roles();
		
		echo '
		<div id="da_general_user_roles">
			<fieldset>';

			foreach ( $editable_roles as $val => $trans ) {
				$role = $wp_roles->get_role( $val );

				// admins have access by default
				if ( $role->has_cap( 'manage_options' ) )
					continue;
				
				echo '
				<input id="da-general-user-roles-' . $val . '" type="checkbox" name="download_attachments_general[user_roles][]" value="' . $val . '" ' . checked( true, in_array( $val, ( isset( Download_Attachments()->options['user_roles'] ) ? Download_Attachments()->options['user_roles'] : Download_Attachments()->defaults['general']['user_roles'] ) ), false ) . '/><label for="da-general-user-roles-' . $val . '">' . translate_user_role( $wp_roles->role_names[$val] ) . '</label>';
			}

			echo '
			<p class="description">' . __( 'Select user roles allowed to add, remove and manage attachments.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	public function da_general_deactivation_delete() {
		echo '
		<div id="da_general_deactivation_delete">
			<fieldset>';

			foreach ( $this->choices as $val => $trans ) {
				echo '
			<input id="da-general-deactivation-delete-' . $val . '" type="radio" name="download_attachments_general[deactivation_delete]" value="' . esc_attr( $val ) . '" ' . checked( ($val === 'yes' ? true : false ), Download_Attachments()->options['deactivation_delete'], false ) . '/><label for="da-general-deactivation-delete-' . $val . '">' . $trans . '</label>';
			}

			echo '
			<p class="description">' . __( 'Enable if you want all plugin data to be deleted on deactivation.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}
	
	public function da_general_reset_downloads() {
		echo '
		<div id="da_general_deactivation_delete">
			<fieldset>';
		
		submit_button( __( 'Reset downloads', 'download-attachments' ), 'secondary', 'reset_da_downloads', false );
		
		echo '
				<p class="description">' . __( 'Click to reset the downloads count for all the attachments.', 'download-attachments' ) . '</p>
			</fieldset>
		</div>';
	}

	/**
	 * Validate general settings, reset general settings, reset download counts.
	 * 
	 * @global object $wp_roles
	 * @global object $wpdb
	 * @param array $input
	 * @return array
	 */
	public function validate_general( $input ) {
		// get old input for saving tabs
		$old_input = Download_Attachments()->options;

		// save general
		if ( isset( $_POST['save_da_general'] ) ) {
			
			$new_input = $old_input;
			
			global $wp_roles;
			
			// label
			$new_input['label'] = sanitize_text_field( $input['label'] );
			
			// capabilities
			$user_roles = array();
			
			foreach ( $wp_roles->roles as $role_name => $role_text ) {
				$role = $wp_roles->get_role( $role_name );

				if ( ! $role->has_cap( 'manage_options' ) ) {
					if ( in_array( $role_name, array_map( 'esc_attr', $input['user_roles'] ) ) ) {
						$role->add_cap( Download_Attachments()->capability );
						$user_roles[] = $role_name;
					} else {
						$role->remove_cap( Download_Attachments()->capability );
					}
				}
			}

			$new_input['user_roles'] = $user_roles;
			
			// post types
			$post_types = array();
			$new_input['post_types'] = (isset( $input['post_types'] ) ? $input['post_types'] : array());

			foreach ( $this->post_types as $post_type ) {
				$post_types[$post_type] = (in_array( $post_type, $input['post_types'], true ) ? true : false);
			}

			$new_input['post_types'] = $post_types;
			
			// download method
			$new_input['download_method'] = isset( $input['download_method'], $this->download_methods[$input['download_method']] ) ? $input['download_method'] : Download_Attachments()->defaults['general']['download_method'];
			
			// pretty urls
			$new_input['pretty_urls'] = isset( $input['pretty_urls'], $this->choices[$input['pretty_urls']] ) ? ( $input['pretty_urls'] === 'yes' ? true : false ) : Download_Attachments()->defaults['general']['pretty_urls'];
			
			// download link
			if ( $input['pretty_urls'] ) {
				$new_input['download_link'] = sanitize_title( $input['download_link'] );

				if ( $new_input['download_link'] === '' )
					$new_input['download_link'] = Download_Attachments()->defaults['general']['download_link'];
			} else
				$new_input['download_link'] = Download_Attachments()->defaults['general']['download_link'];
			
			// encrypt urls
			$new_input['encrypt_urls'] = isset( $input['encrypt_urls'], $this->choices[$input['encrypt_urls']] ) ? ( $input['encrypt_urls'] === 'yes' ? true : false ) : Download_Attachments()->defaults['general']['encrypt_urls'];
			
			// deactivation delete
			$new_input['deactivation_delete'] = (isset( $input['deactivation_delete'] ) && in_array( $input['deactivation_delete'], array_keys( $this->choices ), true ) ? ($input['deactivation_delete'] === 'yes' ? true : false) : Download_Attachments()->defaults['general']['deactivation_delete']);

			$input = $new_input;
		
		// save display
		} elseif ( isset( $_POST['save_da_display'] ) ) {
			
			$new_input = $old_input;

			// frontend columns
			$columns = array();
			$input['frontend_columns'] = (isset( $input['frontend_columns'] ) ? $input['frontend_columns'] : array());

			foreach ( Download_Attachments()->columns as $column => $text ) {
				if ( in_array( $column, array( 'id', 'type', 'exclude' ), true ) )
					continue;
				elseif ( $column === 'title' )
					$columns[$column] = true;
				else
					$columns[$column] = (in_array( $column, $input['frontend_columns'], true ) ? true : false);
			}

			$new_input['frontend_columns'] = $columns;
			
			// frontend content
			$contents = array();
			$input['frontend_content'] = (isset( $input['frontend_content'] ) ? $input['frontend_content'] : array());

			foreach ( $this->contents as $content => $trans ) {
				$contents[$content] = (in_array( $content, $input['frontend_content'], true ) ? true : false);
			}

			$new_input['frontend_content'] = $contents;
			
			// display style
			$new_input['display_style'] = isset( $input['display_style'], $this->display_styles[$input['display_style']] ) ? $input['display_style'] : Download_Attachments()->defaults['general']['display_style'];

			// use css style
			$new_input['use_css_style'] = (isset( $input['use_css_style'] ) && in_array( $input['use_css_style'], array_keys( $this->choices ), true ) ? ($input['use_css_style'] === 'yes' ? true : false) : Download_Attachments()->defaults['general']['use_css_style']);
			
			// download box display
			$new_input['download_box_display'] = (isset( $input['download_box_display'] ) && in_array( $input['download_box_display'], array_keys( $this->download_box_displays ), true ) ? $input['download_box_display'] : Download_Attachments()->defaults['general']['download_box_display']);
			
			$input = $new_input;
			
		// save admin
		} elseif ( isset( $_POST['save_da_admin'] ) ) {
			
			$new_input = $old_input;
			
			// backend columns
			$columns = array();
			$input['backend_columns'] = (isset( $input['backend_columns'] ) ? $input['backend_columns'] : array());

			foreach ( Download_Attachments()->columns as $column => $text ) {
				if ( in_array( $column, array( 'index', 'icon', 'exclude' ), true ) )
					continue;
				if ( $column === 'title' )
					$columns[$column] = true;
				else
					$columns[$column] = (in_array( $column, $input['backend_columns'], true ) ? true : false);
			}

			$new_input['backend_columns'] = $columns;
			
			// backend content
			$contents = array();
			$input['backend_content'] = (isset( $input['backend_content'] ) ? $input['backend_content'] : array());

			foreach ( $this->contents as $content => $trans ) {
				$contents[$content] = (in_array( $content, $input['backend_content'], true ) ? true : false);
			}

			$new_input['backend_content'] = $contents;
			
			// attachment link
			$new_input['attachment_link'] = (isset( $input['attachment_link'] ) && in_array( $input['attachment_link'], array_keys( $this->attachment_links ), true ) ? $input['attachment_link'] : Download_Attachments()->defaults['general']['attachment_link']);

			// library
			$new_input['library'] = (isset( $input['library'] ) && in_array( $input['library'], array_keys( $this->libraries ), true ) ? $input['library'] : Download_Attachments()->defaults['general']['library']);
			
			// downloads in media library
			$new_input['downloads_in_media_library'] = (isset( $input['downloads_in_media_library'] ) && in_array( $input['downloads_in_media_library'], array_keys( $this->choices ), true ) ? ($input['downloads_in_media_library'] === 'yes' ? true : false) : Download_Attachments()->defaults['general']['downloads_in_media_library']);
			
			$input = $new_input;
			
		// reset general
		} elseif ( isset( $_POST['reset_da_general'] ) ) {
			$new_input = $old_input;
			
			global $wp_roles;

			// capabilities
			$new_input['user_roles'] = array();
			
			foreach ( $wp_roles->roles as $role_name => $display_name ) {
				$role = $wp_roles->get_role( $role_name );

					if ( $role->has_cap( 'upload_files' ) ) {
						$role->add_cap( $capability );
						
						$new_input['user_roles'][] = $role_name;
					} else {
						$role->remove_cap( $capability );
					}
			}
			
			$keys = array(
				'label',
				'download_method',
				'post_types',
				'pretty_urls',
				'download_link',
				'encrypt_urls',
				'deactivation_delete'
			);

			foreach( $keys as $key ) {
				if ( array_key_exists( $key, Download_Attachments()->defaults['general'] ) ) {
					$new_input[$key] = Download_Attachments()->defaults['general'][$key];
				}
			}
			
			$input = $new_input;

			add_settings_error( 'reset_general_settings', 'reset_general_settings', __( 'General settings restored to defaults.', 'download-attachments' ), 'updated' );
			
		// reset display
		} elseif ( isset( $_POST['reset_da_display'] ) ) {
			$new_input = $old_input;

			$keys = array(
				'frontend_columns',
				'display_style',
				'frontend_content',
				'use_css_style',
				'download_box_display'
			);

			foreach( $keys as $key ) {
				if ( array_key_exists( $key, Download_Attachments()->defaults['general'] ) ) {
					$new_input[$key] = Download_Attachments()->defaults['general'][$key];
				}
			}
			
			$input = $new_input;

			add_settings_error( 'reset_display_settings', 'reset_display_settings', __( 'Display settings restored to defaults.', 'download-attachments' ), 'updated' );
			
		// reset admin
		} elseif ( isset( $_POST['reset_da_admin'] ) ) {
			$new_input = $old_input;

			$keys = array(
				'backend_columns',
				'backend_content',
				'attachment_link',
				'library',
				'downloads_in_media_library'
			);

			foreach( $keys as $key ) {
				if ( array_key_exists( $key, Download_Attachments()->defaults['general'] ) ) {
					$new_input[$key] = Download_Attachments()->defaults['general'][$key];
				}
			}
			
			$input = $new_input;

			add_settings_error( 'reset_admin_settings', 'reset_admin_settings', __( 'Admin settings restored to defaults.', 'download-attachments' ), 'updated' );
			
		// reset downloads
		} elseif ( isset( $_POST['reset_da_downloads'] ) ) {
			global $wpdb;

			// lets use wpdb to reset downloads a lot faster then normal update_post_meta for each post_id
			$result = $wpdb->update(
			$wpdb->postmeta, array( 'meta_value' => 0 ), array( 'meta_key' => '_da_downloads' ), '%d', '%s'
			);

			$input = Download_Attachments()->options;

			if ( $result === false )
				add_settings_error( 'reset_downloads', 'reset_downloads_error', __( 'Error occurred while resetting the downloads count.', 'download-attachments' ), 'error' );
			else
				add_settings_error( 'reset_downloads', 'reset_downloads_updated', __( 'Attachments downloads count has been reset.', 'download-attachments' ), 'updated' );
		}

		return $input;
	}

}

new Download_Attachments_Settings();
