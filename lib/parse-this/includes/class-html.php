<?php
/**
 * HTML class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts jf2 from HTML pages without microformats.
 *
 * Reads Open Graph, Dublin Core, Parse.ly, citation and other meta tags, plus
 * <title>, <video> and <audio> elements. This is the last fallback in
 * Parser::parse(). Originally derived from the Press This code removed
 * from WordPress core.
 *
 * @since 1.0.0
 */
class HTML extends Base {
	/**
	 * Parses meta tags and media elements of an HTML document into jf2.
	 *
	 * @since 1.0.0
	 *
	 * @since 2.0.0 Returns an empty array for anything that isn't a DOMDocument,
	 *              rather than returning that value unchanged. Added $args.
	 *
	 * @param \DOMDocument|mixed $doc  Parsed HTML document.
	 * @param string             $url  URL of the page.
	 * @param array              $args Optional. Parse arguments (see Parser::parse()).
	 * @return array jf2 properties, or an empty array if $doc is not a
	 *               DOMDocument. When $args['debug'] is set, the collected meta tags are
	 *               included under '_meta'.
	 */
	public static function parse( $doc, $url, $args = array() ) {
		// Only HTML has meta tags. Decoded JSON (mf2 or jf2) must not be merged into results as is.
		if ( ! $doc instanceof \DOMDocument ) {
			return array();
		}
		$xpath = new \DOMXPath( $doc );

		$meta = array();
		// Look for OGP properties.
		foreach ( $xpath->query( '//meta[(@name or @property or @itemprop) and @content]' ) as $tag ) {
			$meta_name = self::limit_string( $tag->getAttribute( 'property' ) );
			if ( ! $meta_name ) {
				$meta_name = self::limit_string( $tag->getAttribute( 'name' ) );
			}
			if ( ! $meta_name ) {
				$meta_name = self::limit_string( $tag->getAttribute( 'itemprop' ) );
			}
			$meta_value = $tag->getAttribute( 'content' );

			// Sanity check. $key is usually things like 'title', 'description', 'keywords', etc.
			if ( strlen( $meta_name ) > 200 ) {
				continue;
			}
			// Decode known JSON encoded properties.
			if ( 'parsely-metadata' === $meta_name ) {
				$json = json_decode( $meta_value, true );
				if ( is_array( $json ) ) {
					$meta_value = $json;
				}
			}

			// Parsely-page is deprecated but convert it to the new parsely format.
			if ( 'parsely-page' === $meta_name ) {
				$json = json_decode( $meta_value, true );
				if ( is_array( $json ) ) {
					foreach ( $json as $key => $value ) {
						$key  = str_replace( '_', '-', $key );
						$meta = self::set( $meta, 'parsely-' . $key, $value );
					}
					continue;
				}
			}
			$meta = self::set( $meta, $meta_name, $meta_value );
		}

		$title = $xpath->query( '//title' )->item( 0 );
		if ( $title ) {
			$meta['title'] = trim( $title->textContent ); // phpcs:ignore
		}
		$meta          = self::parse_meta( $meta );
		if ( isset( $meta['og'] ) ) {
			$meta['og'] = self::parse_meta( $meta['og'] );
		}
		$jf2 = self::meta_to_jf2( $meta );

		if ( ! isset( $jf2['video'] ) ) {
			// Fetch and gather <video> data.
			$videos = array();
			foreach ( $xpath->query( '//video' ) as $video ) {
				$src = $video->getAttribute( 'src' );
				if ( ! empty( $src ) ) {
					$videos[] = pt_make_absolute_url( $src, $url );
				}
			}
			$jf2['video'] = array_unique( $videos );
		}

		if ( ! isset( $jf2['audio'] ) ) {
			// Fetch and gather <audio> data.
			$audios = array();

			foreach ( $xpath->query( '//audio' ) as $audio ) {
				$src = $audio->getAttribute( 'src' );
				if ( ! empty( $src ) ) {
					$audios[] = pt_make_absolute_url( $src, $url );
				}
			}

			foreach ( $xpath->query( '//figure' ) as $audio ) {
				$src = $audio->getAttribute( 'data-audio-url' );
				if ( ! empty( $src ) ) {
					$audios[] = pt_make_absolute_url( $src, $url );
				}
			}
			$jf2['audio'] = array_unique( $audios );
		}

		if ( ! empty( $args['debug'] ) ) {
			$jf2['_meta'] = $meta;
		}
		return array_filter( $jf2 );
	}

