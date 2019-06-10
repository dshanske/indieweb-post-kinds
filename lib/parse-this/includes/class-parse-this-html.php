<?php
/**
 * Parse This HTML class.
 * Originally Derived from the Press This Class with Enhancements.
 *
 */
class Parse_This_HTML {
	/**
	 * Utility method to limit an array to 100 values.
	 * Originally set to 50 but some sites are very detailed in their meta.
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @param array $value Array to limit.
	 * @return array Original array if fewer than 100 values, limited array, empty array otherwise.
	 */
	private static function limit_array( $value ) {
		if ( is_array( $value ) ) {
			if ( count( $value ) > 100 ) {
				return array_slice( $value, 0, 100 );
			}

			return $value;
		}

		return array();
	}

	/**
	 * Utility method to limit the length of a given string to 5,000 characters.
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @param string $value String to limit.
	 * @return bool|int|string If boolean or integer, that value. If a string, the original value
	 *                         if fewer than 5,000 characters, a truncated version, otherwise an
	 *                         empty string.
	 */
	private static function limit_string( $value ) {
		$return = '';

		if ( is_numeric( $value ) || is_bool( $value ) ) {
			$return = $value;
		} elseif ( is_string( $value ) ) {
			if ( mb_strlen( $value ) > 5000 ) {
				$return = mb_substr( $value, 0, 5000 );
			} else {
				$return = $value;
			}
			$return = sanitize_text_field( trim( $return ) );
		}

		return $return;
	}

	/**
	 * Utility method to limit a given URL to 2,048 characters.
	 *
	 * @ignore
	 * @since 4.2.0
	 *
	 * @param string $url URL to check for length and validity.
	 * @param string $source_url URL URL to use to resolve relative URLs
	 * @return string Escaped URL if of valid length (< 2048) and makeup. Empty string otherwise.
	 */
	private static function limit_url( $url, $source_url ) {
		if ( ! is_string( $url ) ) {
			return '';
		}

		// HTTP 1.1 allows 8000 chars but the "de-facto" standard supported in all current browsers is 2048.
		if ( strlen( $url ) > 2048 ) {
			return ''; // Return empty rather than a truncated/invalid URL
		}

		// Does not look like a URL.
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		$url = WP_Http::make_absolute_url( $url, $source_url );

		return esc_url_raw( $url, array( 'http', 'https' ) );
	}

	/**
	 * Utility method to limit image source URLs.
	 *
	 * Excluded URLs include share-this type buttons, loaders, spinners, spacers, WordPress interface images,
	 * tiny buttons or thumbs, mathtag.com or quantserve.com images, or the WordPress.com stats gif.
	 *
	 *
	 * @param string $src Image source URL.
	 * @return string If not matched an excluded URL type, the original URL, empty string otherwise.
	 */
	private static function limit_img( $src, $source_url ) {
		$src = self::limit_url( $src, $source_url );

		if ( preg_match( '!/ad[sx]?/!i', $src ) ) {
			// Ads
			return '';
		} elseif ( preg_match( '!(/share-?this[^.]+?\.[a-z0-9]{3,4})(\?.*)?$!i', $src ) ) {
			// Share-this type button
			return '';
		} elseif ( preg_match( '!/(spinner|loading|spacer|blank|rss)\.(gif|jpg|png)!i', $src ) ) {
			// Loaders, spinners, spacers
			return '';
		} elseif ( preg_match( '!/([^./]+[-_])?(spinner|loading|spacer|blank)s?([-_][^./]+)?\.[a-z0-9]{3,4}!i', $src ) ) {
			// Fancy loaders, spinners, spacers
			return '';
		} elseif ( preg_match( '!([^./]+[-_])?thumb[^.]*\.(gif|jpg|png)$!i', $src ) ) {
			// Thumbnails, too small, usually irrelevant to context
			return '';
		} elseif ( false !== stripos( $src, '/wp-includes/' ) ) {
			// Classic WordPress interface images
			return '';
		} elseif ( false !== stripos( $src, '/wp-content/themes' ) ) {
			// Anything within a WordPress theme directory
			return '';
		} elseif ( false !== stripos( $src, '/wp-content/plugins' ) ) {
			// Anything within a WordPress plugin directory
			return '';
		} elseif ( preg_match( '![^\d]\d{1,2}x\d+\.(gif|jpg|png)$!i', $src ) ) {
			// Most often tiny buttons/thumbs (< 100px wide)
			return '';
		} elseif ( preg_match( '!/pixel\.(mathtag|quantserve)\.com!i', $src ) ) {
			// See mathtag.com and https://www.quantcast.com/how-we-do-it/iab-standard-measurement/how-we-collect-data/
			return '';
		} elseif ( preg_match( '!/[gb]\.gif(\?.+)?$!i', $src ) ) {
			// WordPress.com stats gif
			return '';
		}
		// Optionally add additional limits
		return apply_filters( 'parse_this_img_filters', $src );
	}

