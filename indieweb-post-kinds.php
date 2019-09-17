<?php
/**
 * Post Kinds
 *
 * @link    http://indieweb.org/Post_Kinds_Plugin
 * @package Post Kinds
 * Plugin Name: Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 3.3.3
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: indieweb-post-kinds
 * Domain Path:  /languages
 */

if ( ! defined( 'POST_KINDS_KSES' ) ) {
	define( 'POST_KINDS_KSES', false );
}

spl_autoload_register(
	function ( $class ) {
		$base_dir = trailingslashit( __DIR__ ) . 'includes/';
		$bases    = array( 'Kind', 'Post_Kind' );

		foreach ( $bases as $base ) {
			if ( strncmp( $class, $base, strlen( $base ) ) === 0 ) {
				$filename = 'class-' . strtolower( str_replace( '_', '-', $class ) );
				$file     = $base_dir . $filename . '.php';
				if ( file_exists( $file ) ) {
					require $file;
				}
			}
		}
	}
);

register_activation_hook( __FILE__, array( 'Kind_Taxonomy', 'activate_kinds' ) );
register_deactivation_hook( __FILE__, array( 'Post_Kinds_Plugin', 'deactivate' ) );

if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'lib/parse-this/parse-this.php' ) ) {
	add_action( 'admin_notices', array( 'Post_Kinds_Plugin', 'parse_this_error' ) );
}

if ( ! class_exists( 'Classic_Editor' ) ) {
	add_action( 'admin_notices', array( 'Post_Kinds_Plugin', 'classic_editor_error' ) );
}

add_action( 'plugins_loaded', array( 'Post_Kinds_Plugin', 'plugins_loaded' ), 11 );
add_action( 'init', array( 'Post_Kinds_Plugin', 'init' ) );

class Post_Kinds_Plugin {
	public static $version = '3.3.3';
	public static function init() {
		// Add Kind Taxonomy.
		Kind_Taxonomy::init();
		Kind_Taxonomy::register();
	}

	public static function parse_this_error() {
		$class   = 'notice notice-error';
		$message = __( 'Parse This is not installed. Please advise the developer', 'indieweb-post-kinds' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function classic_editor_error() {
		if ( ! self::post_uses_gutenberg() ) {
			return '';
		}

		$class   = 'notice notice-error';
		$message = __( 'Classic Editor Plugin is not active. The Post Kinds plugin will not function correctly at this time without using the Classic Editor.', 'indieweb-post-kinds' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function activate() {
		Kind_Taxonomy::activate_kinds();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function plugins_loaded() {
		$cls = get_called_class();
		load_plugin_textdomain( 'indieweb-post-kinds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Add WordPress Compatibility File for Functions Introduced Post 4.9.9
		require_once plugin_dir_path( __FILE__ ) . 'includes/compat.php';

		// Add Kind Global Functions.
		require_once plugin_dir_path( __FILE__ ) . '/includes/kind-functions.php';

		// Add Time Global Functions.
		require_once plugin_dir_path( __FILE__ ) . '/includes/time-functions.php';

		// Parse This
		require_once plugin_dir_path( __FILE__ ) . 'lib/parse-this/includes/autoload.php';
		if ( ! class_exists( 'REST_Parse_This' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'lib/parse-this/includes/class-rest-parse-this.php';
		}
		require_once plugin_dir_path( __FILE__ ) . 'lib/parse-this/includes/functions.php';
		$class_load = array(
			'Plugins', // Plugin Specific Customization
			'Media_Metadata', // Media Metadata Enhancements
			'Config', // Configuration Menu
			'Metabox', // Metabox for Classic Editor
			'View', // Kind Display Functionality
		);

		foreach ( $class_load as $load ) {
			add_action( 'init', array( 'Kind_' . $load, 'init' ) );
		}

		// Add a Settings Link to the Plugins Page.
		$plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", array( 'Post_Kinds_Plugin', 'settings_link' ) );

		// Load stylesheets.
		add_action( 'wp_enqueue_scripts', array( $cls, 'style_load' ) );
		add_action( 'admin_enqueue_scripts', array( $cls, 'admin_style_load' ) );

		// Load Privacy Declaration
		add_action( 'admin_init', array( $cls, 'privacy_declaration' ) );
		remove_all_actions( 'do_feed_rss2' );
		remove_all_actions( 'do_feed_atom' );
		add_action( 'do_feed_rss2', array( $cls, 'do_feed_rss2' ), 10, 1 );
		add_action( 'do_feed_atom', array( $cls, 'do_feed_atom' ), 10, 1 );

		// Register Widgets
		add_action(
			'widgets_init',
			function() {
				register_widget( 'Kind_Menu_Widget' );
				register_widget( 'Kind_Post_Widget' );
			}
		);
	}

	public static function do_feed_atom( $for_comments ) {
		if ( $for_comments ) {
			load_template( plugin_dir_path( __FILE__ ) . 'templates/feed-atom-comments.php' );
		} else {
			load_template( plugin_dir_path( __FILE__ ) . 'templates/feed-atom.php' );
		}
	}

	public static function do_feed_rss2( $for_comments ) {
		if ( $for_comments ) {
			load_template( plugin_dir_path( __FILE__ ) . 'templates/feed-rss2-comments.php' );
		} else {
			load_template( plugin_dir_path( __FILE__ ) . 'templates/feed-rss2.php' );
		}
	}

	/**
	 * Adds link to Plugin Page for Options Page.
	 *
	 * @access public
	 * @param array $links Array of Existing Links.
	 * @return array Modified Links.
	 */
	public static function settings_link( $links ) {
		// Because of how Kind_Config::admin_menu() is set up, the settings page
		// can be located at two different URLs; menu_page_url() finds both.
		$settings_url  = menu_page_url( 'kind_options', false );
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			$settings_url,
			__( 'Settings', 'indieweb-post-kinds' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Loads the Stylesheet for the Plugin.
	 */
	public static function style_load() {
		wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.min.css', array(), self::$version );
	}

	/**
	 * Loads the Admin Stylesheet for the Plugin.
	 */
	public static function admin_style_load() {
		wp_enqueue_style( 'kind-admin', plugin_dir_url( __FILE__ ) . 'css/kind.admin.min.css', array(), self::$version );
	}

	public static function privacy_declaration() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = __(
				'For responses to URLs, such as responding to a post or article, this site allows the storage of data around the post/article in order to generate a rich citation. Items such as author name and image, summary of the text, embed provided by third-party site, etc may be stored and are solely to provide this context. We will remove any of this on request.',
				'indieweb-post-kinds'
			);
			wp_add_privacy_policy_content(
				'Post Kinds',
				wp_kses_post( wpautop( $content, false ) )
			);
		}
	}

	public static function post_uses_gutenberg() {
		$screen = get_current_screen();
		if ( ! is_object( $screen ) || 'post' !== $screen->base ) {
			return true;
		}

		return $screen->is_block_editor;
	}
}

if ( ! function_exists( 'ifset' ) ) {
	/**
	 * If set, return otherwise false.
	 *
	 * @param type $var Check if set.
	 * @return $var|false Return either $var or $return.
	 */
	function ifset( &$var, $return = false ) {

		return isset( $var ) ? $var : $return;
	}
}
