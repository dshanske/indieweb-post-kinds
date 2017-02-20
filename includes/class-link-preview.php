<?php
/**
 * Gathers Data for Link Previews
 *
 * Parses Arbitrary URLs
 */

class Link_Preview {
	public static function init() {
		add_action( 'wp_ajax_kind_urlfetch', array( 'Link_Preview', 'urlfetch' ) );
		add_action( 'rest_api_init', array( 'Link_Preview', 'register_routes' ) );
	}

	/**
	 * Register the Route.
	 */
	public static function register_routes() {
		register_rest_route( 'link-preview/1.0', '/parse', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => array( 'Link_Preview', 'parse' ),
				'args'  => array(
					'kindurl'  => array( 
						'required' => true 
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				}
			),
		) );
	}

	/**
	 * Returns if valid URL
	 *
	 * @param string $url
	 *
	 * @return boolean
	 */
	public static function is_valid_url($url) {
		return filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Parses marked up HTML.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function mergeparse ($content, $url) {
		$parsethis = new Parse_This();
		$parsethis->set_source( $content, $url );
		$metadata = $parsethis->meta_to_microformats();
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

	public static function urlfetch() {
		global $wpdb;
		if ( empty( $_POST['kind_url'] ) ) {
				wp_send_json_error( new WP_Error( 'nourl', __( 'You must specify a URL' ) ) );
		}
		if ( filter_var( $_POST['kind_url'], FILTER_VALIDATE_URL ) === false ) {
				wp_send_json_error( new WP_Error( 'badurl', __( 'Input is not a valid URL' ) ) );
		}

		$content = Parse_Mf2::fetch( $_POST['kind_url'] );
		if ( is_wp_error( $content ) ) {
			wp_send_json_error( $response );
		}
		wp_send_json_success( self::mergeparse( $content, $_POST['kind_url'] ) );
	}

	// Callback Handler
	public static function parse( $request ) {
		// We don't need to specifically check the nonce like with admin-ajax. It is handled by the API.
		$params = $request->get_params();
		if ( isset( $params['kindurl'] ) ) {
			$content = Parse_Mf2::fetch( $params['kindurl'] );
			return self::mergeparse( $content, $params['kindurl'] );
		}
	}
		        


}
?>
