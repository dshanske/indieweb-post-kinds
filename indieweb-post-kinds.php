<?php
/**
 * Post Kinds
 *
 * @link    http://indieweb.org/Post_Kinds_Plugin
 * @package Post_Kinds
 * Plugin Name: Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 3.7.3
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: David Shanske
 * Author URI: https://david.shanske.com
 * Text Domain: indieweb-post-kinds
 * Domain Path:  /languages
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'POST_KINDS_KSES' ) ) {
	define( 'POST_KINDS_KSES', false );
}

spl_autoload_register(
	function ( $cls ) {
		$base_dir = trailingslashit( __DIR__ ) . 'includes/';
		$bases    = array( 'Kind', 'Post_Kind', 'MF2' );

		foreach ( $bases as $base ) {
			if ( strncmp( $cls, $base, strlen( $base ) ) === 0 ) {
				$filename = 'class-' . strtolower( str_replace( '_', '-', $cls ) );
				$file     = $base_dir . $filename . '.php';
				if ( file_exists( $file ) ) {
					require $file;
				}
			}
		}
	}
);

register_activation_hook( __FILE__, array( 'Post_Kinds_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Post_Kinds_Plugin', 'deactivate' ) );
add_action( 'upgrader_process_complete', array( 'Post_Kinds_Plugin', 'upgrader_process_complete' ), 10, 2 );


if ( Post_Kinds_Plugin::show_editor_error() ) {
	add_action( 'admin_notices', array( 'Post_Kinds_Plugin', 'classic_editor_error' ) );
}

add_action( 'plugins_loaded', array( 'Post_Kinds_Plugin', 'plugins_loaded' ), 11 );
add_action( 'init', array( 'Post_Kinds_Plugin', 'init' ) );

/**
 * Loads the plugin, its assets and its global functions.
 */
class Post_Kinds_Plugin {
	/**
	 * Plugin version, read from the plugin header on init.
	 *
	 * @since 2.4.0
	 * @var string
	 */
	public static $version;

	/**
	 * Sets the version and registers the kind taxonomy.
	 *
	 * @since 2.4.0
	 */
	public static function init() {
		self::$version = get_file_data( __FILE__, array( 'Version' => 'Version' ) )['Version'];
		// Add Kind Taxonomy.
		Kind_Taxonomy::init();
		Kind_Taxonomy::register();
	}

	/**
	 * Outputs an admin notice when Parse This is not available at all.
	 *
	 * @since 3.1.0
	 */
	public static function parse_this_error() {
		wp_admin_notice(
			esc_html__( 'Parse This is not installed. Please advise the developer', 'indieweb-post-kinds' ),
			array( 'type' => 'error' )
		);
	}

