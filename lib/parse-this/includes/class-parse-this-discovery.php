<?php

/**
 * Parse This Discovery class.
 *
 */
class Parse_This_Discovery {
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
	 * Downloads the $url and returns the feeds it finds
	 *
	 * @param string $url URL to scan.
	 * @return WP_Error|boolean WP_Error if invalid and true if successful
	 */
	public function fetch( $url ) {
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
		$links = array();

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

		$content = wp_remote_retrieve_body( $response );
		// This is an RSS or Atom Feed URL and if it is not we do not know how to deal with XML anyway
		if ( ( in_array( $content_type, array( 'application/rss+xml', 'application/atom+xml', 'text/xml', 'application/xml', 'text/xml' ), true ) ) ) {
			$content = Parse_This::fetch_feed( $url );
			if ( class_exists( 'Parse_This_RSS' ) ) {
				$links[] = array(
					'url'        => $url,
					'type'       => 'feed',
					'_feed_type' => Parse_This_RSS::get_type( $content ),
					'name'       => $content->get_title(),
				);
			}
			return array( 'results' => $links );
		}

		if ( in_array( $content_type, array( 'application/mf2+json', 'application/jf2+json', 'application/jf2feed+json' ), true ) ) {
			$content = json_decode( $content, true );
		}
		if ( 'application/json' === $content_type ) {
			$content = json_decode( $content, true );
			if ( $content && isset( $content['version'] ) && 'https://jsonfeed.org/version/1' === $content['version'] ) {
				$links[] = array(
					'url'        => $url,
					'type'       => 'feed',
					'_feed_type' => 'jsonfeed',
				);
			}
			return array( 'results' => $links );
		}
		if ( 'text/html' === $content_type ) {
			$doc = pt_load_domdocument( $content );
			if ( $doc instanceof DOMDocument ) {
				$xpath = new DOMXPath( $doc );
				// Fetch and gather <link> data.
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
				$feeds = Parse_This_MF2::find_hfeed( $doc, $url );
				foreach ( $feeds as $key => $feed ) {
					if ( ! Parse_This_MF2::is_microformat( $feed ) ) {
						continue;
					}
					if ( array_key_exists( 'children', $feed ) ) {
						unset( $feed['children'] );
					}
					$jf2 = mf2_to_jf2( $feed );
					if ( isset( $jf2['type'] ) && 'feed' === $jf2['type'] ) {
						$author = array();
						if ( array_key_exists( 'author', $jf2 ) ) {
							if ( is_array( $jf2['author'] ) ) {
								$author = $jf2['author'];
							} elseif ( is_string( $jf2['author'] ) ) {
								$author = array(
									'type' => 'card',
								);
								if ( wp_http_validate_url( $jf2['author'] ) ) {
									$author['url'] = $jf2['author'];
								} else {
									$author['name'] = $jf2['author'];
								}
							}
						}
						$links[] = array_filter(
							array(
								'url'        => $jf2['url'],
								'type'       => 'feed',
								'_feed_type' => 'microformats',
								'name'       => isset( $jf2['name'] ) ? $jf2['name'] : null,
								'author'     => $author,
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
	}

}
