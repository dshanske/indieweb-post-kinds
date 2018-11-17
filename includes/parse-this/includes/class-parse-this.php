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

	private function get_feed_type( $type ) {
		switch ( $type ) {
			case 'application/json':
				return 'jsonfeed';
			case 'application/rss+xml':
				return 'rss';
			case 'application/atom+xml':
				return 'atom';
			case 'application/jf2feed+json':
				return 'jf2feed';
			case 'application/stream+json':
				return 'activitystream';
			case 'application/mf2+json':
				return 'mf2-json';
			case 'application/jf2+json':
				return 'jf2-json';
			default:
				return 'feed';
		}
	}

	/* Reproduced version of fetch_feed from core which calls bundled SimplePie instead of older version
	*/
	public static function fetch_feed( $url ) {
		if ( ! class_exists( 'SimplePie', false ) ) {
			$path  = plugin_dir_path( __DIR__ ) . 'vendor/simplepie/simplepie/library/';
			$files = array(
				'SimplePie/Credit.php',
				'SimplePie/Restriction.php',
				'SimplePie/Enclosure.php',
				'SimplePie/Category.php',
				'SimplePie/Misc.php',
				'SimplePie/Cache.php',
				'SimplePie/File.php',
				'SimplePie/Sanitize.php',
				'SimplePie/Rating.php',
				'SimplePie/Registry.php',
				'SimplePie/IRI.php',
				'SimplePie/Locator.php',
				'SimplePie/Content/Type/Sniffer.php',
				'SimplePie/XML/Declaration/Parser.php',
				'SimplePie/Parser.php',
				'SimplePie/Item.php',
				'SimplePie/Parse/Date.php',
				'SimplePie/Author.php',
				'SimplePie.php',
			);
			foreach ( $files as $file ) {
				//	if ( file_exists( $path . $file ) ) {
					require_once $path . $file;
				//	}
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
	 * Fetches a list of feeds
	 *
	 * @param string $url URL to scan
	 */
	public function fetch_feeds( $url = null ) {
		if ( ! $url ) {
			$url = $this->url;
		}
		if ( empty( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'indieweb-post-kinds' ) );
		}
		$this->fetch( $url );
		// A feed was given
		if ( $this->content instanceof SimplePie ) {
			return array(
				'results' => array( Parse_This_RSS::parse( $this->content, $url ) ),
			);
		}
		if ( $this->doc instanceof DOMDocument ) {
			$xpath = new DOMXPath( $this->doc );
			// Fetch and gather <link> data.
			$links = array();
			foreach ( $xpath->query( '(//link|//a)[@rel and @href]' ) as $link ) {
				$rel   = $link->getAttribute( 'rel' );
				$href  = $link->getAttribute( 'href' );
				$title = $link->getAttribute( 'title' );
				$type  = self::get_feed_type( $link->getAttribute( 'type' ) );
				if ( in_array( $rel, array( 'alternate', 'feed' ), true ) ) {
					$links[] = array(
						'url'  => WP_Http::make_absolute_url( $href, $url ),
						'type' => $type,
						'name' => $title,
					);
				}
			}
			// Check to see if the current page is an h-feed
			$this->parse();
			if ( 'feed' === $this->jf2['type'] ) {
				$links[] = array(
					'url'  => $url,
					'type' => 'microformats',
					'name' => $this->jf2['name'],
				);
			}
			// Sort feeds by priority
			$rank = array(
				'jf2feed'        => 0,
				'jf2-json'       => 1,
				'mf2-json'       => 2,
				'microformats'   => 3,
				'jsonfeed'       => 4,
				'atom'           => 5,
				'rss'            => 6,
				'activitystream' => 6,
			);
			usort(
				$links,
				function( $a, $b ) use ( $rank ) {
					return $rank[ $a['type'] ] > $rank[ $b['type'] ];
				}
			);

			return array( 'results' => $links );
		}
		return new WP_Error( 'unknown error', null, $this->content );
	}

	/**
	 * Downloads the source's via server-side call for the given URL.
	 *
	 * @param string $url URL to scan.
	 * @param boolean $is_feed Force it to think this is an RSS/Atom feed
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

		$args          = array(
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

		// Strip any character set off the content type
		$content_type = trim( array_shift( explode( ';', $content_type ) ) );
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

		$response = wp_safe_remote_get( $url, $args );
		$content  = wp_remote_retrieve_body( $response );
		if ( in_array( $content_type, array( 'application/mf2+json', 'application/jf2+json' ), true ) ) {
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
			'alternate' => false,
			'feed'      => false,
		);
		$args     = wp_parse_args( $args, $defaults );
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
		// If No MF2
		if ( empty( $this->jf2 ) ) {
			$args['alternate'] = true;
			$this->jf2         = Parse_This_HTML::parse( $content, $this->url, $args );
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
