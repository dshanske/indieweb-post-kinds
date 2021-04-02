<?php
/**
 * Provides REST Endpoint to Retrieve the Parsed Data
 */

class REST_Parse_This {
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
		add_management_page(
			__( 'Parse This', 'indieweb-post-kinds' ), // page title
			__( 'Parse This', 'indieweb-post-kinds' ), // menu title
			'manage_options', // access capability
			'parse_this',
			array( $this, 'debug' )
		);
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
						<p> <?php esc_html_e( 'Test the Parse Tools Debugger. You can report sites to the developer for possibly improvement in future.', 'parse-this' ); ?>
							<a href="https://github.com/dshanske/parse-this/issues"><?php esc_html_e( 'Open an Issue', 'parse-this' ); ?></a>
						</p>

							<p>
							<?php
							if ( is_plugin_active( 'parse-this/parse-this.php' ) ) {
								esc_html_e( 'You are using the plugin version of Parse This as opposed to a version built into any plugin', 'parse-this' );
							}
							?>
						<hr />
			<form method="get" action="<?php echo esc_url( rest_url( '/parse-this/1.0/parse/' ) ); ?> ">
				<p>
					<label for="url"><?php esc_html_e( 'URL', 'indieweb-post-kinds' ); ?></label><input type="url" class="widefat" name="url" id="url" />
				</p>
				<table class="form-table" role="presentation">
					<tbody>
					<tr>
						<th scope="row">
							<label for="mf2"><?php esc_html_e( 'MF2', 'indieweb-post-kinds' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="mf2" id="mf2" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="discovery"><?php esc_html_e( 'Feed Discovery', 'indieweb-post-kinds' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="discovery" id="discovery" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="discovery"><?php esc_html_e( 'References', 'indieweb-post-kinds' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="references" id="references" checked />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="location"><?php esc_html_e( 'Clean up Location', 'indieweb-post-kinds' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="location" id="location" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for"return"><?php esc_html_e( 'Return Type', 'indieweb-post-kinds' ); ?></label>
						</th>
						<td>
							<select name="return">
								<option value="single"><?php esc_html_e( 'Single', 'indieweb-post-kinds' ); ?></option>
								<option value="feed"><?php esc_html_e( 'Feed', 'indieweb-post-kinds' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="follow"><?php esc_html_e( 'Follow Author Links', 'indieweb-post-kinds' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="follow" id="follow" />
						</td>
					</tr>
					</tbody>
				</table>
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
		$cls = get_called_class();
		register_rest_route(
			'parse-this/1.0',
			'/parse',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $cls, 'read' ),
					'args'                => array(
						'url' => array(
							'required'          => true,
							'validate_callback' => array( $cls, 'is_valid_url' ),
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
		$refs      = $request->get_param( 'references' );
		$discovery = $request->get_param( 'discovery' );
		$location  = $request->get_param( 'location' );
		$follow    = $request->get_param( 'follow' );
		if ( $discovery ) {
			$parse = new Parse_This_Discovery();
			return $parse->fetch( $url );
		}
		$parse = new Parse_This( $url );
		$r     = $parse->fetch();

		if ( is_wp_error( $r ) ) {
			return $r;
		}
		$parse->parse(
			array(
				'return'     => $return,
				'follow'     => $follow,
				'references' => $refs,
				'location'   => $location,
			)
		);
		if ( $mf2 ) {
			return $parse->get( 'mf2' );
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

new REST_Parse_This();