	/**
	 * Limit embed source URLs to specific providers.
	 *
	 * Not all core oEmbed providers are supported. Supported providers include YouTube, Vimeo,
	 * Vine, Daily Motion, SoundCloud, and Twitter.
	 *
	 *
	 * @param string $src Embed source URL.
	 * @param string $source_url Source URL
	 * @return string If not from a supported provider, an empty string. Otherwise, a reformatted embed URL.
	 */
	private static function limit_embed( $src, $source_url ) {
		$src = self::limit_url( $src, $source_url );

		if ( empty( $src ) ) {
			return '';
		}

		if ( preg_match( '!//(m|www)\.youtube\.com/(embed|v)/([^?]+)\?.+$!i', $src, $src_matches ) ) {
			// Embedded Youtube videos (www or mobile)
			$src = 'https://www.youtube.com/watch?v=' . $src_matches[3];
		} elseif ( preg_match( '!//player\.vimeo\.com/video/([\d]+)([?/].*)?$!i', $src, $src_matches ) ) {
			// Embedded Vimeo iframe videos
			$src = 'https://vimeo.com/' . (int) $src_matches[1];
		} elseif ( preg_match( '!//vimeo\.com/moogaloop\.swf\?clip_id=([\d]+)$!i', $src, $src_matches ) ) {
			// Embedded Vimeo Flash videos
			$src = 'https://vimeo.com/' . (int) $src_matches[1];
		} elseif ( preg_match( '!//vine\.co/v/([^/]+)/embed!i', $src, $src_matches ) ) {
			// Embedded Vine videos
			$src = 'https://vine.co/v/' . $src_matches[1];
		} elseif ( preg_match( '!//(www\.)?dailymotion\.com/embed/video/([^/?]+)([/?].+)?!i', $src, $src_matches ) ) {
			// Embedded Daily Motion videos
			$src = 'https://www.dailymotion.com/video/' . $src_matches[2];
		} else {
			$oembed = _wp_oembed_get_object();

			if ( ! $oembed->get_provider(
				$src,
				array(
					'discover' => false,
				)
			) ) {
				$src = '';
			}
		}

		return $src;
	}

	public static function set( $array, $key, $value ) {
		if ( ! isset( $array[ $key ] ) ) {
			$array[ $key ] = $value;
		} elseif ( is_string( $array[ $key ] ) ) {
			$array[ $key ] = array( $array[ $key ], $value );
		} elseif ( is_array( $array[ $key ] ) ) {
			$array[ $key ][] = $value;
		}
		return $array;
	}

