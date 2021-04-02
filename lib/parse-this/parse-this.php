<?php
/**
 * Plugin Name: Parse This
 * Plugin URI: https://github.com/dshanske/parse-this
 * Description:
 * Version: 1.0.1
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: parse-this
 * Domain Path:  /languages
 */


/*
 Parse This Load
 */

if ( ! function_exists( 'parse_this_loader' ) ) {
	function parse_this_loader() {
		require_once plugin_dir_path( __FILE__ ) . 'includes/autoload.php';

		// Functions Not Available in Earlier Versions of WordPress
		require_once plugin_dir_path( __FILE__ ) . 'includes/compat-functions.php';

		require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
		// Parse This REST Endpoint
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-rest-parse-this.php';

	}
	add_action( 'plugins_loaded', 'parse_this_loader', 9 );
}

