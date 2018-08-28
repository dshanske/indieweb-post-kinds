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
		register_rest_route(
			'link-preview/1.0', '/parse', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( 'Link_Preview', 'read' ),
					'args'                => array(
						'kindurl' => array(
							'required'          => true,
							'validate_callback' => array( 'Link_Preview', 'is_valid_url' ),
							'sanitize_callback' => 'esc_url_raw',
						),
						'kind'    => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'permission_callback' => function () {
						return current_user_can( 'publish_posts' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( 'Link_Preview', 'create' ),
					'args'                => array(
						'kindurl' => array(
							'validate_callback' => array( 'Link_Preview', 'is_valid_url' ),
							'sanitize_callback' => 'esc_url_raw',
						),
						'kind'    => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_key',
						),
						'content' => array(
							'sanitize_callback' => 'wp_kses_post',
						),
						'status'  => array(
							'sanitize_callback' => 'sanitize_key',
						),
					),
					'permission_callback' => function() {
						return current_user_can( 'publish_posts' );
					},
				),
			)
		);
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

	/**
	 * Parses marked up HTML.
	 *
	 * @param string $content HTML marked up content.
	 */
	private static function mergeparse( $content, $url, $kind ) {
		if ( empty( $content ) || empty( $url ) ) {
			return array();
		}
		$parsethis = new Parse_This();
		$parsethis->set_source( $content, $url );
		$metadata = $parsethis->meta_to_microformats();
		$mf2data  = Parse_MF2::mf2parse( $content, $url );
		$data     = array_merge( $metadata, $mf2data );
		$data     = array_filter( $data );

		if ( ! isset( $data['summary'] ) && isset( $data['content'] ) ) {
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
		// Attempt to set a featured image
		if ( ! isset( $data['featured'] ) ) {
			if ( isset( $data['photo'] ) && is_array( $data['photo'] ) && 1 === count( $data['photo'] ) ) {
				$data['featured'] = $data['photo'];
				unset( $data['photo'] );
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
	public static function read( $request ) {
		// We don't need to specifically check the nonce like with admin-ajax. It is handled by the API.
		$params = $request->get_params();
		$kind   = ifset( $params['kind'] );
		if ( isset( $params['kindurl'] ) && ! empty( $params['kindurl'] ) ) {
			return self::parse( $params['kindurl'], $kind );
		}
		return new WP_Error(
			'invalid_url', __( 'Missing or Invalid URL', 'indieweb-post-kinds' ), array(
				'status' => 400,
			)
		);
	}

	// Create Post
	public static function create( $request ) {
		$params = $request->get_params();
		if ( ! isset( $params['kind'] ) ) {
			return new WP_Error(
				'incomplete', __( 'Missing Kind', 'indieweb-post-kinds' ), array(
					'status' => 400,
				)
			);
		}
		if ( ! isset( $params['kindurl'] ) && ! isset( $params['content'] ) ) {
			return new WP_Error(
				'incomplete', __( 'Missing Content or KindURL', 'indieweb-post-kinds' ), array(
					'status' => 400,
				)
			);
		}
		if ( ! isset( $params['status'] ) ) {
			$params['status'] = 'publish';
		}
		$postarr = array(
			'post_status' => $params['status'],
			'post_author' => get_current_user_id(),
		);
		if ( isset( $params['kindurl'] ) ) {
			$parse                 = self::simple_parse( $params['kindurl'] );
			$postarr['post_title'] = $parse['name'];
			$property              = Kind_Taxonomy::get_kind_info( $params['kind'], 'property' );
			if ( ! $property ) {
				return;
			}
			$postarr['meta_input'] = array(
				'mf2_' . $property => $parse,
			);
		}
		if ( isset( $params['content'] ) ) {
			$postarr['post_content'] = $params['content'];
		}
		$ret = wp_insert_post( $postarr, true );
		if ( is_wp_error( $ret ) ) {
			return $ret;
		}
		set_post_kind( $ret, $params['kind'] );
		return $ret;
	}

	public static function addscheme( $url, $scheme = 'http://' ) {
		return wp_parse_url( $url, PHP_URL_SCHEME ) === null ? $scheme . $url : $url;
	}


	public static function parse( $url, $kind = null ) {
		if ( ! self::is_valid_url( $url ) ) {
			return new WP_Error(
				'invalid_url', __( 'Missing or Invalid URL', 'indieweb-post-kinds' ), array(
					'status' => 400,
				)
			);
		}
		$content = Parse_Mf2::fetch( $url );
		if ( is_wp_error( $content ) ) {
			if ( 'content-type' === $content->get_error_code() ) {
				return self::media( $url );
			} else {
				return $content;
			}
		}
		return self::mergeparse( $content, $url, $kind );
	}

	public static function media( $url ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		if ( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return new WP_Error(
				'not_local_media', __( 'At this time you can only use a local media file when entering the URL of a media file', 'indieweb-post-kinds' ), array( 'status' => 400 )
			);
		}
		$id   = attachment_url_to_postid( $url );
		$meta = wp_get_attachment_metadata( $id );
		if ( ! $meta ) {
			$meta = wp_generate_attachment_metadata( $id, get_attached_file( $id ) );
			wp_update_attachment_metadata( $id, $meta );
		}
		$return                = array();
		$return['raw']         = $meta;
		$return['publication'] = ifset( $meta['album'] );
		$return['author']      = ifset( $meta['artist'] );
		$return['title']       = ifset( $meta['title'] );
		return array_filter( $return );
	}

	public static function simple_parse( $url, $kind = null ) {
		$parse = self::parse( $url, $kind );
		if ( is_wp_error( $parse ) ) {
			return $parse;
		}
		$unset = array( 'raw', 'content', 'unfiltered' );
		foreach ( $unset as $u ) {
			unset( $parse[ $u ] );
		}
		return $parse;

	}
}

