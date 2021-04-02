<?php

/**
 * Parse This class.
 * Originally Derived from the Press This Class with Enhancements.
 */
class Parse_This {
	private $url = '';
	private $doc;
	private $links = array();
	private $jf2   = array();

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
			$this->url = pt_secure_rewrite( $url );
		}
	}

	public function get( $key = 'jf2' ) {
		if ( 'mf2' === $key ) {
			return jf2_to_mf2( $this->jf2 );
		}
		if ( ! in_array( $key, get_object_vars( $this ), true ) ) {
			$key = 'jf2';
		}
		return $this->$key;
	}

	/*
	 Cleans HTML content.
	 *
	 * @param string $content HTML content to be cleaned.
	 * @param array $strip Any keys in this array will be removed from the allowed tags that are retained when cleaned.
	 *
	 * @return string Clean Content.
	 */
	public static function clean_content( $content, $strip = array() ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}
		// Decode escaped entities so that they can be stripped
		$content     = html_entity_decode( $content, ENT_COMPAT | ENT_HTML401, 'UTF-8' );
		$content     = preg_replace( '/<!--(.|\s)*?-->/', '', $content );
		$domdocument = pt_load_domdocument( $content );
		$scripts     = $domdocument->getElementsByTagName( 'script' );
		foreach ( $scripts as $item ) {
			$item->parentNode->removeChild( $item ); // phpcs:ignore
		}

		$content = $domdocument->saveHTML();

		$allowed = array(
			'a'          => array(
				'href' => array(),
				'name' => array(),
			),
			'abbr'       => array(),
			'b'          => array(),
			'br'         => array(),
			'code'       => array(),
			'ins'        => array(),
			'del'        => array(),
			'em'         => array(),
			'i'          => array(),
			'q'          => array(),
			'strike'     => array(),
			'strong'     => array(),
			'time'       => array(
				'datetime' => array(),
			),
			'blockquote' => array(),
			'pre'        => array(),
			'p'          => array(),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'ul'         => array(),
			'li'         => array(),
			'ol'         => array(),
			'span'       => array(),
			'img'        => array(
				'src'    => array(),
				'alt'    => array(),
				'title'  => array(),
				'width'  => array(),
				'height' => array(),
				'srcset' => array(),
			),
			'figure'     => array(),
			'figcaption' => array(),
			'picture'    => array(
				'srcset' => array(),
				'type'   => array(),
			),
			'video'      => array(
				'poster' => array(),
				'src'    => array(),
			),
			'audio'      => array(
				'duration' => array(),
				'src'      => array(),
			),
			'track'      => array(
				'label'   => array(),
				'src'     => array(),
				'srclang' => array(),
				'kind'    => array(),
			),
			'source'     => array(
				'src'    => array(),
				'srcset' => array(),
				'type'   => array(),

			),
			'hr'         => array(),
		);
		if ( ! empty( $strip ) ) {
			$allowed = array_diff_key( $allowed, $strip );
		}
		return trim( wp_kses( $content, $allowed ) );
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
			$this->url    = pt_secure_rewrite( $url );
			$this->domain = wp_parse_url( $url, PHP_URL_HOST );
		}
		if ( $jf2 ) {
			$this->jf2 = $source_content;
		} elseif ( is_string( $this->content ) ) {
			$this->doc = pt_load_domdocument( $this->content );
		}
	}

	/*
	 Reproduced version of fetch_feed from core which calls bundled SimplePie instead of older version
	*/
	public static function fetch_feed( $url ) {
		$url = pt_secure_rewrite( $url );
		if ( ! class_exists( 'SimplePie', false ) ) {
			require_once ABSPATH . WPINC . '/class-simplepie.php';
		}
		require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
		require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
		$feed = new SimplePie();

		$feed->set_cache_class( 'WP_Feed_Cache' );
		$feed->set_file_class( 'WP_SimplePie_File' );
		$feed->enable_cache( false );
		$feed->set_feed_url( $url );
		$feed->strip_htmltags( false );
		/**
		 * Fires just before processing the SimplePie feed object.
		 *
		 * @since 3.0.0
		 *
		 * @param object $feed SimplePie feed object (passed by reference).
		 * @param mixed  $url  URL of feed to retrieve. If an array of URLs, the feeds are merged.
		 */
		do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );
		$feed->init();
		$feed->set_output_encoding( get_option( 'blog_charset' ) );

		if ( $feed->error() ) {
			return new WP_Error( 'simplepie-error', $feed->error() );
		}

		return $feed;
	}

	/**
	 * Returns a list of supported content types
	 *
	 * @param string $content_type
	 * @return boolean if supported
	 */
	public function supported_content( $content_type ) {
		$types = array(
			'application/mf2+json',
			'text/html',
			'application/json',
			'application/feed+json',
			'application/xml',
			'text/xml',
			'application/jf2+json',
			'application/jf2feed+json',
			'application/rss+xml',
			'application/atom+xml',
		);
		return in_array( $content_type, $types, true );
	}

	public static function redirect( $url, $allowlist = true ) {
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'indieweb-post-kinds' ) );
		}
		$url        = pt_secure_rewrite( $url );
		$domain     = wp_parse_url( $url, PHP_URL_HOST );
		$shorteners = array( 'fb.me', 't.co', 'youtu.be', 'ow.ly', 'bit.ly', 'tinyurl.com' );
		if ( ! $allowlist && ! in_array( $domain, $shorteners, true ) ) {
			return false;
		}
		$user_agent    = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36 Parse This/WP';
		$args          = array(
			'timeout'             => 15,
			'limit_response_size' => 1048576,
			'redirection'         => 0,
		);
		$response      = wp_safe_remote_get( $url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$redirect      = wp_remote_retrieve_header( $response, 'location' );
		if ( ! $redirect ) {
			return false;
		}
		return ( normalize_url( $redirect ) !== normalize_url( $url ) ) ? $redirect : false;
	}

	/**
	 * Downloads the source's via server - side call for the given URL .
	 *
	 * @param string $url URL to scan .
	 * @return WP_Error | boolean WP_Error if invalid and true if successful
	 */
	public function fetch( $url = null ) {
		if ( ! $url ) {
			$url = $this->url;
		}
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'indieweb-post-kinds' ) );
		}
		$user_agent = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36 Parse This/WP';
		$args       = array(
			'timeout'             => 15,
			'limit_response_size' => 1048576,
			'redirection'         => 5,
		// Use an explicit user-agent for Parse This
		);

		$response = wp_safe_remote_get( $url, $args );

		$raw = wp_remote_retrieve_header( $response, 'link' );
		if ( is_array( $raw ) && 1 <= count( $raw ) ) {
			foreach ( $raw as $link ) {
				$pieces              = explode( '; ', $link );
				$uri                 = trim( array_shift( $pieces ), '<>' );
				$this->links[ $uri ] = array();
				foreach ( $pieces as $p ) {
					$elements                            = explode( '=', $p );
					$this->links[ $uri ][ $elements[0] ] = trim( $elements[1], '"' );
				}
			}
			ksort( $this->links );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$content_type  = wp_remote_retrieve_header( $response, 'content-type' );
		if ( in_array( $response_code, array( 403, 415 ), true ) ) {
			$args['user-agent'] = $user_agent;
			$response           = wp_safe_remote_get( $url, $args );
			$response_code      = wp_remote_retrieve_response_code( $response );
			if ( in_array( $response_code, array( 403, 415 ), true ) ) {
				return new WP_Error( 'source_error', 'Unable to Retrieve' );
			}
		}
		if ( is_array( $content_type ) ) {
			$content_type = array_pop( $content_type );
		}
						// Strip any character set off the content type
						$ct = explode( ';', $content_type );
		if ( is_array( $ct ) ) {
			$content_type = array_shift( $ct );
		}
						$content_type = trim( $content_type );
						// List of content types we know how to handle
		if ( ! self::supported_content( $content_type ) ) {
			return new WP_Error( 'content-type', 'Content Type is Not Supported', array( 'content-type' => $content_type ) );
		}

						$content = wp_remote_retrieve_body( $response );
						// This is an RSS or Atom Feed URL and if it is not we do not know how to deal with XML anyway
		if ( class_exists( 'Parse_This_RSS' ) && ( in_array( $content_type, array( 'application/rss+xml', 'application/atom+xml', 'text/xml', 'application/xml', 'text/xml' ), true ) ) ) {
			// Get a SimplePie feed object from the specified feed source.
			$content = self::fetch_feed( $url );
			if ( is_wp_error( $content ) ) {
				return false;
			}

			$this->set( $content, $url, true );
			return true;
		}

		if ( in_array( $content_type, array( 'application/mf2+json', 'application/jf2+json', 'application/jf2feed+json' ), true ) ) {
			$content = json_decode( $content, true );
			return true;
		}
		if ( in_array( $content_type, array( 'application/feed+json', 'application/json' ), true ) ) {
			$content = json_decode( $content, true );
			if ( class_exists( 'Parse_This_JSONFeed' ) && $content && isset( $content['version'] ) && false !== strpos( $content['version'], 'https://jsonfeed.org/version/' ) ) {
				$content = Parse_This_JSONFeed::to_jf2( $content, $url );
				$this->set( $content, $url, true );
			}
			// This header indicates we are probing the WordPress REST API
			if ( wp_remote_retrieve_header( $response, 'x-wp-total' ) ) {
				$content = Parse_This_RESTAPI::to_jf2( $content, $url );
				$this->set( $content, $url, true );
			}
			// We do not yet know how to cope with this
			return true;
		}
						$this->set( $content, $url, ( 'application/jf2+json' === $content_type ) );
						return true;
	}

	public function parse( $args = array() ) {
		$defaults = array(
			'alternate'  => false, // check for rel-alternate jf2 or mf2 feed
			'return'     => 'single', // Options are single, feed or TBC mention
			'follow'     => false, // If set to true h-card and author properties with external urls will be retrieved parsed and merged into the return
			'limit'      => 150, // Limit the number of children returned.
			'jsonld'     => true,  // Try JSON-LD parsing
			'html'       => true, // If mf2 parsing does not work look for html parsing which includes OGP, meta tags, and title tags
			'references' => true, // Store nested citations as references per the JF2 spec
			'location' => false, // Collapse location parameters in jf2. Specifically, location will be a string and latitude, longitude, and altitude will be set as h-entry properties.
		);
		$args     = wp_parse_args( $args, $defaults );
		// If not an option then revert to single
		if ( ! in_array( $args['return'], array( 'single', 'feed' ), true ) ) {
			$args['return'] = 'single';
		}
		if ( class_exists( 'Parse_This_RSS' ) && $this->content instanceof SimplePie ) {
			$this->jf2 = Parse_This_RSS::parse( $this->content, $this->url );
			return;
		} elseif ( $this->doc instanceof DOMDocument ) {
			$content = $this->doc;
		} else {
			$content = $this->content;
		}
		if ( ! $content ) {
			return new WP_Error( 'Missing Content' );
		}

		if ( ! is_array( $this->jf2 ) ) {
			$this->jf2 = array(
				'raw' => $this->jf2,
				'url' => $this->url,
			);
			return;
		}
		// Ensure not already preparsed
		if ( empty( $this->jf2 ) ) {
			$this->jf2 = Parse_This_MF2::parse( $content, $this->url, $args );
		}
		if ( ! isset( $this->jf2['url'] ) ) {
			$this->jf2['url'] = $this->url;
		}
		// If No MF2 or if the parsed jf2 is missing any sort of content then try to find it in the HTML
		if ( isset( $this->jf2['type'] ) && 'card' === $this->jf2['type'] ) {
			$more = array_intersect( array_keys( $this->jf2 ), array( 'name', 'url', 'photo' ) );
		} else {
			$more = array_intersect( array_keys( $this->jf2 ), array( 'summary', 'content', 'refs', 'items' ) );
		}
		if ( empty( $more ) ) {
			$alt = null;

			if ( $args['jsonld'] ) {
				$alt = Parse_This_JSONLD::parse( $this->doc, $this->url, $args );
			}

			if ( empty( $alt ) ) {
				$empty = true;
			} elseif ( is_countable( $alt ) && 1 === count( $alt ) && array_key_exists( '_jsonld', $alt ) ) {
				$empty = true;
			} else {
					$empty = false;
			}
			if ( $empty && $args['html'] ) {
				$args['alternate'] = true;
				if ( in_array( wp_parse_url( $this->url, PHP_URL_HOST ), array( 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be' ), true ) ) {
					$alt = Parse_This_YouTube::parse( $this->content, $this->url, $args );
				} elseif ( in_array( wp_parse_url( $this->url, PHP_URL_HOST ), array( 'www.instagram.com', 'instagram.com' ), true ) ) {
					$alt = Parse_This_Instagram::parse( $this->doc, $this->url, $args );
				} elseif ( in_array( wp_parse_url( $this->url, PHP_URL_HOST ), array( 'twitter.com', 'mobile.twitter.com' ), true ) ) {
					$alt = Parse_This_Twitter::parse( $this->url, $args );
				}
				if ( ! $alt ) {
					$alt = Parse_This_HTML::parse( $content, $this->url, $args );
				}
			}
				$this->jf2 = array_merge( $this->jf2, $alt );
		}
		if ( ! isset( $this->jf2['url'] ) ) {
			$this->jf2['url'] = $this->url;
		}
			// Expand Short URLs in summary
		if ( isset( $this->jf2['summary'] ) ) {
			$urls = wp_extract_urls( $this->jf2['summary'] );
			foreach ( $urls as $url ) {
				$redirect = self::redirect( $url );
				if ( $redirect && ! is_wp_error( $redirect ) ) {
					$this->jf2['_urls'][] = $redirect;
					$this->jf2['summary'] = str_replace( $url, $redirect, $this->jf2['summary'] );
				}
			}
		}
		if ( isset( $this->jf2['location'] ) && $args['location'] ) {
			$this->jf2 = jf2_location( $this->jf2 );
		}
	}
}
