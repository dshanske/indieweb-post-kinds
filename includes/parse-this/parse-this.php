<?php
/**
 * Plugin Name: Parse This
 * Plugin URI: https://github.com/dshanske/parse-this
 * Description:
 * Version: 1.0
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: parse-this
 * Domain Path:  /languages
 */


/* Parse This Load
 */

if ( ! function_exists( 'parse_this_loader' ) ) {
	function parse_this_loader() {

		if ( ! class_exists( 'Mf2\Parser' ) ) {
			require_once dirname( __FILE__ ) . '/vendor/mf2/mf2/Mf2/Parser.php';
		}

		// Global Functions
		require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';

		// Convert Post to MF2 JSON
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-mf2-post.php';

		// Core Parse This Class
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-parse-this.php';
		// Parse This for OGP and HTML Properties
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-parse-this-html.php';

		// Parse This for Microformats 2
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-parse-this-mf2.php';

		// Parse This API
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-parse-this-api.php';
	}
	add_action( 'plugins_loaded', 'parse_this_loader', 11 );
}
