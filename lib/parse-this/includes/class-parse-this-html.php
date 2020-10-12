<?php
/**
 * Parse This HTML class.
 * Originally Derived from the Press This Class with Enhancements.
 */
class Parse_This_HTML extends Parse_This_Base {
	/**
	 * Parses _meta, _images, and _links data from the content.
	 *
	 * @access public
	 */
	public static function parse( $doc, $url ) {
		if ( ! $doc ) {
			return array();
		}
		if ( ! is_object( $doc ) ) {
			return $doc;
		}
		$xpath = new DOMXPath( $doc );

		$meta = array();
		// Look for OGP properties
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
			// Decode known JSON encoded properties
			if ( in_array( $meta_name, array( 'parsely-page', 'parsely-metadata' ), true ) ) {
				$json = json_decode( $meta_value, true );
				if ( is_array( $json ) ) {
					$meta_value = $json;
				}
			}
			$meta = self::set( $meta, $meta_name, $meta_value );
		}

		$meta['title'] = trim( $xpath->query( '//title' )->item( 0 )->textContent );
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
					$videos = $src;
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
					$audios[] = $src;
				}
			}

			foreach ( $xpath->query( '//figure' ) as $audio ) {
				$src = $audio->getAttribute( 'data-audio-url' );
				if ( ! empty( $src ) ) {
					$audios[] = $src;
				}
			}
			$jf2['audio'] = array_unique( $audios );
		}

		/*
		 For now do not search every link embed etc
		// Fetch and gather <iframe> data.
		$embeds = array();

		foreach ( $xpath->query( '//iframe[@src]' ) as $embed ) {
			$src = self::limit_embed( $embed->getAttribute( 'src' ), $url );
			if ( ! empty( $src ) ) {
				$embeds[] = $src;
			}
		}

		// Fetch and gather <img> data.
		$images = array();
		foreach ( $xpath->query( '//img[@src]' ) as $image ) {
			$src = self::limit_img( $image->getAttribute( 'src' ), $url );
			if ( ! empty( $src ) ) {
				$images[] = $src;
			}
		}
		$images = array_unique( $images );

		// Fetch and gather <link> data.
		$links = array();

		foreach ( $xpath->query( '//link[@rel and @href]' ) as $link ) {
			$rel = $link->getAttribute( 'rel' );
			$url = self::limit_url( $link->getAttribute( 'href' ), $url );
			if ( ! empty( $url ) ) {
				$links[ $rel ] = $url;
			}
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
		} */

		if ( WP_DEBUG ) {
			$jf2['_meta'] = $meta;
		}
		return array_filter( $jf2 );
	}

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
					$jf2['video']    = ifset( $video['url'], ifset( $video[0] ) );
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
				if ( ! empty( $meta[ $type ]['author'] ) ) {
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
				$jf2['published'] = normalize_iso8601( $dc['Date'] );
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
		if ( ! isset( $jf2['latitude'] ) && isset( $meta['playfoursquare'] ) ) {
			$jf2['latitude']  = ifset( $meta['playfoursquare']['location:latitude'] );
			$jf2['longitude'] = ifset( $meta['playfoursquare']['location:longitude'] );
		}

		if ( ! isset( $jf2['duration'] ) && isset( $meta['duration'] ) ) {
			$jf2['duration'] = $meta['duration'];
		}
		if ( ! isset( $jf2['published'] ) && isset( $meta['citation_date'] ) ) {
			$jf2['published'] = normalize_iso8601( $meta['citation_date'] );
		} elseif ( ! isset( $jf2['published'] ) && isset( $meta['datePublished'] ) ) {
			$jf2['published'] = normalize_iso8601( $meta['datePublished'] );
		}

		if ( ! isset( $jf2['author'] ) && ! empty( $meta['author'] ) ) {
			$jf2['author'] = $meta['author'];
		}
		// If Site Name is not set use domain name less www
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
