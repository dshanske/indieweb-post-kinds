<?php
/**
 * REST_Endpoint class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the parse REST endpoint and the Tools > Parse This debug page.
 *
 * Endpoint: GET /wp-json/parse-this/1.0/parse?url=...
 *
 * @since 1.0.0
 */
class REST_Endpoint {
	/**
	 * Hooks the route registration and the admin menu item.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Adds the Tools > Parse This page for users who can use the parse route.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Uses the route's capability (see required_capability()) instead
	 *              of manage_options.
	 */
	public function admin_menu() {
		add_management_page(
			__( 'Parse This', 'parse-this' ), // Page title.
			__( 'Parse This', 'parse-this' ), // Menu title.
			self::required_capability(), // Capability.
			'parse_this',
			array( $this, 'debug' )
		);
	}

	/**
	 * Renders the debug page.
	 *
	 * The form is sent to the parse endpoint by a script, with the wp_rest nonce
	 * in an X-WP-Nonce header rather than in the URL, and the result is shown on
	 * the page.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Submits with fetch() and shows the result on the page.
	 */
	public static function debug() {
		?>
				<div class="wrap">
						<h1> <?php esc_html_e( 'Parse This Debugger', 'parse-this' ); ?> </h1>
						<p> <?php esc_html_e( 'Test the Parse Tools Debugger. You can report sites to the developer for possibly improvement in future.', 'parse-this' ); ?>
							<a href="https://github.com/dshanske/parse-this/issues"><?php esc_html_e( 'Open an Issue', 'parse-this' ); ?></a>
						</p>

							<p>
							<?php
							if ( is_plugin_active( 'parse-this/parse-this.php' ) ) {
								esc_html_e( 'You are using the plugin version of Parse This as opposed to a version built into any plugin', 'parse-this' );
							}
							?>
							</p>
						<hr />
			<form id="parse-this-debug">
				<p>
					<label for="url"><?php esc_html_e( 'URL', 'parse-this' ); ?></label><input type="url" class="widefat" name="url" id="url" />
				</p>
				<table class="form-table" role="presentation">
					<tbody>
					<tr>
						<th scope="row">
							<label for="mf2"><?php esc_html_e( 'MF2', 'parse-this' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="mf2" id="mf2" value="1" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="discovery"><?php esc_html_e( 'Feed Discovery', 'parse-this' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="discovery" id="discovery" value="1" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="references"><?php esc_html_e( 'References', 'parse-this' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="references" id="references" value="1" checked />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="location"><?php esc_html_e( 'Clean up Location', 'parse-this' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="location" id="location" value="1" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="return"><?php esc_html_e( 'Return Type', 'parse-this' ); ?></label>
						</th>
						<td>
							<select name="return" id="return">
								<option value="single"><?php esc_html_e( 'Single', 'parse-this' ); ?></option>
								<option value="feed"><?php esc_html_e( 'Feed', 'parse-this' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="follow"><?php esc_html_e( 'Follow Author Links', 'parse-this' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="follow" id="follow" value="1" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="debug"><?php esc_html_e( 'Include Source Data', 'parse-this' ); ?></label>
						</th>
						<td>
							<input type="checkbox" name="debug" id="debug" value="1" checked />
						</td>
					</tr>
					</tbody>
				</table>
			<?php submit_button( __( 'Parse', 'parse-this' ) ); ?>
						</form>
			<pre id="parse-this-result" style="white-space: pre-wrap; word-break: break-all;"></pre>
				</div>
				<?php
				$settings = array(
					'endpoint' => rest_url( '/parse-this/1.0/parse/' ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'parsing'  => __( 'Parsing…', 'parse-this' ),
				);
				wp_enqueue_script( 'parse-this-debug', plugins_url( 'js/debug.js', __DIR__ ), array(), '2.0.0', true );
				wp_add_inline_script( 'parse-this-debug', 'var parseThisDebug = ' . wp_json_encode( $settings ) . ';', 'before' );
	}


	/**
	 * Registers the parse-this/1.0/parse route.
	 *
	 * Requires the edit_posts capability (see permission_check()).
	 *
	 * @since 1.0.0
	 */
	public static function register_routes() {
		$cls = get_called_class();
		register_rest_route(
			'parse-this/1.0',
			'/parse',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $cls, 'read' ),
					'args'                => array(
						'url'             => array(
							'required'          => true,
							'validate_callback' => array( $cls, 'is_valid_url' ),
							'sanitize_callback' => 'esc_url_raw',
						),
						'return'          => array(
							'description' => __( 'Whether to return a single item or a feed.', 'parse-this' ),
							'type'        => 'string',
							'enum'        => array( 'single', 'feed' ),
						),
						'mf2'             => array(
							'description' => __( 'Return mf2 instead of jf2.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'discovery'       => array(
							'description' => __( 'List the URL\'s feeds instead of parsing it.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'references'      => array(
							'description' => __( 'Move referenced items into refs.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'location'        => array(
							'description' => __( 'Collapse location properties into one location.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'follow'          => array(
							'description' => __( 'Fetch the author\'s page when the author is only a URL.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'require_content' => array(
							'description' => __( 'Whether a result needs full content before fallbacks stop. Defaults to true for feeds and false otherwise.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'always_arrays'   => array(
							'description' => __( 'Always return the array properties as arrays, as Microsub does.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'debug'           => array(
							'description' => __( 'Include the raw data each source was read from.', 'parse-this' ),
							'type'        => 'boolean',
						),
						'nocache'         => array(
							'description' => __( 'Fetch the URL again instead of using a cached result.', 'parse-this' ),
							'type'        => 'boolean',
						),
					),
					'permission_callback' => array( $cls, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may use the parse route.
	 *
	 * Parsing makes the server fetch arbitrary URLs, sometimes several per
	 * request, so it is limited to users who can write posts.
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if the current user has the required capability.
	 */
	public static function permission_check() {
		return current_user_can( self::required_capability() );
	}

	/**
	 * Returns the capability required for the parse route and the debug page.
	 *
	 * @since 2.0.0
	 *
	 * @return string Capability name.
	 */
	public static function required_capability() {
		/**
		 * Filters the capability required to use the parse-this/1.0/parse route
		 * and the Tools > Parse This page.
		 *
		 * @since 2.0.0
		 *
		 * @param string $capability Capability name. Default 'edit_posts'.
		 */
		return apply_filters( 'parse_this_rest_capability', 'edit_posts' );
	}

	/**
	 * Handles a parse request.
	 *
	 * Accepted parameters: url (required); mf2, to return mf2 instead of jf2;
	 * discovery, to list the URL's feeds instead of parsing it; and return,
	 * references, location, follow, require_content, always_arrays and debug,
	 * which are passed to Parser::parse(); and nocache, to fetch the URL again
	 * rather than use a cached result.
	 *
	 * Results are cached for 15 minutes (see the parse_this_cache_lifetime
	 * filter), except with debug.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Parsed jf2 (or mf2), discovery results, or WP_Error if
	 *                        the URL cannot be fetched.
	 */
	public static function read( $request ) {
		$url       = $request->get_param( 'url' );
		$mf2       = $request->get_param( 'mf2' );
		$return    = $request->get_param( 'return' );
		$refs      = $request->get_param( 'references' );
		$discovery = $request->get_param( 'discovery' );
		$location  = $request->get_param( 'location' );
		$follow    = $request->get_param( 'follow' );

		$args = array(
			'return'     => $return,
			'follow'     => $follow,
			'references' => $refs,
			'location'   => $location,
		);
		// Only pass require_content when given, so the automatic default applies otherwise.
		$require_content = $request->get_param( 'require_content' );
		if ( null !== $require_content ) {
			$args['require_content'] = rest_sanitize_boolean( $require_content );
		}
		$args['always_arrays'] = rest_sanitize_boolean( $request->get_param( 'always_arrays' ) );
		$args['debug']         = rest_sanitize_boolean( $request->get_param( 'debug' ) );

		/**
		 * Filters how long parse results are cached, in seconds.
		 *
		 * Repeated requests for the same URL and arguments within this time are
		 * answered from a transient instead of fetching the URL again. Return 0
		 * to turn caching off. Requests with debug or nocache are never served
		 * from the cache.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $lifetime Lifetime in seconds. Default 15 minutes.
		 * @param string $url      URL being parsed.
		 */
		$lifetime = (int) apply_filters( 'parse_this_cache_lifetime', 15 * MINUTE_IN_SECONDS, $url );
		$key      = 'pt_parse_' . md5( wp_json_encode( array( $url, (bool) $mf2, (bool) $discovery, $args ) ) );
		$use      = $lifetime > 0 && ! $args['debug'];
		if ( $use && ! rest_sanitize_boolean( $request->get_param( 'nocache' ) ) ) {
			$cached = get_transient( $key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		if ( $discovery ) {
			$parse  = new Discovery();
			$result = $parse->fetch( $url );
		} else {
			$parse = new Parser( $url );
			$r     = $parse->fetch();
			if ( is_wp_error( $r ) ) {
				return $r;
			}
			$parse->parse( $args );
			$result = $mf2 ? $parse->get( 'mf2' ) : $parse->get();
		}

		if ( $use && ! is_wp_error( $result ) ) {
			set_transient( $key, $result, $lifetime );
		}
		return $result;
	}

	/**
	 * Validates the url parameter.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $url     URL to validate.
	 * @param WP_REST_Request|null $request Optional. Request object. Unused.
	 * @param string|null          $key     Optional. Parameter name. Unused.
	 * @return string|false The URL if valid, false otherwise.
	 */
	public static function is_valid_url( $url, $request = null, $key = null ) {
		return wp_http_validate_url( $url );
	}


	/**
	 * Adds a scheme to a URL that lacks one.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url    URL.
	 * @param string $scheme Optional. Scheme to prepend. Default 'http://'.
	 * @return string The URL with a scheme.
	 */
	public static function addscheme( $url, $scheme = 'http://' ) {
		return wp_parse_url( $url, PHP_URL_SCHEME ) === null ? $scheme . $url : $url;
	}
}
