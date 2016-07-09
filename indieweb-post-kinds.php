<?php
/**
 * Post Kinds
 *
 * @link    http://indiewebcamp.com/Post_Kinds_Plugin
 * @package Post Kinds
 * Plugin Name: Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 2.3.7
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: Post kinds
 */

define( 'POST_KINDS_VERSION', '2.3.7' );

if ( ! defined( 'MULTIKIND' ) ) {
	define( 'MULTIKIND', false );
}

add_action( 'plugins_loaded', array( 'Post_Kinds_Plugin', 'init' ) );

class Post_Kinds_Plugin {
	public static function init() {
		load_plugin_textdomain( 'Post kind', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		// Add Kind Taxonomy.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-taxonomy.php';
		add_action( 'init' , array( 'Kind_Taxonomy', 'init' ) );
		// Register Kind Taxonomy.
		add_action( 'init', array( 'Kind_Taxonomy', 'register' ), 1 );

		// On Activation, add terms.
		register_activation_hook( __FILE__, array( 'Kind_Taxonomy', 'activate_kinds' ) );

		// Config Settings.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-config.php';
		add_action( 'init' , array( 'Kind_Config', 'init' ) );

		// Add a Settings Link to the Plugins Page.
		$plugin = plugin_basename( __FILE__ );
		add_filter( 'plugin_action_links_$plugin', array( 'Post_Kinds_Plugin', 'settings_link' ) );
		
		// Add Kind Post UI Configuration
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-tabmeta.php';
		add_action( 'init' , array( 'Kind_Tabmeta', 'init' ) );

		// Add Kind Global Functions.
		require_once plugin_dir_path( __FILE__ ) . '/includes/kind-functions.php';
		
		// Add Kind Display Functions.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-view.php';
		add_action( 'init' , array( 'Kind_View', 'init' ) );

		// Add Kind Meta Storage and Retrieval Functions.
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-meta.php';
		
		// Add an MF2 Parser
		if ( version_compare( PHP_VERSION, '5.3', '>' ) ) {
			if ( ! class_exists( 'Mf2\Parser' ) ) {
				require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/Parser.php';
				require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/functions.php';
				require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/Twitter.php';
			}
		}
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-mf2-cleaner.php';
		
		// Add Link Preview Parsing
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
		$option = get_option( 'iwt_options', Kind_Config::Defaults() );
		if ( ! isset( $option['themecompat'] ) ) {
			wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.min.css', array(), POST_KINDS_VERSION );
		} else {
			wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.themecompat.min.css', array(), POST_KINDS_VERSION );
		}
	}
	
	/**
	 * Loads the Admin Stylesheet for the Plugin.
	 */
	public static function admin_style_load() {
		wp_enqueue_style( 'kind-admin', plugin_dir_url( __FILE__ ) . 'css/kind.admin.min.css', array(), POST_KINDS_VERSION );
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

function tz_seconds_to_offset($seconds) {
	return ($seconds < 0 ? '-' : '+') . sprintf( '%02d:%02d', abs( $seconds / 60 / 60 ), abs( $seconds / 60 ) % 60 );
}
function tz_offset_to_seconds($offset) {
	if ( preg_match( '/([+-])(\d{2}):?(\d{2})/', $offset, $match ) ) {
		$sign = ($match[1] == '-' ? -1 : 1);
		return (($match[2] * 60 * 60) + ($match[3] * 60)) * $sign;
	} else {
		return 0;
	}
}

function kind_get_timezones() {

	$o = array();

	$t_zones = timezone_identifiers_list();

	foreach ( $t_zones as $a ) {
		$t = '';

		try {
			// this throws exception for 'US/Pacific-New'
			$zone = new DateTimeZone( $a );

			$seconds = $zone->getOffset( new DateTime( 'now' , $zone ) );
			$o[] = tz_seconds_to_offset( $seconds );
		} // exceptions must be catched, else a blank page
		catch (Exception $e) {
			// die("Exception : " . $e->getMessage() . '<br />');
			// what to do in catch ? , nothing just relax
		}
	}
	$o = array_unique( $o );
	asort( $o );

	return $o;
}

?>
