<?php
/**
 * Discovery class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Discovers the feeds a URL offers.
 *
 * Finds rel=alternate and rel=feed links, the WordPress REST API root, the
 * page itself as an h-feed, YouTube channel feeds, and direct RSS, Atom and
 * JSON Feed URLs. Used by the REST endpoint's discovery option.
 *
 * @since 1.0.0
 */
class Discovery {
	/**
	 * Maps a feed MIME type to the plugin's feed type name.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Recognizes application/mf2+json and application/rdf+xml.
	 *
	 * @param string $type MIME type from a link's type attribute.
	 * @return string One of 'jsonfeed', 'json', 'rss', 'atom', 'jf2feed',
	 *                'microformats', or an empty string if the type is not a feed.
	 */
	private function get_feed_type( $type ) {
		switch ( $type ) {
			case 'application/feed+json':
				return 'jsonfeed';
			case 'application/json':
				return 'json';
			case 'text/xml':
			case 'application/rss+xml':
			case 'application/rdf+xml':
				return 'rss';
			case 'application/atom+xml':
				return 'atom';
			case 'application/jf2feed+json':
				return 'jf2feed';
			case 'text/mf2+html':
			case 'application/mf2+json':
				return 'microformats';
			default:
				return '';
		}
	}

	/**
	 * Returns the URL to list for a feed that was fetched directly.
	 *
	 * After a permanent redirect (301 or 308) the feed has moved, so its new
	 * address is used. After a temporary one, or none, the requested URL is
	 * kept, as XRay does.
	 *
	 * @since 2.0.0
	 *
	 * @param string $url      Requested URL.
	 * @param array  $response Response from pt_remote_get().
	 * @return string The URL to list.
	 */
	private static function feed_url( $url, $response ) {
		$object = isset( $response['http_response'] ) && is_object( $response['http_response'] ) && method_exists( $response['http_response'], 'get_response_object' ) ? $response['http_response']->get_response_object() : null;
		if ( ! $object || empty( $object->history ) || empty( $object->url ) ) {
			return $url;
		}
		$first = reset( $object->history );
		$code  = isset( $first->status_code ) ? (int) $first->status_code : 0;
		return in_array( $code, array( 301, 308 ), true ) ? $object->url : $url;
	}

