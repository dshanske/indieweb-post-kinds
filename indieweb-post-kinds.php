<?php
/**
 * Post Kinds
 *
 * @link    http://indiewebcamp.com/Post_Kinds_Plugin
 * @package Post Kinds
 * Plugin Name: Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 2.3.6
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: Post kinds
 */

define( 'POST_KINDS_VERSION', '2.3.6' );

load_plugin_textdomain( 'Post kind', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

if ( ! defined( 'MULTIKIND' ) ) {
	define( 'MULTIKIND', false );
}

// Add Kind Taxonomy.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-taxonomy.php';

// Config Settings.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-config.php';

// Add a Settings Link to the Plugins Page.
$plugin = plugin_basename( __FILE__ );
add_filter( 'plugin_action_links_$plugin', array( 'kind_config', 'settings_link' ) );


// Add Kind Post UI Configuration.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-tabmeta.php';


// Add Kind Global Functions.
require_once plugin_dir_path( __FILE__ ) . '/includes/kind-functions.php';
// Add Kind Display Functions.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-view.php';

// Add Kind Meta Storage and Retrieval Functions.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kind-meta.php';

// Add an MF2 Parser
if ( version_compare( phpversion(), 5.3, '<' ) ) {
	if ( ! class_exists( 'Mf2\Parser' ) ) {
  	require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/Parser.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/functions.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/Mf2/Twitter.php';
	}
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-mf2-cleaner.php';

// Add Link Preview Parsing
require_once plugin_dir_path( __FILE__ ) . 'includes/class-link-preview.php';


// Load stylesheets.
add_action( 'wp_enqueue_scripts', 'kindstyle_load' );
add_action( 'admin_enqueue_scripts', 'admin_kindstyle_load' );


/**
 * Loads the Stylesheet for the Plugin.
 */
if ( ! function_exists( 'kindstyle_load' ) ) {
	/**
	 * Loads Plugin Style Sheet.
	 */
	function kindstyle_load() {
    $option = get_option( 'iwt_options', Kind_Config::Defaults() );
		if ( ! isset($option['themecompat']) ) {
			wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.min.css', array(), POST_KINDS_VERSION );
		}
   else {
      wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.themecompat.min.css', array(), POST_KINDS_VERSION );
    }

	}
} else {
	die( 'You have another version of Post Kinds installed!' );
}

/**
 * Loads the Admin Stylesheet for the Plugin.
 */
if ( ! function_exists( 'admin_kindstyle_load' ) ) {
  /**
   * Loads Plugin Style Sheet.
   */
  function admin_kindstyle_load() {
    wp_enqueue_style( 'kind-admin', plugin_dir_url( __FILE__ ) . 'css/kind.admin.min.css', array(), POST_KINDS_VERSION );
  }
} else {
  die( 'You have another version of Post Kinds installed!' );
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
  return ($seconds < 0 ? '-' : '+') . sprintf('%02d:%02d', abs($seconds/60/60), abs($seconds/60)%60);
}
function tz_offset_to_seconds($offset) {
  if(preg_match('/([+-])(\d{2}):?(\d{2})/', $offset, $match)) {
    $sign = ($match[1] == '-' ? -1 : 1);
    return (($match[2] * 60 * 60) + ($match[3] * 60)) * $sign;
  } else {
    return 0;
  }
}

function kind_get_timezones()
{
    $o = array();
     
    $t_zones = timezone_identifiers_list();
     
    foreach($t_zones as $a)
    {
        $t = '';
         
        try
        {
            //this throws exception for 'US/Pacific-New'
            $zone = new DateTimeZone($a);
             
            $seconds = $zone->getOffset( new DateTime("now" , $zone) );
            $o[] = tz_seconds_to_offset($seconds);
        }
         
        //exceptions must be catched, else a blank page
        catch(Exception $e)
        {
            //die("Exception : " . $e->getMessage() . '<br />');
            //what to do in catch ? , nothing just relax
        }
    }
    $o = array_unique($o);
    asort($o);
     
    return $o;
} 

function kind_icon($slug) {
	if ( empty($slug) ) {
		return '';
	}
	$file = wp_remote_get( plugin_dir_url( __FILE__) . 'svg/' . $slug . '.svg' );
	// If it fails to retrieve, then retrieve website as the default icon
	if ( is_wp_error( $file ) ) {
		$file = wp_remote_get( plugin_dir_url( __FILE__) . 'svg/' . 'website.svg' );
	}
	$icon = '<span class="kind-icon">' . wp_remote_retrieve_body( $file ) . '</span>';
	return apply_filters('kind-icon', $icon, $slug);
}

?>