	/**
	 * Maps collected meta tag values to jf2 properties.
	 *
	 * Open Graph takes precedence, then Dublin Core, then citation_*, Parse.ly
	 * and generic tags. The og:type value selects the jf2 type: article becomes
	 * an entry, profile a card, and book, music.song and video types a cite.
	 *
	 * @since 1.0.0
	 * @since 2.0.1 Parse.ly tags are trimmed, article:modified_time becomes
	 *              updated rather than modified, and Dublin Core names (DC.,
	 *              dc. and DCTERMS.) are matched in any case.
	 *
	 * @param array $meta Meta values as grouped by parse_meta(), keyed by prefix
	 *                    (og, article, dc, music, video, ...) or by plain name.
	 * @return array jf2 properties.
	 */
	public static function meta_to_jf2( $meta ) {
		if ( empty( $meta ) ) {
			return array();
		}
		$jf2 = array();
		if ( isset( $meta['og'] ) ) {
			if ( isset( $meta['og']['url'] ) ) {
				$jf2['url'] = $meta['og']['url'];
			}
			if ( isset( $meta['og']['title'] ) ) {
				$jf2['name'] = $meta['og']['title'];
			}
			if ( isset( $meta['og']['description'] ) ) {
				$jf2['summary'] = $meta['og']['description'];
			}
			if ( isset( $meta['og']['image'] ) ) {
				$image = $meta['og']['image'];
				if ( is_string( $image ) ) {
					$jf2['featured'] = $image;
				} elseif ( is_array( $image ) ) {
					$jf2['featured'] = $image[0] ?? $image['secure_url'] ?? null;
				}
			}
			if ( isset( $meta['og']['site_name'] ) ) {
				$jf2['publication'] = $meta['og']['site_name'];
			}
			if ( isset( $meta['og']['video'] ) ) {
				$video = $meta['og']['video'];
				if ( is_string( $video ) ) {
					$jf2['video'] = $video;
				} elseif ( is_array( $video ) ) {
					$jf2['video']    = $video['url'] ?? $video[0] ?? null;
					$jf2['category'] = $video['tag'] ?? null;
				}
			}
			if ( isset( $meta['og']['audio'] ) ) {
				$jf2['audio'] = $meta['og']['audio'];
			}
			if ( isset( $meta['og']['locale'] ) ) {
				$jf2['locale'] = $meta['og']['locale'];
			}
			if ( isset( $meta['og']['longitude'] ) ) {
				$jf2['location'] = array(
					'longitude' => $meta['og']['longitude'],
					'latitude'  => $meta['og']['latitude'] ?? null,
				);
			}
			if ( isset( $meta['og']['type'] ) ) {
				$type = $meta['og']['type'];
				if ( isset( $meta[ $type ]['tag'] ) ) {
					$jf2['category'] = $meta[ $type ]['tag'];
				}
				if ( ! empty( $meta[ $type ]['author'] ) ) {
					$jf2['author'] = $meta[ $type ]['author'];
				}
				if ( 'article' === $type ) {
					$jf2['type'] = 'entry';
					$published   = $meta['article']['published_time'] ?? $meta['article']['published'] ?? null;
					if ( $published ) {
						$jf2['published'] = normalize_iso8601( $published );
					}
					$modified = $meta['article']['modified_time'] ?? $meta['article']['modified'] ?? null;
					if ( $modified ) {
						$jf2['updated'] = normalize_iso8601( $modified );
					}
					$jf2['category'] = $meta['article']['tag'] ?? null;
				}
				if ( 'book' === $type ) {
					$jf2['type'] = 'cite';
					if ( isset( $meta['book']['isbn'] ) ) {
						$jf2['uid'] = $meta['book']['isbn'];
					}
					if ( isset( $meta['book']['release_date'] ) ) {
						$jf2['release_date'] = $meta['book']['release_date'];
					}
				}
				if ( 'profile' === $type ) {
					$jf2['type'] = 'card';
				}
				if ( 'music.song' === $type ) {
					$jf2['type'] = 'cite';
					if ( isset( $meta['music']['musician'] ) ) {
						$jf2['author'] = $meta['music']['musician'];
					}
					if ( isset( $meta['music']['duration'] ) ) {
						$jf2['duration'] = $meta['music']['duration'];
					}
					if ( isset( $meta['music']['release_date'] ) ) {
						$jf2['release_date'] = $meta['music']['release_date'];
					}
					if ( isset( $meta['music']['album'] ) ) {
						$jf2['publication'] = $meta['music']['album'];
					}
				}
				if ( in_array( $type, array( 'video.movie', 'video.episode' ), true ) ) {
					$jf2['type'] = 'cite';
					if ( isset( $meta['video']['tag'] ) ) {
						$jf2['category'] = $meta['video']['tag'];
					}
					if ( isset( $meta['video']['release_date'] ) ) {
						$jf2['release_date'] = $meta['video']['release_date'];
					}
					if ( isset( $meta['video']['duration'] ) ) {
						$jf2['duration'] = $meta['video']['duration'];
					}
				}
			}
		}
		// Dublin Core: DC.title, dc.title, DCTERMS.title and so on, in any case.
		$dc = array();
		foreach ( $meta as $prefix => $values ) {
			if ( is_array( $values ) && in_array( strtolower( (string) $prefix ), array( 'dc', 'dcterms' ), true ) ) {
				foreach ( $values as $key => $value ) {
					$dc += array( strtolower( (string) $key ) => $value );
				}
			}
		}
		if ( $dc ) {
			if ( isset( $dc['title'] ) ) {
				$jf2['name'] = $dc['title'];
			}
			if ( isset( $dc['creator'] ) ) {
				if ( is_string( $dc['creator'] ) ) {
					$jf2['author'] = $dc['creator'];
				} else {
					$jf2['author'] = array();
					foreach ( $dc['creator'] as $creator ) {
						$jf2['author'][] = array(
							'type' => 'card',
							'name' => $creator,
						);
					}
				}
			}
			if ( isset( $dc['description'] ) ) {
				$jf2['summary'] = $dc['description'];
			}
			$date = $dc['date'] ?? ( $dc['issued'] ?? ( $dc['created'] ?? null ) );
			if ( $date && ! isset( $jf2['published'] ) ) {
				$jf2['published'] = normalize_iso8601( $date );
			}
		}

		if ( ! isset( $jf2['author'] ) ) {
			foreach ( array( 'citation_author', 'parsely-author', 'author' ) as $author ) {
				if ( isset( $meta[ $author ] ) ) {
					if ( is_string( $meta[ $author ] ) ) {
						$jf2['author'] = $meta[ $author ];
					} else {
						$jf2['author'] = array();
						foreach ( $meta[ $author ] as $a ) {
							$jf2['author'][] = $a;
						}
					}
					break;
				}
			}
		}

		if ( isset( $jf2['author'] ) && is_array( $jf2['author'] ) && 1 === count( $jf2['author'] ) ) {
			$jf2['author'] = array_pop( $jf2['author'] );
		}

		if ( ! isset( $jf2['featured'] ) && isset( $meta['parsely-image-url'] ) ) {
			$jf2['featured'] = esc_url_raw( $meta['parsely-image-url'] );
		}

		if ( empty( $jf2['category'] ) && isset( $meta['parsely-tags'] ) ) {
			$tags            = is_array( $meta['parsely-tags'] ) ? $meta['parsely-tags'] : explode( ',', $meta['parsely-tags'] );
			$jf2['category'] = array_values( array_filter( array_map( 'trim', array_filter( $tags, 'is_string' ) ), 'strlen' ) );
		}

		if ( ! isset( $jf2['latitude'] ) && isset( $meta['playfoursquare'] ) ) {
			$jf2['latitude']  = $meta['playfoursquare']['location:latitude'] ?? null;
			$jf2['longitude'] = $meta['playfoursquare']['location:longitude'] ?? null;
		}

		if ( ! isset( $jf2['duration'] ) && isset( $meta['duration'] ) ) {
			$jf2['duration'] = $meta['duration'];
		}
		if ( ! isset( $jf2['published'] ) ) {
			foreach ( array( 'citation_date', 'datePublished', 'parsely-pub-date' ) as $date ) {
				if ( isset( $meta[ $date ] ) ) {
					$jf2['published'] = normalize_iso8601( $meta[ $date ] );
					break;
				}
			}
		}

		// If Site Name is not set use domain name less www.
		if ( ! isset( $jf2['publication'] ) && isset( $jf2['url'] ) ) {
			$jf2['publication'] = preg_replace( '/^www\./', '', wp_parse_url( $jf2['url'], PHP_URL_HOST ) );
		}

		if ( ! isset( $jf2['name'] ) && isset( $meta['parsely-title'] ) ) {
			$jf2['name'] = $meta['parsely-title'];
		} elseif ( ! isset( $jf2['name'] ) && isset( $meta['name'] ) ) {
			$jf2['name'] = $meta['name'];
		}

		if ( ! isset( $jf2['type'] ) && isset( $meta['parsely-type'] ) ) {
			$jf2['type'] = ( 'post' === $meta['parsely-type'] ) ? 'entry' : 'feed';
		}

		return $jf2;
	}

