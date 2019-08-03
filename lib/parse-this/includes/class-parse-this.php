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
		if ( 'mf2' === $key ) {
			return jf2_to_mf2( $this->jf2 );
		}
		if ( ! in_array( $key, get_object_vars( $this ), true ) ) {
			$key = 'jf2';
		}
		return $this->$key;
	}


	public static function clean_content( $content ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}
		$allowed = array(
			'a'          => array(
				'href' => array(),
				'name' => array(),
			),
			'abbr'       => array(),
			'b'          => array(),
			'br'         => array(),
			'code'       => array(),
			'del'        => array(),
			'em'         => array(),
			'i'          => array(),
			'q'          => array(),
			'strike'     => array(),
			'strong'     => array(),
			'time'       => array(),
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
				'src'   => array(),
				'alt'   => array(),
				'title' => array(),
			),
		);
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
			$this->url    = $url;
			$this->domain = wp_parse_url( $url, PHP_URL_HOST );
		}
		if ( $jf2 ) {
			$this->jf2 = $source_content;
		} elseif ( is_string( $this->content ) ) {
			$this->doc = pt_load_domdocument( $this->content );
		}
	}

	/* Reproduced version of fetch_feed from core which calls bundled SimplePie instead of older version
	*/
	public static function fetch_feed( $url ) {
		if ( ! class_exists( 'SimplePie', false ) ) {
			// Try to use bundled SimplePie if not WordPress older SimplePie
			$file = plugin_dir_path( __DIR__ ) . 'lib/simplepie/autoloader.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			} else {
				require_once ABSPATH . WPINC . '/class-simplepie.php';
			}
		}
		require_once ABSPATH . WPINC . '/class-wp-feed-cache.php';
		require_once ABSPATH . WPINC . '/class-wp-feed-cache-transient.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
		require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
		$feed = new SimplePie();

		$feed->set_sanitize_class( 'WP_SimplePie_Sanitize_KSES' );

		// We must manually overwrite $feed->sanitize because SimplePie's
		// constructor sets it before we have a chance to set the sanitization class
		$feed->sanitize = new WP_SimplePie_Sanitize_KSES();

		$feed->set_cache_class( 'WP_Feed_Cache' );
		$feed->set_file_class( 'WP_SimplePie_File' );
		$feed->enable_cache( false );
		$feed->strip_htmltags( false );
		$feed->set_feed_url( $url );

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
			'application/xml',
			'text/xml',
			'application/jf2+json',
			'application/jf2feed+json',
			'application/rss+xml',
			'application/atom+xml',
		);
		return in_array( $content_type, $types, true );
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
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
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
		$user_agent = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36 Parse This/WP';
		$args       = array(
			'timeout'             => 15,
			'limit_response_size' => 1048576,
			'redirection'         => 5,
			// Use an explicit user-agent for Parse This
		);

		$response      = wp_safe_remote_get( $url, $args );
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
		if ( ( in_array( $content_type, array( 'application/rss+xml', 'application/atom+xml', 'text/xml', 'application/xml', 'text/xml' ), true ) ) ) {
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
		if ( 'application/json' === $content_type ) {
			$content = json_decode( $content, true );
			if ( $content && isset( $content['version'] ) && 'https://jsonfeed.org/version/1' === $content['version'] ) {
				$content = Parse_This_JSONFeed::to_jf2( $content, $url );
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
			'html'       => true, // If mf2 parsing does not work look for html parsing
			'references' => true, // Store nested citations as references per the JF2 spec
		);
		$args     = wp_parse_args( $args, $defaults );
		// If not an option then revert to single
		if ( ! in_array( $args['return'], array( 'single', 'feed' ), true ) ) {
			$args['return'] = 'single';
		}
		if ( $this->content instanceof WP_Post ) {
			$this->jf2 = self::wp_post( $this->content );
			return;
		} elseif ( $this->content instanceof SimplePie ) {
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
		// Ensure not already preparsed
		if ( empty( $this->jf2 ) ) {
			$this->jf2 = Parse_This_MF2::parse( $content, $this->url, $args );
		}
		if ( ! isset( $this->jf2['url'] ) ) {
			$this->jf2['url'] = $this->url;
		}
		// If the HTML argument is not true return at this point
		if ( ! $args['html'] ) {
			return;
		}
		// If No MF2
		if ( empty( $this->jf2 ) ) {
			$args['alternate'] = true;
			$this->jf2         = Parse_This_HTML::parse( $content, $this->url, $args );
			return;
		}
		// If the parsed jf2 is missing any sort of content then try to find it in the HTML
		$more = array_intersect( array_keys( $this->jf2 ), array( 'summary', 'content', 'references' ) );
		if ( empty( $more ) && $this->doc instanceof DOMDocument ) {
			$this->jf2 = array_merge( $this->jf2, Parse_This_HTML::parse( $this->doc, $this->url ) );
		}
		if ( ! isset( $this->jf2['url'] ) ) {
			$this->jf2['url'] = $this->url;
		}

	}

	public static function wp_post( $post ) {
		$mf2 = new MF2_Post( $post );
		return mf2_to_jf2( $mf2->get() );
	}

}
