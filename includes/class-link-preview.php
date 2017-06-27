<?php
/**
 * Gathers Data for Link Previews
 *
 * Parses Arbitrary URLs
 */

class Link_Preview {
	public static function init() {
		add_action( 'rest_api_init', array( 'Link_Preview', 'register_routes' ) );
	}

	/**
	 * Register the Route.
	 */
	public static function register_routes() {
		register_rest_route( 'link-preview/1.0', '/parse', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( 'Link_Preview', 'callback' ),
				'args'  => array(
					'kindurl'  => array(
						'required' => true,
						'validate_callback' => array( 'Link_Preview', 'is_valid_url' ),
						'sanitize_callback' => 'esc_url_raw',
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			),
		) );
	}

	/**
	 * Returns if valid URL for REST validation
	 *
	 * @param string $url
	 *
	 * @return boolean
	 */
	public static function is_valid_url($url, $request = null, $key = null ) {
		if ( ! is_string( $url ) || empty( $url ) ) {
			return false;
		}
		return filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Parses marked up HTML.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function mergeparse ($content, $url) {
		if ( empty( $content ) || empty( $url ) ) {
			return array();
		}
		$parsethis = new Parse_This();
		$parsethis->set_source( $content, $url );
		$metadata = $parsethis->meta_to_microformats();
		return $metadata;
		if ( version_compare( PHP_VERSION, '5.3', '>' ) ) {
			$mf2data = Parse_MF2::mf2parse( $content, $url );
			$data = array_merge( $metadata, $mf2data );
			$data = array_filter( $data );
		} else {
			$data = $metadata;
		}

		if ( ! isset( $data['summary'] ) ) {
			$data['summary'] = substr( $data['content']['text'], 0, 300 );
			if ( 300 < strlen( $data['content']['text'] ) ) {
				$data['summary'] .= '...';
			}
		}
		if ( isset( $data['name'] ) ) {
			if ( isset( $data['summary'] ) ) {
				if ( false !== stripos( $data['summary'], $data['name'] ) ) {
					unset( $data['name'] );
				}
			}
		}

		/**
		 * Parse additionally by plugin.
		 *
		 * @param array $data An array of properties.
		 * @param string $content The content of the retrieved page.
		 * @param string $url Source URL
		 */
		return apply_filters( 'kind_parse_data', $data, $content, $url );
	}

	// Callback Handler
	public static function callback( $request ) {
		// We don't need to specifically check the nonce like with admin-ajax. It is handled by the API.
		$params = $request->get_params();
		if ( isset( $params['kindurl'] ) && ! empty( $params['kindurl' ] ) ) {
			return self::parse( $params['kindurl'] );
		}
		return new WP_Error( 'invalid_url' , __( 'Missing or Invalid URL' , 'indieweb-post-kinds' ), array( 'status' => 400 ) );
	}

	public static function parse( $url ) {
		if ( ! self::is_valid_url( $url ) ) {
			return new WP_Error( 'invalid_url' , __( 'Missing or Invalid URL' , 'indieweb-post-kinds' ), array( 'status' => 400 ) );
		}
		$content = Parse_Mf2::fetch( $url );
		return self::mergeparse( $content, $url );
	}
}
?>
