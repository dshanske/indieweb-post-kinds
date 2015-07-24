<?php
/**
 * Post Kinds
 *
 * @link http://indiewebcamp.com/Post_Kinds_Plugin
 * @package Post Kinds
 * Plugin Name: Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 2.0.2
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: Post kinds
 */

define( 'POST_KINDS_VERSION', '2.0.2' );

load_plugin_textdomain( 'Post kind', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

if ( ! defined( 'POST_KIND_INCLUDE' ) )
	define( 'POST_KIND_INCLUDE' , '' );

if ( ! defined( 'MULTIKIND' ) )
	define( 'MULTIKIND', '0' );

// Add Kind Taxonomy.
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-taxonomy.php' );

// Config Settings.
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-config.php' );

// Add a Settings Link to the Plugins Page.
$plugin = plugin_basename( __FILE__ );
add_filter( 'plugin_action_links_$plugin', array( 'kind_config', 'settings_link' ) );


// Add Kind Post Metadata.
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-postmeta.php' );

// Add Kind Core Functions.
require_once( plugin_dir_path( __FILE__ ) . '/includes/kind-functions.php' );
// Add Kind Display Functions.
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-view.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-display.php' );

// Add Kind Meta Display Functions.
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-meta.php' );

if ( ! class_exists( 'ogp\Parser' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-ogp-parser.php' );}

// Load stylesheets.
add_action( 'wp_enqueue_scripts', 'kindstyle_load' );
add_action( 'admin_enqueue_scripts', 'kind_admin_style' );

/**
 * Loads the Stylesheet for the Plugin.
 *
 */
function kindstyle_load() {
        wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.min.css', array(), POST_KINDS_VERSION );
  }

/**
 * Loads the Admin Only Stylesheet for the Plugin.
 *
 */
function kind_admin_style() {
    wp_enqueue_style( 'kind-admin', plugins_url( 'css/kind-admin.min.css', __FILE__), array(), POST_KINDS_VERSION );
}

// Add a notice to the Admin if the Webmentions Plugin isn't Activated.
add_action( 'admin_notices', 'postkind_plugin_notice' );


/**
 * Adds a notice if the Webmentions Plugin is Not Installed.
 *
 */
function postkind_plugin_notice() {
	if ( ! class_exists( 'WebMentionPlugin' ) ) {
			echo '<div class="error"><p>';
			echo '<a href="https://wordpress.org/plugins/webmention/">';
			esc_html_e( 'This Plugin Requires the WordPress Webmention Plugin', 'post_kinds' );
			echo '</a></p></div>';
		}
}


/**
 * Returns the Domain Name out of a URL.
 *
 * @param string $url URL
 *
 * @return string domain name
 */
if ( ! function_exists( 'extract_domain_name' ) ) {
    function extract_domain_name( $url ) {
      $host = parse_url( $url, PHP_URL_HOST );
      $host = preg_replace( '/^www\./', '', $host );
      return $host;
    }
  }

/**
 * Returns True if Array is Multidimensional.
 *
 * @param array $arr array
 *
 * @return boolean result
 */
if ( ! function_exists( 'is_multi_array') ) {
	function is_multi_array( $arr ) {
		if ( count( $arr ) == count( $arr, COUNT_RECURSIVE ) ) return false;
		else return true;
	}
}

/**
 * Array_Filter for multi-dimensional arrays.
 *
 * @param array $array 
 * @param function $callback
 * @return array
 */
if (!  function_exists( 'array_filter_recursive' ) ) {
	function array_filter_recursive( $array, $callback = null ) {
		foreach ( $array as $key => & $value ) {
			if ( is_array( $value ) ) {
				$value = array_filter_recursive( $value, $callback );
			}
			else {
				if ( ! is_null( $callback ) ) {
					if ( ! $callback( $value ) ) {
						unset( $array[ $key ] );
					}
				}
				else {
					if ( ! ( bool ) $value ) {
						unset( $array[ $key ] );
					}
				}
			}
		}
		unset( $value );
		return $array;
	}
} ?>