	/**
	 * Outputs an admin notice when another plugin loaded a copy of Parse This
	 * older than 2.0.0.
	 *
	 * Post Kinds still works, because its bundled 2.0 API is loaded alongside the
	 * older copy, but the older copy serves the parse-this REST route used by the
	 * reply metabox.
	 *
	 * @since 4.0.0
	 */
	public static function parse_this_outdated_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		wp_admin_notice(
			sprintf(
				/* translators: %s: Name of the plugin, or path of the file, that loaded the older copy of Parse This. */
				esc_html__( 'Post Kinds: %s is loading a copy of Parse This older than version 2.0. Post Kinds will keep working, but URL lookups in the post editor use that older copy. Updating that plugin, or installing the Parse This plugin (version 2.0 or later), resolves this.', 'indieweb-post-kinds' ),
				'<strong>' . esc_html( self::parse_this_source() ) . '</strong>'
			),
			array( 'type' => 'warning' )
		);
	}

	/**
	 * Returns whether the loaded copy of Parse This is version 2.0.0 or later.
	 *
	 * Copies older than 2.0.0 do not define PARSE_THIS_VERSION.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public static function parse_this_is_current() {
		return defined( 'PARSE_THIS_VERSION' ) && version_compare( PARSE_THIS_VERSION, '2.0.0', '>=' );
	}

	/**
	 * Returns a name for whatever loaded Parse This: the plugin's name, or the
	 * file's path relative to the content directory if it isn't a plugin.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public static function parse_this_source() {
		if ( ! function_exists( 'parse_this_loader' ) ) {
			return '';
		}
		$file = wp_normalize_path( ( new ReflectionFunction( 'parse_this_loader' ) )->getFileName() );
		$dir  = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );
		if ( 0 === strpos( $file, $dir ) ) {
			$slug = strtok( substr( $file, strlen( $dir ) ), '/' );
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			foreach ( get_plugins() as $basename => $data ) {
				if ( 0 === strpos( $basename, $slug . '/' ) ) {
					return $data['Name'];
				}
			}
			return $slug;
		}
		return str_replace( trailingslashit( wp_normalize_path( WP_CONTENT_DIR ) ), '', $file );
	}

	/**
	 * Whether to check for the block editor and warn about it.
	 *
	 * Not on ClassicPress, or when the Classic Editor plugin is active.
	 *
	 * @since 3.3.4
	 *
	 * @return bool
	 */
	public static function show_editor_error() {
		// Do not show this error in ClassicPress.
		if ( self::is_classicpress() ) {
			return false;
		}
		if ( class_exists( 'Classic_Editor' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Whether the site runs ClassicPress.
	 *
	 * @since 3.6.0
	 *
	 * @return bool
	 */
	public static function is_classicpress() {
		return function_exists( 'classicpress_version' );
	}

	/**
	 * Outputs an admin notice on screens that use the block editor.
	 *
	 * Post Kinds works only with the Classic Editor. The notice is not shown
	 * if the Classic Editor or Disable Gutenberg plugin is active.
	 *
	 * @since 3.2.6
	 *
	 * @return string|void An empty string if no notice is needed.
	 */
	public static function classic_editor_error() {
		if ( ! self::post_uses_gutenberg() ) {
			return '';
		}

		if ( class_exists( 'DisableGutenberg' ) || class_exists( 'Classic_Editor' ) ) {
			return '';
		}

		$class   = 'notice notice-error';
		$message = __( 'Classic Editor or Disable Gutenberg Plugin is not active. The Post Kinds plugin will not function correctly at this time without using the Classic Editor.', 'indieweb-post-kinds' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}


	/**
	 * Flushes the rewrite rules after Post Kinds is updated.
	 *
	 * @since 3.4.19
	 *
	 * @param WP_Upgrader $upgrade_object The upgrader.
	 * @param array       $options        Details of the update.
	 */
	public static function upgrader_process_complete( $upgrade_object, $options ) {
		$current_plugin_path_name = plugin_basename( __FILE__ );
		if ( ( 'update' === $options['action'] ) && ( 'plugin' === $options['type'] ) && array_key_exists( 'plugins', $options ) ) {
			foreach ( $options['plugins'] as $each_plugin ) {
				if ( $each_plugin === $current_plugin_path_name ) {
					flush_rewrite_rules();
				}
			}
		}
	}

	/**
	 * Creates the kind terms and flushes the rewrite rules on activation.
	 *
	 * @since 3.3.0
	 */
	public static function activate() {
		Kind_Taxonomy::activate_kinds();
		flush_rewrite_rules();
	}

	/**
	 * Flushes the rewrite rules on deactivation.
	 *
	 * @since 3.3.0
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Loads the translations, global functions and Parse This, and adds the hooks.
	 *
	 * @since 2.5.2
	 */
	public static function plugins_loaded() {
		$cls = get_called_class();
		load_plugin_textdomain( 'indieweb-post-kinds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Polyfills for functions newer than the plugin's minimums.
		require_once plugin_dir_path( __FILE__ ) . 'includes/compat.php';

		// Add Kind Global Functions.
		require_once plugin_dir_path( __FILE__ ) . '/includes/kind-functions.php';

		// Add Time Global Functions.
		require_once plugin_dir_path( __FILE__ ) . '/includes/time-functions.php';

		/*
		 * Parse This. The standalone plugin, if active, has already loaded at
		 * plugins_loaded priority 9 and defined parse_this_loader(). Otherwise
		 * load the bundled copy. Its loader sets up the classes, the functions
		 * and the REST route.
		 */
		$parse_this = plugin_dir_path( __FILE__ ) . 'lib/parse-this/parse-this.php';
		if ( ! function_exists( 'parse_this_loader' ) && file_exists( $parse_this ) ) {
			require_once $parse_this;
			parse_this_loader();
		}

		if ( ! function_exists( 'parse_this_loader' ) ) {
			add_action( 'admin_notices', array( $cls, 'parse_this_error' ) );
		} elseif ( ! self::parse_this_is_current() ) {
			/*
			 * Another plugin loaded a copy older than 2.0.0. Load the bundled 2.0
			 * API alongside it: everything in 2.0 is in the ParseThis namespace,
			 * so it does not clash with the old copy's global names, and both
			 * versions only load their bundled Mf2\Parser and Masterminds\HTML5
			 * if those classes do not already exist.
			 */
			$includes = plugin_dir_path( __FILE__ ) . 'lib/parse-this/includes/';
			if ( file_exists( $includes . 'autoload.php' ) ) {
				require_once $includes . 'autoload.php';
				require_once $includes . 'functions.php';
			}
			add_action( 'admin_notices', array( $cls, 'parse_this_outdated_notice' ) );
		}
		$class_load = array(
			'Plugins', // Plugin Specific Customization.
			'Media_Metadata', // Media Metadata Enhancements.
			'Config', // Configuration Menu.
			'Metabox', // Metabox for Classic Editor.
			'View', // Kind Display Functionality.
			'Upgrade', // One-time upgrade of stored data.
		);

		foreach ( $class_load as $load ) {
			add_action( 'init', array( 'Kind_' . $load, 'init' ) );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'post-kinds upgrade', array( 'Kind_Upgrade', 'cli' ) );
		}

		// Add a Settings Link to the Plugins Page.
		$plugin = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$plugin", array( 'Post_Kinds_Plugin', 'settings_link' ) );

		// Load stylesheets.
		add_action( 'wp_enqueue_scripts', array( $cls, 'style_load' ) );
		add_action( 'admin_enqueue_scripts', array( $cls, 'admin_style_load' ) );

		// Load Privacy Declaration.
		add_action( 'admin_init', array( $cls, 'privacy_declaration' ) );

		// Register Widgets.
		add_action(
			'widgets_init',
			function () {
				register_widget( 'Kind_Menu_Widget' );
				register_widget( 'Kind_Post_Widget' );
			}
		);
	}

	/**
	 * Adds link to Plugin Page for Options Page.
	 *
	 * @since 2.4.0
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
	 *
	 * @since 2.4.0
	 */
	public static function style_load() {
		wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'css/kind.min.css', array(), self::$version );
	}

	/**
	 * Loads the Admin Stylesheet for the Plugin.
	 *
	 * @since 2.4.0
	 */
	public static function admin_style_load() {
		wp_enqueue_style( 'kind-admin', plugin_dir_url( __FILE__ ) . 'css/kind.admin.min.css', array(), self::$version );
	}

	/**
	 * Adds suggested text to the privacy policy guide.
	 *
	 * @since 3.0.5
	 */
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

	/**
	 * Whether the current screen uses the block editor.
	 *
	 * Screens other than the post editor count as using it.
	 *
	 * @since 3.3.2
	 *
	 * @return bool
	 */
	public static function post_uses_gutenberg() {
		$screen = get_current_screen();
		if ( ! is_object( $screen ) || 'post' !== $screen->base ) {
			return true;
		}

		return $screen->is_block_editor;
	}
}