	/**
	 * Checks whether a content type is one discovery knows how to handle.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content_type MIME type, without parameters.
	 * @return bool True if supported.
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
	 * Downloads a URL and returns the feeds it finds.
	 *
	 * Results are sorted by preference: jf2feed, microformats, jsonfeed,
	 * wordpress, atom, rss, then anything else.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL to scan.
	 * @return array|WP_Error Array with a 'results' key listing the feeds found (each
	 *                        with url, type, _feed_type and, where known, name and
	 *                        author), or WP_Error if the URL is invalid or cannot
	 *                        be fetched.
	 */
	public function fetch( $url ) {
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'invalid-url', __( 'A valid URL was not provided.', 'parse-this' ) );
		}

		$links = array();

		$response = pt_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$wprest        = array();
		$linkheaders   = wp_remote_retrieve_header( $response, 'link' );
		if ( $linkheaders ) {
			if ( is_array( $linkheaders ) ) {
				foreach ( $linkheaders as $link ) {
					if ( preg_match( '/<(.[^>]+)>;\s+rel\s?=\s?[\"\']?(https:\/\/)?api.w.org?\/?[\"\']?/i', $link, $result ) ) {
						$wprest[] = array(
							'url'        => untrailingslashit( pt_make_absolute_url( $result[1], $url ) ),
							'type'       => 'feed',
							'_feed_type' => 'wordpress',
							'name'       => 'WordPress REST API',
						);
					}
				}
			} elseif ( preg_match( '/<(.[^>]+)>;\s+rel\s?=\s?[\"\']?(https:\/\/)?api.w.org?\/?[\"\']?/i', $linkheaders, $result ) ) {
						$wprest[] = array(
							'url'        => untrailingslashit( pt_make_absolute_url( $result[1], $url ) ),
							'type'       => 'feed',
							'_feed_type' => 'wordpress',
							'name'       => 'WordPress REST API',
						);
			}
		}
		// Strip any character set off the content type.
		$ct = explode( ';', $content_type );
		if ( is_array( $ct ) ) {
			$content_type = array_shift( $ct );
		}
		$content_type = trim( $content_type );

		$content = wp_remote_retrieve_body( $response );
		// Feeds and JSON are often served with a generic or wrong content type.
		$content_type = pt_sniff_content_type( $content_type, $content );
		// A feed reached through a permanent redirect is listed at its new address.
		$feed_url = self::feed_url( $url, $response );
		// Find YouTube RSS feeds. Handle URLs (/@name) can't be mapped; their pages advertise the feed instead.
		if ( in_array( wp_parse_url( $url, PHP_URL_HOST ), array( 'www.youtube.com', 'm.youtube.com', 'youtube.com' ), true ) ) {
			$youtube_feed = self::youtube_rss( $url );
			if ( $youtube_feed ) {
				$links[] = array(
					'url'        => $youtube_feed,
					'type'       => 'feed',
					'_feed_type' => 'atom',
					'name'       => 'YouTube Feed',
				);
			}
		}
		// This is an RSS or Atom Feed URL and if it is not we do not know how to deal with XML anyway.
		if ( ( in_array( $content_type, array( 'application/rss+xml', 'application/atom+xml', 'text/xml', 'application/xml', 'text/xml' ), true ) ) ) {
			// SimplePie goes by the content type, so give it the sniffed one.
			$response['headers']['content-type'] = $content_type;
			$content                             = Parser::fetch_feed( $url, $response );
			if ( is_wp_error( $content ) ) {
				return $content;
			}
			if ( class_exists( RSS::class ) ) {
				$links[] = array(
					'url'        => $feed_url,
					'type'       => 'feed',
					'_feed_type' => RSS::get_type( $content ),
					'name'       => $content->get_title(),
				);
			}
			return array( 'results' => $links );
		}

		if ( in_array( $content_type, array( 'application/mf2+json', 'application/jf2+json', 'application/jf2feed+json' ), true ) ) {
			$content = json_decode( $content, true );
			// mf2 JSON whose first item is an h-feed, or a jf2 feed, is itself a feed.
			$first = $content['items'][0]['type'] ?? array();
			if ( 'application/mf2+json' === $content_type && is_array( $first ) && in_array( 'h-feed', $first, true ) ) {
				$links[] = array(
					'url'        => $feed_url,
					'type'       => 'feed',
					'_feed_type' => 'microformats',
				);
			} elseif ( 'application/jf2feed+json' === $content_type || ( is_array( $content ) && 'feed' === ( $content['type'] ?? '' ) ) ) {
				$links[] = array(
					'url'        => $feed_url,
					'type'       => 'feed',
					'_feed_type' => 'jf2feed',
				);
			}
			return array( 'results' => $links );
		}
		if ( in_array( $content_type, array( 'application/json', 'application/feed+json' ), true ) ) {
			$content = json_decode( $content, true );
			if ( is_array( $content ) && isset( $content['version'] ) && is_string( $content['version'] ) && 0 === strpos( $content['version'], 'https://jsonfeed.org/version/' ) ) {
				$links[] = array(
					'url'        => $feed_url,
					'type'       => 'feed',
					'_feed_type' => 'jsonfeed',
				);
			}
			return array( 'results' => $links );
		}
		if ( 'text/html' === $content_type ) {
			$doc = pt_load_domdocument( $content );
			if ( $doc instanceof \DOMDocument ) {
				$xpath = new \DOMXPath( $doc );
				// Fetch and gather <link> data.
				$mf2 = false;
				foreach ( $xpath->query( '(//link|//a)[@rel and @href]' ) as $link ) {
					$rel   = $link->getAttribute( 'rel' );
					$rels  = preg_split( '/\s+/', strtolower( trim( $rel ) ) );
					$href  = $link->getAttribute( 'href' );
					$title = $link->getAttribute( 'title' );
					$mime  = strtolower( trim( strtok( $link->getAttribute( 'type' ), ';' ) ) );
					$type  = self::get_feed_type( $mime );
					// rel=feed points to an h-feed page (https://indieweb.org/rel-feed); with
					// rel=alternate, text/html is usually a translation, not a feed.
					if ( in_array( 'feed', $rels, true ) && in_array( $mime, array( '', 'text/html' ), true ) ) {
						$type = 'microformats';
					}
					// An mf2 alternate of this page means the page itself needn't be checked;
					// rel=feed links point to other feed pages.
					if ( 'microformats' === $type && in_array( 'alternate', $rels, true ) ) {
						$mf2 = true;
					}
					// application/json: a WordPress REST API resource (every post links its own)
					// isn't a feed; anything else is a JSON Feed, as version 1 used that type.
					if ( 'json' === $type ) {
						$type = preg_match( '#/wp-json/|[?&]rest_route=#', $href ) ? '' : 'jsonfeed';
					}

					if ( array_intersect( $rels, array( 'alternate', 'feed' ) ) && ! empty( $type ) ) {
						$links[] = array_filter(
							array(
								'url'        => pt_make_absolute_url( $href, $url ),
								'type'       => 'feed',
								'_feed_type' => $type,
								'name'       => $title,
								'_mime-type' => $link->getAttribute( 'type' ),
								'_rel'       => $rel,
							)
						);
					}
					if ( in_array( 'https://api.w.org/', $rels, true ) && empty( $wprest ) ) {
						$wprest[] = array_filter(
							array(
								'url'        => untrailingslashit( pt_make_absolute_url( $href, $url ) ),
								'type'       => 'feed',
								'_feed_type' => 'wordpress',
								'name'       => 'WordPress REST API',
							)
						);
					}
				}

				// If an mf2 feed was found, do not check to see if this page is also one.
				if ( ! $mf2 ) {
					// Check to see if the current page is an h-feed.
					$feeds = MF2::find_hfeed( $doc, $url );
					foreach ( $feeds as $key => $feed ) {
						if ( ! MF2::is_microformat( $feed ) ) {
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
			}

			if ( ! empty( $wprest ) ) {
				$links = array_merge( $wprest, $links );
			}

			// Sort feeds by priority.
			$rank = array(
				'jf2feed'      => 0,
				'microformats' => 1,
				'jsonfeed'     => 2,
				'wordpress'    => 3,
				'atom'         => 4,
				'rss'          => 5,
			);
			usort(
				$links,
				function ( $a, $b ) use ( $rank ) {
					// Unknown feed types sort last.
					$rank_a = isset( $rank[ $a['_feed_type'] ] ) ? $rank[ $a['_feed_type'] ] : count( $rank );
					$rank_b = isset( $rank[ $b['_feed_type'] ] ) ? $rank[ $b['_feed_type'] ] : count( $rank );
					if ( $rank_a === $rank_b ) {
						return 0;
					}
					return ( $rank_a < $rank_b ) ? -1 : 1;
				}
			);

			// The same feed can be both constructed (YouTube) and advertised by the page; keep the first.
			$seen = array();
			foreach ( $links as $key => $link ) {
				if ( isset( $seen[ $link['url'] ] ) ) {
					unset( $links[ $key ] );
				}
				$seen[ $link['url'] ] = true;
			}

			return array( 'results' => array_values( $links ) );

		}

		return array( 'results' => $links );
	}


	/**
	 * Builds the RSS feed URL for a YouTube channel, user or playlist page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url YouTube page URL.
	 * @return string|null The feed URL, or null if $url is not a channel, user or
	 *                     playlist URL.
	 */
	private static function youtube_rss( $url ) {
		$youtube_url_base = 'https://www.youtube.com/feeds/videos.xml';
		$preg_entities    = array(
			'channel_id'  => '\/channel\/(([^\/])+?)$', // match YouTube channel ID from url.
			'user'        => '\/user\/(([^\/])+?)$', // match YouTube user from url.
			'playlist_id' => '\/playlist\?list=(([^\/])+?)$',  // match YouTube playlist ID from url.
		);

		foreach ( $preg_entities as $key => $preg_entity ) {
			if ( preg_match( '/' . $preg_entity . '/', $url, $matches ) ) {
				if ( isset( $matches[1] ) ) {
						return $youtube_url_base . '?' . $key . '=' . $matches[1];
				}
			}
		}
	}
}
