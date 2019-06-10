<?php
/**
 * Gathers Data for Link Previews
 *
 * Parses Arbitrary URLs
 */

class Parse_This_API {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

		/**
		 * Adds Options Page for Plugin Options.
		 *
		 * @access public
		 */
	public function admin_menu() {
		// If the IndieWeb Plugin is installed use its menu.
		if ( class_exists( 'IndieWeb_Plugin' ) ) {
			add_submenu_page(
				'indieweb',
				__( 'Parse This', 'indieweb-post-kinds' ), // page title
				__( 'Parse This', 'indieweb-post-kinds' ), // menu title
				'manage_options', // access capability
				'parse_this',
				array( $this, 'debug' )
			);
		} else {
			add_management_page( '', __( 'Post Kinds', 'indieweb-post-kinds' ), 'manage_options', 'parse_this', array( $this, 'debug' ) );
		}
	}

	/**
	 * Generate Debug Tool
	 *
	 * @access public
	 */
	public static function debug() {
		?>
				<div class="wrap">
						<h2> <?php esc_html_e( 'Parse This Debugger', 'indieweb-post-kinds' ); ?> </h2>
						<p> <?php esc_html_e( 'Test the Parse Tools Debugger. You can report sites to the developer for possibly improvement in future', 'parse-this' ); ?>
						</p>
						<a href="https://github.com/dshanske/parse-this/issues"><?php esc_html_e( 'Open an Issue', 'parse-this' ); ?></a>
							<p> 
							<?php
							if ( is_plugin_active( 'parse-this/parse-this.php' ) ) {
								esc_html_e( 'You are using the plugin version of Parse This as opposed to a version built into any plugin', 'parse-this' );
							}
							?>
						<hr />
			<form method="get" action="<?php echo esc_url( rest_url( '/parse-this/1.0/parse/' ) ); ?> ">
			<p><label for="url"><?php esc_html_e( 'URL', 'indieweb-post-kinds' ); ?></label><input type="url" class="widefat" name="url" id="url" /></p>
			<p><label for="mf2"><?php esc_html_e( 'MF2', 'indieweb-post-kinds' ); ?></label><input type="checkbox" name="mf2" id="mf2" /></p>
			<p><label for="discovery"><?php esc_html_e( 'Feed Discovery', 'indieweb-post-kinds' ); ?></label><input type="checkbox" name="discovery" id="discovery" /></p>
			<p><label for"return"><?php esc_html_e( 'Return Type', 'indieweb-post-kinds' ); ?></label>
				<select name="return">
					<option value="single"><?php esc_html_e( 'Single', 'indieweb-post-kinds' ); ?></option>
					<option value="feed"><?php esc_html_e( 'Feed', 'indieweb-post-kinds' ); ?></option>
				</select>
			</p>
			<p><label for="follow"><?php esc_html_e( 'Follow Author Links', 'indieweb-post-kinds' ); ?></label><input type="checkbox" name="follow" id="follow" /></p>
			<?php wp_nonce_field( 'wp_rest' ); ?>
			<?php submit_button( __( 'Parse', 'indieweb-post-kinds' ) ); ?>
						</form>
				</div>
				<?php
	}


	/**
	 * Register the Route.
	 */
	public static function register_routes() {
		register_rest_route(
			'parse-this/1.0',
			'/parse',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'Parse_This_API', 'read' ),
					'args'                => array(
						'url' => array(
							'required'          => true,
							'validate_callback' => array( 'Parse_This_API', 'is_valid_url' ),
							'sanitize_callback' => 'esc_url_raw',
						),
					),
					'permission_callback' => function () {
						return current_user_can( 'read' );
					},
				),
			)
		);
	}

	public static function read( $request ) {
		$url       = $request->get_param( 'url' );
		$mf2       = $request->get_param( 'mf2' );
		$return    = $request->get_param( 'return' );
		$discovery = $request->get_param( 'discovery' );
		$follow    = $request->get_param( 'follow' );
		if ( ! class_exists( 'Parse_This' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-parse-this.php';
		}

		$parse = new Parse_This( $url );
		if ( $discovery ) {
			return $parse->fetch_feeds();

		} else {
			$r = $parse->fetch();
		}
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		$parse->parse(
			array(
				'return' => $return,
				'follow' => $follow,
			)
		);
		if ( $mf2 ) {
			return jf2_to_mf2( $parse->get() );
		}
		return $parse->get();
	}

	/**
	 * Returns if valid URL for REST validation
	 *
	 * @param string $url
	 *
	 * @return boolean
	 */
	public static function is_valid_url( $url, $request = null, $key = null ) {
		return wp_http_validate_url( $url );
	}


	public static function addscheme( $url, $scheme = 'http://' ) {
		return wp_parse_url( $url, PHP_URL_SCHEME ) === null ? $scheme . $url : $url;
	}

}

new Parse_This_API();
