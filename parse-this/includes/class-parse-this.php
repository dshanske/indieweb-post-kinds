<?php
/**
 * Parse This class.
 * Originally Derived from the Press This Class with Enhancements.
 *
 */
class Parse_This {
	private $url = '';
	private $doc;
	private $jf2 = array();

	private $domain = '';

	private $content = '';

	/**
	 * Constructor.
	 *
	 * @since x.x.x
	 * @access public
	 */
	public function __construct( $url = null ) {
		if ( wp_http_validate_url( $url ) ) {
			$this->url = $url;
		}
	}

	public function get( $key = 'jf2' ) {
		if ( ! in_array( $key, get_object_vars( $this ), true ) ) {
			$key = 'jf2';
		}
		return $this->$key;
	}

	/**
	 * Sets the source.
	 *
	 * @since x.x.x
	 * @access public
	 *
	 * @param string $source_content source content.
	 * @param string $url Source URL
	 * @param string $jf2 If set it passes the content directly as preparsed
	 */
	public function set( $source_content, $url, $jf2 = false ) {
		$this->content = $source_content;
		if ( wp_http_validate_url( $url ) ) {
			$this->url    = $url;
			$this->domain = wp_parse_url( $url, PHP_URL_HOST );
		}
		if ( $jf2 ) {
			$this->jf2 = $source_content;
		} elseif ( is_string( $this->content ) ) {
			if ( class_exists( 'Masterminds\\HTML5' ) ) {
				$this->doc = new \Masterminds\HTML5( array( 'disable_html_ns' => true ) );
				$this->doc = $this->doc->loadHTML( $this->content );
			} else {
				$this->doc = new DOMDocument();
				libxml_use_internal_errors( true );
				$this->doc->loadHTML( mb_convert_encoding( $this->content, 'HTML-ENTITIES', mb_detect_encoding( $this->content ) ) );
				libxml_use_internal_errors( false );
			}
		}
	}


	/**
	 * Downloads the source's via server-side call for the given URL.
	 *
	 * @param string $url URL to scan.
	 * @return WP_Error|boolean WP_Error if invalid and true if successful
	 */
	public function fetch( $url = null ) {
		if ( ! $url ) {
			$url = $this->url;
		}
		if ( empty( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'indieweb-post-kinds' ) );
		}
		if ( wp_parse_url( home_url(), PHP_URL_HOST ) === wp_parse_url( $url, PHP_URL_HOST ) ) {
			$post_id = url_to_postid( $url );
			if ( $post_id ) {
				$this->set( get_post( $post_id ), $url );
				return;
			}
			$post_id = attachment_url_to_postid( $url );
			if ( $post_id ) {
				$this->set( get_post( $post_id ), $url );
				return;
			}
		}

		$args = array(
			'headers'             => array(
				'Accept' => 'application/jf2+json, application/mf2+json, text/html', // Accept either mf2+json or html
			),
			'timeout'             => 10,
			'limit_response_size' => 1048576,
			'redirection'         => 1,
			// Use an explicit user-agent for Parse This
			'user-agent'          => 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36 Parse This/WP',
		);
		$response      = wp_safe_remote_head( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$content_type  = wp_remote_retrieve_header( $response, 'content-type' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		switch ( $response_code ) {
			case 200:
				break;
			default:
				return new WP_Error( 'source_error', wp_remote_retrieve_response_message( $response ), array( 'status' => $response_code ) );
		}

		if ( preg_match( '#(image|audio|video|model)/#is', $content_type ) ) {
			return new WP_Error( 'content-type', 'Content Type is Media' );
		}

		$response = wp_safe_remote_get( $url, $args );
		$content  = wp_remote_retrieve_body( $response );
		if ( in_array( $content_type, array( 'application/mf2+json', 'application/jf2+json' ), true ) ) {
			$content = json_decode( $content, true );
		}
		$this->set( $content, $url, ( 'application/jf2+json' === $content_type ) );
		return true;
	}

	public function parse() {
		if ( $this->content instanceof WP_Post ) {
			$this->jf2 = self::wp_post( $this->content );
			return;
		} elseif ( $this->doc instanceof DOMDocument ) {
			$content = $this->doc;
		} else {
			$content = $this->content;
		}
		// Ensure not already preparsed
		if ( empty( $this->jf2 ) ) {
			$this->jf2 = Parse_This_MF2::parse( $content, $this->url, false );
		}
		// If No MF2
		if ( empty( $this->jf2 ) ) {
			$this->jf2 = Parse_This_HTML::parse( $content, $this->url );
			return;
		}
		// If the parsed jf2 is missing any sort of content then try to find it in the HTML
		$more = array_intersect( array_keys( $this->jf2 ), array( 'name', 'summary', 'content' ) );
		if ( empty( $more ) && $this->doc instanceof DOMDocument ) {
			$this->jf2 = array_merge( $this->jf2, Parse_This_HTML::parse( $this->doc, $this->url ) );
		}

	}

	public static function wp_post( $post ) {
		$mf2 = new MF2_Post( $post );
		return $mf2->get( null, true );
	}

}
