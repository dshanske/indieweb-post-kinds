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
						<p> <?php esc_html_e( 'Test the Parse Tools Debugger. You can report sites to the developer for possibly improvement in future', 'indieweb-post-kinds' ); ?>
						</p>
						<hr />
			<form method="get" action="<?php echo esc_url( rest_url( '/parse-this/1.0/parse/' ) ); ?> ">
			<label for="url"><?php esc_html_e( 'URL', 'indieweb-post-kinds' ); ?></label><input type="url" class="widefat" name="url" id="url" />
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
					'callback'            => array( $this, 'read' ),
					'args'                => array(
						'url' => array(
							'required'          => true,
							'validate_callback' => array( $this, 'is_valid_url' ),
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
		$url    = $request->get_param( 'url' );
		$mf2    = $request->get_param( 'mf2' );
		$parse  = new Parse_This( $url );
		$return = $parse->fetch();
		if ( is_wp_error( $return ) ) {
			return $return;
		}
		$parse->parse();
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
