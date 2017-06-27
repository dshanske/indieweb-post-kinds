<?php
/**
 * Post Kinds
 *
 * @link    http://indieweb.org/Post_Kinds_Plugin
 * @package Post Kinds
 * Plugin Name: Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 2.6.3
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: indieweb-post-kinds
 * Domain Path:  /languages

 */

if ( ! defined( 'POST_KINDS_KSES' ) ) {
	define( 'POST_KINDS_KSES', false );
}



add_action( 'plugins_loaded', array( 'Post_Kinds_Plugin', 'plugins_loaded' ) );
add_action( 'init', array( 'Post_Kinds_Plugin', 'init' ) );

class Post_Kinds_Plugin {
	public static $version = '2.6.3';
	public static function init() {
		// Add Kind Taxonomy.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-taxonomy.php';
		Kind_Taxonomy::init();
		Kind_Taxonomy::register();
	}
	public static function plugins_loaded() {
		load_plugin_textdomain( 'indieweb-post-kinds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		// On Activation, add terms.
		register_activation_hook( __FILE__, array( 'Kind_Taxonomy', 'activate_kinds' ) );

		// Add Kind Global Functions.
		require_once plugin_dir_path( __FILE__ ) . '/includes/kind-functions.php';

		// Plugin Specific Kind Customizations
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-kind-plugins.php';
		add_action( 'init' , array( 'Kind_Plugins', 'init' ) );

		// Config Settings.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-config.php';
		add_action( 'init' , array( 'Kind_Config', 'init' ) );

		// Add a Settings Link to the Plugins Page.
		$plugin = plugin_basename( __FILE__ );
		add_filter( 'plugin_action_links_$plugin', array( 'Post_Kinds_Plugin', 'settings_link' ) );

		// Add Kind Post UI Configuration
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-tabmeta.php';
		add_action( 'init' , array( 'Kind_Tabmeta', 'init' ) );
		Kind_Tabmeta::$version = self::$version;

		// Add Kind Display Functions.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-view.php';
		add_action( 'init' , array( 'Kind_View', 'init' ) );

		// Add Kind Meta Storage and Retrieval Functions.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-meta.php';

		// Add an MF2 Parser
		if ( version_compare( PHP_VERSION, '5.3', '>' ) ) {
			if ( ! class_exists( 'Mf2\Parser' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/Parser.php';
			}
		  if ( ! function_exists( 'Mf2\xpcs' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/functions.php';
			}
			if ( ! class_exists( 'Mf2\Shim' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/Twitter.php';
			}
			require_once plugin_dir_path( __FILE__ ) . 'includes/class-parse-mf2.php';
		}
		// Add Link Preview Parsing
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-parse-this.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-link-preview.php';
		add_action( 'init' , array( 'Link_Preview', 'init' ) );

		// Load stylesheets.
		add_action( 'wp_enqueue_scripts', array( 'Post_Kinds_Plugin', 'style_load' ) );
		add_action( 'admin_enqueue_scripts', array( 'Post_Kinds_Plugin', 'admin_style_load' ) );
	}

	/**
	 * Adds link to Plugin Page for Options Page.
	 *
	 * @access public
	 * @param array $links Array of Existing Links.
	 * @return array Modified Links.
	 */
	public static function settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=kind_options">Settings</a>';
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
}

if ( ! function_exists( 'ifset' ) ) {
	/**
	 * If set, return otherwise false.
	 *
	 * @param type $var Check if set.
	 * @return $var|false Return either $var or false.
	 */
	function ifset(&$var) {

		return isset( $var ) ? $var : false;
	}
}

?>
