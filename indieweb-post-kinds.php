<?php
/**
 * Plugin Name: IndieWeb Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 2.0.0
 * Author: David Shanske
 * Author URI: http://david.shanske.com
 * Text Domain: Post kinds
 */

load_plugin_textdomain( 'Post kind', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 

if ( ! defined( 'POST_KIND_INCLUDE' ) )
    define('POST_KIND_INCLUDE', '');

if ( ! defined( 'MULTIKIND' ) )
    define('MULTIKIND', '0');

// Add Kind Taxonomy
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-taxonomy.php');

 // Config Settings
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-config.php');

// Add a Settings Link to the Plugins Page
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", array('kind_config', 'settings_link') );


// Add Kind Post Metadata
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-postmeta.php');

// Add Kind Core Functions
require_once( plugin_dir_path( __FILE__ ) . '/includes/kind-functions.php');
// Add Kind Display Functions
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-view.php');
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-display.php');

// Add Kind Meta Display Functions
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-kind-meta.php');

if(!class_exists ("ogp\Parser")) {
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-ogp-parser.php');
	}

// Load stylesheets
add_action( 'wp_enqueue_scripts', 'kindstyle_load' );
add_action('admin_enqueue_scripts', 'kind_admin_style');

function kindstyle_load() {
        wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.min.css');
  }

function kind_admin_style() {
    wp_enqueue_style('kind-admin', plugins_url('css/kind-admin.min.css', __FILE__));
}

// Add a notice to the Admin Pages if the WordPress Webmentions Plugin isn't Activated
add_action( 'admin_notices', 'postkind_plugin_notice' );

function postkind_plugin_notice() {
	if (!class_exists("WebMentionPlugin"))
		{
			echo '<div class="error"><p>';
			echo '<a href="https://wordpress.org/plugins/webmention/">';
			esc_html_e( 'This Plugin Requires the WordPress Webmention Plugin', 'post_kinds' );
			echo '</a></p></div>';
		}
}


// Extracts the Domain Name for a URL for presentation purposes
if (!function_exists('extract_domain_name')) {
    function extract_domain_name($url) {
      $host = parse_url($url, PHP_URL_HOST);
      $host = preg_replace("/^www\./", "", $host);
      return $host;
    }
  }

if (!function_exists('is_multi_array') ) {
	function is_multi_array( $arr ) {
  	rsort( $arr );
  	return isset( $arr[0] ) && is_array( $arr[0] );
	}
}

if (!function_exists('array_filter_recursive') ) {
	function array_filter_recursive($array, $callback = null) {
		foreach ($array as $key => & $value) {
			if (is_array($value)) {
				$value = array_filter_recursive($value, $callback);
			}
			else {
				if ( ! is_null($callback)) {
					if ( ! $callback($value)) {
						unset($array[$key]);
					}
				}
				else {
					if ( ! (bool) $value) {
						unset($array[$key]);
					}
				}
			}
		}
		unset($value);
		return $array;
	}
}

?>