	/**
	 * Parses _meta, _images, and _links data from the content.
	 *
	 * @access public
	 */
	public static function parse( $doc, $url ) {
		if ( ! $doc ) {
			return array();
		}
		$xpath = new DOMXPath( $doc );

		$jsonld = array();
		foreach ( $xpath->query( "//script[@type='application/ld+json']" ) as $script ) {
			$jsonld = json_decode( $script->textContent, true ); // phpcs:ignore
		}
		$meta = array();
		// Look for OGP properties
		foreach ( $xpath->query( '//meta[(@name or @property) and @content]' ) as $tag ) {
			$meta_name = self::limit_string( $tag->getAttribute( 'property' ) );
			if ( ! $meta_name ) {
				$meta_name = self::limit_string( $tag->getAttribute( 'name' ) );
			}
			$meta_value = $tag->getAttribute( 'content' );

			// Sanity check. $key is usually things like 'title', 'description', 'keywords', etc.
			if ( strlen( $meta_name ) > 200 ) {
				continue;
			}
			// Decode known JSON encoded properties
			if ( in_array( $meta_name, array( 'parsely-page', 'parsely-metadata' ), true ) ) {
				$json = json_decode( $meta_value, true );
				if ( is_array( $json ) ) {
					$meta_value = $json;
				}
			}
			$meta = self::set( $meta, $meta_name, $meta_value );
		}

		// Fetch and gather <img> data.
		$images = array();
		foreach ( $xpath->query( '//img[@src]' ) as $image ) {
			$src = self::limit_img( $image->getAttribute( 'src' ), $url );
			if ( ! empty( $src ) ) {
				$images[] = $src;
			}
		}

		// Fetch and gather <video> data.
		$videos = array();
		foreach ( $xpath->query( '//video' ) as $video ) {
			$src = $video->getAttribute( 'src' );
			if ( ! empty( $src ) ) {
				$videos = $src;
			}
		}

		// Fetch and gather <audio> data.
		$audios = array();

		foreach ( $xpath->query( '//audio' ) as $audio ) {
			$src = $audio->getAttribute( 'src' );
			if ( ! empty( $src ) ) {
				$audios[] = $src;
			}
		}
		foreach ( $xpath->query( '//figure' ) as $audio ) {
			$src = $audio->getAttribute( 'data-audio-url' );
			if ( ! empty( $src ) ) {
				$audios[] = $src;
			}
		}

		// Fetch and gather <iframe> data.
		$embeds = array();

		foreach ( $xpath->query( '//iframe[@src]' ) as $embed ) {
			$src = self::limit_embed( $embed->getAttribute( 'src' ), $url );
			if ( ! empty( $src ) ) {
				$embeds[] = $src;
			}
		}

		// Fetch and gather <link> data.
		$links = array();

		foreach ( $xpath->query( '//link[@rel and @href]' ) as $link ) {
			$rel = $link->getAttribute( 'rel' );
			$url = self::limit_url( $link->getAttribute( 'href' ), $url );
			if ( ! empty( $url ) ) {
				$links[ $rel ] = $url;
			}
		}

		$meta['title'] = trim( $xpath->query( '//title' )->item( 0 )->textContent );
		$meta          = self::parse_meta( $meta );
		if ( isset( $meta['og'] ) ) {
			$meta['og'] = self::parse_meta( $meta['og'] );
		}

		$video_extensions = array(
			'mp4',
			'mkv',
			'webm',
			'ogv',
			'avi',
			'm4v',
			'mpg',
		);
		$audio_extensions = array(
			'mp3',
			'ogg',
			'm4a',
			'm4b',
			'flac',
			'aac',
		);
		$urls             = array();
		foreach ( $xpath->query( '//a' ) as $link ) {
			$u         = WP_Http::make_absolute_url( $link->getAttribute( 'href' ), $url );
			$urls[]    = wp_http_validate_url( $u );
			$extension = pathinfo( wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION );
			if ( in_array( $extension, $audio_extensions, true ) ) {
				$audios[] = $url;
			}
			if ( in_array( $extension, $video_extensions, true ) ) {
				$videos[] = $url;
			}
		}

		// Possibility of finding the content
		$content = apply_filters( 'parse_this_content', '', $xpath, $url );

		$audios = array_unique( $audios );
		$videos = array_unique( $videos );
		$images = array_unique( $images );
		$return = array_filter( compact( 'jsonld', 'meta', 'images', 'embeds', 'audios', 'videos', 'links', 'content' ) );
		$jf2    = self::raw_to_jf2( $return );
		if ( WP_DEBUG ) {
			$jf2['_raw'] = $return;
		}
		return $jf2;
	}