	/**
	 * Groups prefixed meta names into nested arrays.
	 *
	 * For example og:title and og:image become $return['og']['title'] and
	 * $return['og']['image']. Names separated with a dot (DC.Title) are grouped
	 * the same way. Unprefixed names are kept at the top level.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta Meta values keyed by their full name.
	 * @return array Grouped meta values.
	 */
	public static function parse_meta( $meta ) {
		$return = array();
		if ( isset( $meta ) && is_array( $meta ) ) {
			foreach ( $meta as $key => $value ) {
				$name = explode( ':', $key );
				if ( 1 === count( $name ) ) {
					$name = explode( '.', $key );
				}
				if ( 1 < count( $name ) ) {
					$name = $name[0];
					$key  = str_replace( $name . ':', '', $key );
					$key  = str_replace( $name . '.', '', $key );
					if ( is_array( $value ) ) {
						$value = array_unique( $value );
						if ( 1 === count( $value ) ) {
							$value = array_shift( $value );
						}
					}
					if ( ! isset( $return[ $name ] ) ) {
						$return[ $name ] = array(
							$key => $value,
						);
					} else {
						if ( is_string( $return[ $name ] ) ) {
							$return[ $name ] = array( $return[ $name ] );
						}
						$return[ $name ][ $key ] = $value;
					}
				} else {
					$return[ $key ] = $value;
				}
			}
		}
		return $return;
	}
}
