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
			case 'application/json+oembed':
			case 'text/xml+oembed':
				return '';
			case 'text/html':
				return 'microformats';
			default:
				return 'microformats';
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
		$fetch = $this->fetch( $url );
		if ( is_wp_error( $fetch ) ) {
			return $fetch;
		}
		// A feed was given
		if ( $this->content instanceof SimplePie ) {
			if ( ! class_exists( 'Parse_This_RSS', false ) ) {
				require_once plugin_dir_path( __FILE__ ) . '/class-parse-this-rss.php';
			}
			return array(
				'results' => array(
					array(
						'url'        => $url,
						'type'       => 'feed',
						'_feed_type' => Parse_This_RSS::get_type( $this->content ),
						'name'       => $this->content->get_title(),
					),
				),
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
				if ( in_array( $rel, array( 'alternate', 'feed' ), true ) && ! empty( $type ) ) {
					$links[] = array_filter(
						array(
							'url'        => WP_Http::make_absolute_url( $href, $url ),
							'type'       => 'feed',
							'_feed_type' => $type,
							'name'       => $title,
						)
					);
				}
			}
			// Check to see if the current page is an h-feed
			$this->parse( array( 'return' => 'feed' ) );

			if ( isset( $this->jf2['type'] ) && 'feed' === $this->jf2['type'] ) {
				$links[] = array_filter(
					array(
						'url'        => $url,
						'type'       => 'feed',
						'_feed_type' => 'microformats',
						'name'       => $this->jf2['name'],
					)
				);
			} elseif ( isset( $this->jf2['items'] ) ) {
				foreach ( $this->jf2['items'] as $item ) {
					if ( 'feed' === $item['type'] && isset( $item['uid'] ) ) {
						$links[] = array_filter(
							array(
								'url'        => $item['uid'],
								'type'       => 'feed',
								'_feed_type' => 'microformats',
								'name'       => ifset( $item['name'] ),
							)
						);
					}
				}
			}
			// Sort feeds by priority
			$rank = array(
				'jf2feed'      => 0,
				'microformats' => 1,
				'jsonfeed'     => 2,
				'atom'         => 3,
				'rss'          => 4,
			);
			usort(
				$links,
				function( $a, $b ) use ( $rank ) {
					return $rank[ $a['_feed_type'] ] > $rank[ $b['_feed_type'] ];
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
			if ( ! class_exists( 'Parse_This_JSONFeed', false ) ) {
				require_once plugin_dir_path( __FILE__ ) . '/class-parse-this-jsonfeed.php';
			}
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
			if ( ! class_exists( 'Parse_This_RSS', false ) ) {
				require_once plugin_dir_path( __FILE__ ) . '/class-parse-this-rss.php';
			}
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
			if ( ! class_exists( 'Parse_This_MF2', false ) ) {
				require_once plugin_dir_path( __FILE__ ) . '/class-parse-this-mf2.php';
			}
			$this->jf2 = Parse_This_MF2::parse( $content, $this->url, $args );
		}
		if ( ! isset( $this->jf2['url'] ) ) {
			$this->jf2['url'] = $this->url;
		}
		// If the HTML argument is not true return at this point
		if ( ! $args['html'] ) {
			return;
		}
		if ( ! class_exists( 'Parse_This_HTML', false ) ) {
			require_once plugin_dir_path( __FILE__ ) . '/class-parse-this-html.php';
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
		if ( ! class_exists( 'MF2_Post', false ) ) {
			require_once plugin_dir_path( __FILE__ ) . '/class-mf2-post.php';
		}
		$mf2 = new MF2_Post( $post );
		return $mf2->get( null, true );
	}

}