	public static function raw_to_jf2( $raw ) {
		if ( empty( $raw ) ) {
			return array();
		}
		$jf2  = array();
		$meta = isset( $raw['meta'] ) ? $raw['meta'] : array();
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
					$jf2['photo'] = $image;
				} elseif ( is_array( $image ) ) {
					$jf2['photo'] = ifset( $image[0], ifset( $image['secure_url'] ) );
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
					$jf2['video']    = ifset( $video['url'] );
					$jf2['category'] = ifset( $video['tag'] );
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
					'latitude'  => $meta['og']['longitude'],
				);
			}
			if ( isset( $meta['og']['type'] ) ) {
				$type = $meta['og']['type'];
				if ( isset( $meta[ $type ]['tag'] ) ) {
					$jf2['category'] = $meta[ $type ]['tag'];
				}
				if ( isset( $meta[ $type ]['author'] ) ) {
					$jf2['author'] = $meta[ $type ]['author'];
				}
				if ( 'article' === $type ) {
					$jf2['type'] = 'entry';
					$published   = ifset( $meta['article']['published_time'], ifset( $meta['article']['published'] ) );
					if ( $published ) {
						$jf2['published'] = normalize_iso8601( $published );
					}
					$modified = ifset( $meta['article']['modified_time'], ifset( $meta['article']['modified'] ) );
					if ( $modified ) {
						$jf2['modified'] = normalize_iso8601( $modified );
					}
					$jf2['category'] = ifset( $meta['article']['tag'] );
				}
				if ( 'book' === $type ) {
					$jf2['type'] = 'cite';
					if ( isset( $meta['book']['isbn'] ) ) {
						$jf2['uid'] = $meta['book']['isbn'];
					}
					if ( isset( $meta['release_date'] ) ) {
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
		if ( isset( $meta['dc'] ) ) {
			$dc = $meta['dc'];
			if ( isset( $dc['Title'] ) ) {
				$jf2['name'] = $dc['Title'];
			}
			if ( isset( $dc['Creator'] ) ) {
				if ( is_string( $dc['Creator'] ) ) {
					$jf2['author'] = $dc['Creator'];
				} else {
					$jf2['author'] = array();
					foreach ( $dc['Creator'] as $creator ) {
						$jf2['author'][] = array(
							'type' => 'card',
							'name' => $creator,
						);
					}
				}
			}
			if ( isset( $dc['Description'] ) ) {
				$jf2['summary'] = $dc['Description'];
			}
			if ( isset( $dc['Date'] ) && ! isset( $jf2['published'] ) ) {
				$jf2['published'] = $dc['Date'];
			}
		}
		if ( isset( $meta['parsely-page'] ) ) {
			$parsely = $meta['parsely-page'];
			if ( ! isset( $jf2['author'] ) && isset( $parsely['author'] ) ) {
				$jf2['author'] = $parsely['author'];
			}
			if ( ! isset( $jf2['published'] ) && isset( $parsely['pub_date'] ) ) {
				$jf2['published'] = normalize_iso8601( $parsely['pub_date'] );
			}
			if ( ! isset( $jf2['featured'] ) && isset( $parsely['pub_date'] ) ) {
				$jf2['featured'] = esc_url_raw( $parsely['image_url'] );
			}
		}
		if ( ! isset( $jf2['author'] ) && isset( $meta['citation_author'] ) ) {
			if ( is_string( $meta['citation_author'] ) ) {
				$jf2['author'] = $meta['citation_author'];
			} else {
				$jf2['author'] = array();
				foreach ( $meta['citation_author'] as $a ) {
					$jf2['author'][] = array(
						'type' => 'card',
						'name' => $a,
					);
				}
			}
		}
		if ( ! isset( $jf2['published'] ) && isset( $meta['citation_date'] ) ) {
			$jf2['published'] = normalize_iso8601( $meta['citation_date'] );
		}

		if ( ! isset( $jf2['author'] ) && isset( $meta['author'] ) ) {
			$jf2['author'] = $meta['author'];
		}
		if ( ! empty( $raw['audios'] ) && ! isset( $jf2['audio'] ) ) {
			$jf2['audio'] = $raw['audios'];
		}
		if ( ! empty( $raw['videos'] ) && ! isset( $jf2['video'] ) ) {
			$jf2['video'] = $raw['videos'];
		}

		if ( isset( $raw['jsonld'] ) ) {
			$jsonld = $raw['jsonld'];
			if ( ! isset( $jf2['author'] ) && isset( $jsonld['author'] ) ) {
				$jf2['author'] = ifset( $jsonld['author']['name'] );
			}
			if ( ! isset( $jf2['published'] ) && isset( $jsonld['datePublished'] ) ) {
				$jf2['published'] = normalize_iso8601( $jsonld['datePublished'] );
			}

			if ( ! isset( $jf2['updated'] ) && isset( $jsonld['dateModified'] ) ) {
				$jf2['updated'] = normalize_iso8601( $jsonld['dateModified'] );
			}

			if ( ! isset( $jf2['publication'] ) && isset( $jsonld['publisher'] ) ) {
				$jf2['publication'] = ifset( $jsonld['publisher']['name'] );
			}
		}

		//  If Site Name is not set use domain name less www
		if ( ! isset( $jf2['publication'] ) && isset( $jf2['url'] ) ) {
			$jf2['publication'] = preg_replace( '/^www\./', '', wp_parse_url( $jf2['url'], PHP_URL_HOST ) );
		}

		if ( ! isset( $jf2['name'] ) ) {
			$jf2['name'] = $meta['title'];
		}

		return $jf2;
	}

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
