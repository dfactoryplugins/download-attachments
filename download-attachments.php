<?php
/*
Plugin Name: Download Attachments
Description: Download Attachments is a new approach to managing downloads in WordPress. It allows you to easily add and display download links in any post or page.
Version: 1.2.24
Author: dFactory
Author URI: http://dfactory.eu/
Plugin URI: http://dfactory.eu/plugins/download-attachments/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: download-attachments
Domain Path: /languages

Download Attachments
Copyright (C) 2013-2020, Digital Factory - info@digitalfactory.pl

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Download_Attachments' ) ) :

	/**
	 * Download_Attachments final class.
	 *
	 * @class Download_Attachments
	 * @version 1.2.24
	 */
	class Download_Attachments {

		private static $instance;
		public $capability = 'manage_download_attachments';
		public $columns = array();
		public $display_styles = array();
		public $options = array();
		public $defaults = array(
			'general'	 => array(
				// general
				'label'							=> 'Attachments',
				'user_roles'					=> array(),
				'download_method'				=> 'force',
				'link_target'					=> '_self',
				'post_types'					=> array(
					'page'	=> true, 
					'post'	=> true
				),
				'pretty_urls'					=> false,
				'download_link'					=> 'download-attachment',
				'encrypt_urls'					=> false,
				'deactivation_delete'			=> false,
				// display
				'frontend_columns'				=> array(
					'index'		=> false,
					'author'	=> false,
					'icon'		=> true,
					'size'		=> true,
					'date'		=> false,
					'downloads'	=> true
				),
				'display_style'					=> 'list',
				'frontend_content'				=> array(
					'caption'		=> true,
					'description'	=> false
				),
				'use_css_style'					=> true,
				'download_box_display'			=> 'after_content',
				// admin
				'backend_columns'				=> array(
					'id'		=> true,
					'author'	=> false,
					'title'		=> true,
					'type'		=> true,
					'size'		=> true,
					'date'		=> false,
					'downloads'	=> true
				),
				'backend_content'				=> array(
					'caption'		=> true,
					'description'	=> false
				),
				'restrict_edit_downloads'		=> false,
				'attachment_link'				=> 'modal',
				'library'						=> 'all',
				'downloads_in_media_library'	=> true
			),
			'version'	=> '1.2.24'
		);

		/**
		 * Class constructor.
		 *
		 * @return void
		 */
		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'multisite_activation' ) );
			register_deactivation_hook( __FILE__, array( $this, 'multisite_deactivation' ) );

			// settings
			$this->options = array_merge( $this->defaults['general'], get_option( 'download_attachments_general', $this->defaults['general'] ) );

			// actions
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'after_setup_theme', array( $this, 'load_defaults' ) );
			add_action( 'admin_head', array( $this, 'button_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			add_action( 'send_headers', array( $this, 'download_redirect' ) );

			// filters
			add_action( 'wp', array( $this, 'run' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_extend_links' ), 10, 2 );
			add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 10, 2 );
		}

		/**
		 * Disable object cloning.
		 *
		 * @return void
		 */
		public function __clone() {}

		/**
		 * Disable unserializing of the class.
		 *
		 * @return void
		 */
		public function __wakeup() {}

		/**
		 * Main plugin instance,
		 * Insures that only one instance of Post_Ratings exists in memory at one time.
		 *
		 * @return object
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
				self::$instance = new self();
				self::$instance->define_constants();

				add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

				self::$instance->includes();
			}

			return self::$instance;
		}

		/**
		 * Setup plugin constants.
		 *
		 * @return void
		 */
		private function define_constants() {
			define( 'DOWNLOAD_ATTACHMENTS_URL', plugins_url( '', __FILE__ ) );
			define( 'DOWNLOAD_ATTACHMENTS_PATH', plugin_dir_path( __FILE__ ) );
			define( 'DOWNLOAD_ATTACHMENTS_REL_PATH', dirname( plugin_basename( __FILE__ ) ) . '/' );
		}

		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes() {
			if ( is_admin() ) {
				include_once( DOWNLOAD_ATTACHMENTS_PATH . 'includes/settings.php' );
				include_once( DOWNLOAD_ATTACHMENTS_PATH . 'includes/update.php' );
				include_once( DOWNLOAD_ATTACHMENTS_PATH . 'includes/metabox.php' );
				include_once( DOWNLOAD_ATTACHMENTS_PATH . 'includes/media.php' );
			}

			include_once( DOWNLOAD_ATTACHMENTS_PATH . 'includes/functions.php' );
			include_once( DOWNLOAD_ATTACHMENTS_PATH . 'includes/shortcodes.php' );
			include_once( DOWNLOAD_ATTACHMENTS_PATH . 'includes/widgets.php' );
		}

		/**
		 * Multisite activation.
		 *
		 * @global object $wpdb
		 * @param bool $networkwide
		 * @return void
		 */
		public function multisite_activation( $networkwide ) {
			if ( is_multisite() && $networkwide ) {
				global $wpdb;

				$activated_blogs = array();
				$current_blog_id = $wpdb->blogid;
				$blogs_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT blog_id FROM ' . $wpdb->blogs, '' ) );

				foreach ( $blogs_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->activate_single();
					$activated_blogs[] = (int) $blog_id;
				}

				switch_to_blog( $current_blog_id );
				update_site_option( 'download_attachments_activated_blogs', $activated_blogs, array() );
			} else
				$this->activate_single();
		}

		/**
		 * Single site activation.
		 *
		 * @global object $wp_roles
		 * @return void
		 */
		public function activate_single() {
			global $wp_roles;

			// add caps to administrators
			foreach ( $wp_roles->roles as $role_name => $display_name ) {
				$role = $wp_roles->get_role( $role_name );

				if ( $role->has_cap( 'upload_files' ) ) {
					$role->add_cap( $this->capability );
				}
			}

			// add default options
			add_option( 'download_attachments_general', $this->defaults['general'], '', 'no' );
			add_option( 'download_attachments_version', $this->defaults['version'], '', 'no' );
		}

		/**
		 * Multisite deactivation.
		 *
		 * @global object $wpdb
		 * @param bool $networkwide
		 * @return void
		 */
		public function multisite_deactivation( $networkwide ) {
			if ( is_multisite() && $networkwide ) {
				global $wpdb;

				$current_blog_id = $wpdb->blogid;
				$blogs_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT blog_id FROM ' . $wpdb->blogs, '' ) );

				if ( ($activated_blogs = get_site_option( 'download_attachments_activated_blogs', false, false )) === false )
					$activated_blogs = array();

				foreach ( $blogs_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					$this->deactivate_single( true );

					if ( in_array( (int) $blog_id, $activated_blogs, true ) )
						unset( $activated_blogs[array_search( $blog_id, $activated_blogs )] );
				}

				switch_to_blog( $current_blog_id );
				update_site_option( 'download_attachments_activated_blogs', $activated_blogs );
			} else
				$this->deactivate_single();
		}

		/**
		 * Single site deactivation.
		 *
		 * @global object $wp_roles
		 * @param bool $multi
		 * @return void
		 */
		public function deactivate_single( $multi = false ) {
			global $wp_roles;

			// remove capabilities
			foreach ( $wp_roles->roles as $role_name => $display_name ) {
				$role = $wp_roles->get_role( $role_name );

				$role->remove_cap( $this->capability );
			}

			if ( $multi ) {
				$options = get_option( 'download_attachments_general' );
				$check = $options['deactivation_delete'];
			} else
				$check = $this->options['deactivation_delete'];

			if ( $check )
				delete_option( 'download_attachments_general' );
		}

		/**
		 * Pass variables to other classes.
		 *
		 * @return void
		 */
		public function load_defaults() {
			$this->columns = array(
				'index'		 => __( 'Index', 'download-attachments' ),
				'id'		 => __( 'ID', 'download-attachments' ),
				'exclude'	 => __( 'Exclude', 'download-attachments' ),
				'author'	 => __( 'Added by', 'download-attachments' ),
				'title'		 => __( 'Title', 'download-attachments' ),
				'type'		 => __( 'File type', 'download-attachments' ),
				'icon'		 => __( 'Icon', 'download-attachments' ),
				'size'		 => __( 'Size', 'download-attachments' ),
				'date'		 => __( 'Date added', 'download-attachments' ),
				'downloads'	 => __( 'Downloads', 'download-attachments' )
			);
			$this->display_styles = array(
				'list'		=> __( 'List', 'download-attachments' ),
				'table'		=> __( 'Table', 'download-attachments' ),
				'dynatable'	=> __( 'Dynamic Table', 'download-attachments' )
				// 'posts'		=> __( 'Posts', 'download-attachments' )
			);
		}

		/**
		 * Load text domain.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'download-attachments', false, DOWNLOAD_ATTACHMENTS_REL_PATH . 'languages/' );
		}

		/**
		 * Recognize download URL.
		 * 
		 * @global object $wp
		 * @return void
		 */
		public function download_redirect() {
			if ( $this->options['pretty_urls'] ) {
				global $wp;
				
				// encrypt enabled
				if ( isset( $this->options['encrypt_urls'] ) && $this->options['encrypt_urls'] ) {
					$pattern = '/^' . $this->options['download_link'] . '\/(\X+)$/';
					
					if ( preg_match( $pattern, $wp->request, $vars ) === 1 ) {
						// allow for id customization
						$id = apply_filters( 'da_download_attachment_id', $vars[1] );

						da_download_attachment( (int) da_decrypt_attachment_id( $id ) );
					}
				// no encryption
				} else {
					$pattern = '/^' . $this->options['download_link'] . '\/(\d+)$/';
					
					if ( preg_match( $pattern, $wp->request, $vars ) === 1 ) {
						// allow for id customization
						$id = apply_filters( 'da_download_attachment_id', $vars[1] );
						
						da_download_attachment( (int) $id );
					}
				}
			}
		}

		/**
		 * Run the shortcode on specific filter.
		 *
		 * @return void
		 */
		public function run() {
			if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) )
				return;

			$filter = apply_filters( 'da_shortcode_filter_hook', 'the_content' );

			if ( ! empty( $filter ) && is_string( $filter ) )
				add_filter( $filter, array( $this, 'add_content' ) );
		}

		/**
		 * Add frontend attachments box.
		 *
		 * @param mixed $content
		 * @return mixed
		 */
		public function add_content( $content ) {
			// hold display var
			$display = true;

			// check for post type
			if ( ! in_array( get_post_type(), array_keys( $this->options['post_types'], true ) ) || $this->options['download_box_display'] === 'manually' )
				$display = false;

			// check if singular
			if ( ! is_singular() )
				$display = false;

			// check if in the loop
			// if ( ! in_the_loop() )
			// $display = false;
			// we don't want to insert our html in excerpts...
			if ( in_array( current_filter(), array( 'get_the_excerpt', 'the_excerpt' ) ) )
				$display = false;

			// decide whether to add shortcode or not
			if ( (bool) apply_filters( 'da_add_shortcode_to_content', $display ) === false )
				return $content;

			$args = '';

			foreach ( $this->options['frontend_columns'] as $column => $bool ) {
				switch ( $column ) {
					case 'icon':
					case 'size':
					case 'date':
						$args .= ' display_' . $column . '="' . ( $bool === true ? 1 : 0 ) . '"';
						break;

					case 'author':
						$args .= ' display_user="' . ( $bool === true ? 1 : 0 ) . '"';
						break;

					case 'downloads':
						$args .= ' display_count="' . ( $bool === true ? 1 : 0 ) . '"';
						break;
				}
			}

			$args = apply_filters( 'da_add_shortcode_to_content_args', $args );

			// after content
			if ( $this->options['download_box_display'] === 'after_content' )
				return $content . do_shortcode( '[download-attachments' . $args . ']' );
			// before content
			else
				return do_shortcode( '[download-attachments' . $args . ']' ) . $content;
		}

		/**
		 * Add scripts and styles to backend.
		 *
		 * @global object $post
		 * @param string $page
		 * @return void
		 */
		public function admin_enqueue_scripts( $page ) {
			wp_register_style( 'download-attachments-admin', DOWNLOAD_ATTACHMENTS_URL . '/css/admin.css' );

			// settings
			if ( $page === 'settings_page_download-attachments' ) {
				wp_register_script( 'download-attachments-admin-settings', DOWNLOAD_ATTACHMENTS_URL . '/js/admin-settings.js', array( 'jquery' ) );

				wp_localize_script(
					'download-attachments-admin-settings',
					'daArgs',
					array(
						'resetToDefaults'			=> __( 'Are you sure you want to reset these settings to defaults?', 'download-attachments' ),
						'resetDownloadsToDefaults'	=> __( 'Are you sure you want to reset number of downloads of all attachments?', 'download-attachments' )
					)
				);

				wp_enqueue_script( 'download-attachments-admin-settings' );
				wp_enqueue_style( 'download-attachments-admin' );
			// metabox
			} elseif ( in_array( $page, array( 'post.php', 'post-new.php' ), true ) ) {
				wp_register_script( 'da-admin-datatables', DOWNLOAD_ATTACHMENTS_URL . '/assets/datatables/datatables' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.js', [], '1.13.8' );
				wp_register_script( 'da-admin-post', DOWNLOAD_ATTACHMENTS_URL . '/js/admin-post.js', [ 'jquery', 'da-admin-datatables' ], $this->defaults['version'] );
				wp_register_style( 'da-admin-datatables', DOWNLOAD_ATTACHMENTS_URL . '/assets/datatables/datatables' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', [], '1.13.8' );

				$columnTypes = [];

				// number of columns
				$no_columns = 0;

				// get changeable admin columns
				$backend_columns = $this->options['backend_columns'];

				// all possible admin columns
				$columns = [ 'drag', 'id', 'exclude', 'author', 'title', 'type', 'size', 'date', 'downloads', 'actions' ];

				foreach ( $columns as $column ) {
					switch ( $column ) {
						case 'drag':
							$columnTypes[] = [ 'orderable' => false ];
							break;
							
						case 'id':
							if ( $backend_columns[$column] )
								$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];
							break;

						case 'exclude':
							$columnTypes[] = [ 'orderable' => false ];
							break;

						case 'author':
							if ( $backend_columns[$column] )
								$columnTypes[] = [ 'orderable' => true ];
							break;

						case 'title':
							$columnTypes[] = [ 'orderable' => true ];
							break;

						case 'type':
							if ( $backend_columns[$column] )
								$columnTypes[] = [ 'orderable' => true ];
							break;

						case 'size':
							if ( $backend_columns[$column] )
								$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];
							break;

						case 'date':
							if ( $backend_columns[$column] )
								$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];
							break;

						case 'downloads':
							if ( $backend_columns[$column] )
								$columnTypes[] = [ 'orderable' => true, 'type' => 'num' ];
							break;

						case 'actions':
							$columnTypes[] = [ 'orderable' => false ];
							break;
					}
				}

				foreach ( $backend_columns as $bool ) {
					if ( $bool === true )
						$no_columns++;
				}

				// prepare script data
				$script_data = [
					'addTitle'				=> __( 'Select Attachments', 'download-attachments' ),
					'editTitle'				=> __( 'Edit attachment', 'download-attachments' ),
					'buttonAddNewFile'		=> __( 'Add selected attachments', 'download-attachments' ),
					'buttonEditFile'		=> __( 'Save attachment', 'download-attachments' ),
					'selectTitle'			=> __( 'Insert download link', 'download-attachments' ),
					'buttonInsertLink'		=> __( 'Insert into post', 'download-attachments' ),
					'noFiles'				=> __( 'No attachments added yet.', 'download-attachments' ),
					'deleteFile'			=> __( 'Do you want to remove this attachment?', 'download-attachments' ),
					'removeFile'			=> __( 'Remove', 'download-attachments' ),
					'editFile'				=> __( 'Edit', 'download-attachments' ),
					'activeColumns'			=> $no_columns + 3,
					'columnTypes'			=> $columnTypes,
					'internalUnknownError'	=> __( 'Unexpected error occured. Please refresh the page and try again.', 'download-attachments' ),
					'library'				=> ( $this->options['library'] === 'all' ? 1 : 0 ),
					'addNonce'				=> wp_create_nonce( 'da-add-file-nonce-' . ( isset( $post->ID ) ? $post->ID : 0 ) ),
					'saveNonce'				=> wp_create_nonce( 'da-save-files-nonce-' . ( isset( $post->ID ) ? $post->ID : 0 ) ),
					'attachmentLink'		=> $this->options['attachment_link']
				];

				wp_add_inline_script( 'da-admin-post', 'var daArgsPost = ' . wp_json_encode( $script_data ) . ";\n", 'before' );

				wp_enqueue_media( [ 'post' => ( isset( $post->ID ) ? (int) $post->ID : 0 ) ] );
				wp_enqueue_style( 'da-admin' );
				wp_enqueue_style( 'da-admin-datatables' );
				wp_enqueue_script( 'da-admin-post' );

				if ( $post->post_type === 'attachment' )
					wp_enqueue_script( 'da-admin-attachment', DOWNLOAD_ATTACHMENTS_URL . '/js/admin-attachment.js', [ 'jquery' ], $this->defaults['version'] );
			}
		}

		/**
		 * Add scripts and styles to frontend.
		 *
		 * @return void
		 */
		public function wp_enqueue_scripts() {
			if ( $this->options['use_css_style'] === true ) {
				if ( $this->options['display_style'] === 'dynatable' )
					wp_enqueue_style( 'da-frontend', DOWNLOAD_ATTACHMENTS_URL . '/assets/datatables/datatables' . ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '' ) . '.css', [], '1.13.8' );
				else
					wp_enqueue_style( 'da-frontend', DOWNLOAD_ATTACHMENTS_URL . '/css/frontend.css', [], $this->defaults['version'] );
			}
		}

		/**
		 * Editor button init.
		 *
		 * @global string $post_type
		 * @global string $pagenow
		 * @return void
		 */
		public function button_init() {
			global $post_type, $pagenow;

			// check for admin page
			if ( $pagenow != 'post-new.php' && $pagenow !== 'post.php' )
				return;

			$post_type_obj = get_post_type_object( $post_type );

			if ( ! current_user_can( $post_type_obj->cap->edit_posts ) )
				return;

			// check if WYSIWYG is enabled
			if ( get_user_option( 'rich_editing' ) == 'true' ) {
				add_filter( 'mce_buttons', array( $this, 'filter_mce_button' ) );
				add_filter( 'mce_external_plugins', array( $this, 'filter_mce_plugin' ) );
				// add_filter( 'mce_external_languages', array( $this, 'filter_mce_lang') );
			}
		}

		/**
		 * Add tinymce toolbar button.
		 *
		 * @param array	$buttons
		 * @return array
		 */
		public function filter_mce_button( $buttons ) {
			array_push( $buttons, 'download_attachments' );

			return $buttons;
		}

		/**
		 * Handle tinymce button.
		 *
		 * @param array	$plugins
		 * @return array
		 */
		public function filter_mce_plugin( $plugins ) {
			$plugins['download_attachments'] = plugins_url( 'js/tinymce-plugin.js', __FILE__ );

			return $plugins;
		}

		/**
		 * Handle button languages.
		 *
		 * @param array	$locales
		 * @return array
		 */
		public function filter_mce_lang( $locales ) {
			$locales['download_attachments'] = plugin_dir_path( __FILE__ ) . 'includes/tinymce-lang.php';

			return $locales;
		}

		/**
		 * Add links to Support Forum.
		 *
		 * @param array $links
		 * @param string $file
		 * @return array
		 */
		public function plugin_extend_links( $links, $file ) {
			if ( ! current_user_can( 'install_plugins' ) )
				return $links;

			if ( $file === plugin_basename( __FILE__ ) )
				return array_merge( $links, array( sprintf( '<a href="http://www.dfactory.eu/support/forum/download-attachments/" target="_blank">%s</a>', __( 'Support', 'download-attachments' ) ) ) );

			return $links;
		}

		/**
		 * Add links to Settings page.
		 *
		 * @param array $links
		 * @param string $file
		 * @return array
		 */
		public function plugin_settings_link( $links, $file ) {
			if ( ! current_user_can( 'manage_options' ) )
				return $links;

			if ( $file === plugin_basename( __FILE__ ) )
				array_unshift( $links, sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php' ) . '?page=download-attachments', __( 'Settings', 'download-attachments' ) ) );

			return $links;
		}

	}

endif; // end if class_exists check

/**
 * Initialise plugin.
 * 
 * @return object
 */
function Download_Attachments() {
	static $instance;

	// first call to instance() initializes the plugin
	if ( $instance === null || ! ( $instance instanceof Download_Attachments ) )
		$instance = Download_Attachments::instance();

	return $instance;
}

Download_Attachments();