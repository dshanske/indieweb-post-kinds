<?php
/**
 * Plugin Name: Parse This
 * Plugin URI: https://github.com/dshanske/parse-this
 * Description: Turns URLs into structured jf2 data from microformats2, JSON-LD, meta tags and RSS, Atom and JSON feeds.
 * Version: 2.0.0
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: parse-this
 *
 * @package Parse_This
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'parse_this_loader' ) ) {
	/**
	 * Loads Parse This.
	 *
	 * Runs on plugins_loaded at priority 9, so that the standalone plugin loads
	 * ahead of copies bundled in other plugins (Post Kinds loads its copy at
	 * priority 11). Functions are guarded by function_exists() and the first
	 * autoloader registered serves the classes, so whichever copy loads first
	 * wins. It also registers the deprecated global names in aliases.php, so
	 * that plugins bundling an older copy use this one.
	 *
	 * Defines PARSE_THIS_VERSION for the copy that loads, and fires
	 * parse_this_loaded once everything is available. A copy older than 2.0.0
	 * defines neither, so a plugin can tell when an older bundled copy won.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Defines PARSE_THIS_VERSION and fires parse_this_loaded.
	 */
	function parse_this_loader() {
		if ( ! defined( 'PARSE_THIS_VERSION' ) ) {
			/**
			 * Version of the copy of Parse This that loaded. Keep in step with the
			 * Version header.
			 *
			 * @since 2.0.0
			 * @var string
			 */
			define( 'PARSE_THIS_VERSION', '2.0.0' );
		}
		require_once plugin_dir_path( __FILE__ ) . 'includes/autoload.php';
		require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';

		new ParseThis\REST_Endpoint();

		/**
		 * Fires once Parse This has loaded: its classes, functions and REST route
		 * are available.
		 *
		 * Plugins that use Parse This can hook here, or check PARSE_THIS_VERSION,
		 * rather than depend on load order.
		 *
		 * @since 2.0.0
		 *
		 * @param string $version Version of the copy of Parse This that loaded.
		 */
		do_action( 'parse_this_loaded', PARSE_THIS_VERSION );
	}
	add_action( 'plugins_loaded', 'parse_this_loader', 9 );
}
