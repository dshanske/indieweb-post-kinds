<?php
// Parse This Global Functions


if ( ! function_exists( 'jf2_to_mf2' ) ) {
	function jf2_to_mf2( $jf2 ) {
		if ( ! $jf2 || ! is_array( $jf2 ) ) {
			return $jf2;
		}
		if ( 1 === count( $jf2 ) && array_key_exists( 'items', $jf2 ) ) {
			return array(
				'items' => array_map( 'jf2_to_mf2', $jf2['items'] ),
			);
		}

		if ( array_key_exists( 'properties', $jf2 ) || ! array_key_exists( 'type', $jf2 ) ) {
			return $jf2;
		}

		$mf2 = array();
		if ( array_key_exists( 'type', $jf2 ) ) {
			$mf2['type'] = array( 'h-' . $jf2['type'] );
			unset( $jf2['type'] );
		}
		if ( array_key_exists( 'children', $jf2 ) ) {
			$mf2['children'] = array_map( 'jf2_to_mf2', $jf2['children'] );
			unset( $jf2['children'] );
		}

		$mf2['properties'] = array();

		foreach ( $jf2 as $key => $value ) {
			// Exclude values
			if ( empty( $value ) || ( '_raw' === $key ) ) {
				continue;
			}
			if ( ! wp_is_numeric_array( $value ) && is_array( $value ) && array_key_exists( 'type', $value ) ) {
				$value = array( jf2_to_mf2( $value ) );
			} elseif ( wp_is_numeric_array( $value ) ) {
				$value = array_map( 'jf2_to_mf2', $value );
			} elseif ( ! wp_is_numeric_array( $value ) ) {
				$value = array( $value );
			}
			$mf2['properties'][ $key ] = $value;
		}
		return $mf2;
	}
}

if ( ! function_exists( 'mf2_to_jf2' ) ) {

	function mf2_to_jf2( $mf2 ) {
		if ( empty( $mf2 ) || is_string( $mf2 ) || is_object( $mf2 ) ) {
			return $mf2;
		}

		$jf2 = array();

		// If it is a numeric array, run this function through each item
		if ( wp_is_numeric_array( $mf2 ) ) {
			$jf2 = array_map( 'mf2_to_jf2', $mf2 );
			if ( 1 === count( $jf2 ) ) {
				return array_pop( $jf2 );
			}
			return $jf2;
		}

		if ( isset( $mf2['items'] ) ) {
			$jf2['items'] = array_map( 'mf2_to_jf2', $mf2['items'] );
		}

		if ( isset( $mf2['children'] ) ) {
			$jf2['children'] = array_map( 'mf2_to_jf2', $mf2['children'] );
		}

		if ( isset( $mf2['type'] ) ) {
			$type        = is_array( $mf2['type'] ) ? array_pop( $mf2['type'] ) : $mf2['type'];
			$jf2['type'] = str_replace( 'h-', '', $type );
		}
		if ( isset( $mf2['properties'] ) ) {
			foreach ( $mf2['properties'] as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( wp_is_numeric_array( $value ) ) {
						$value = array_map( 'mf2_to_jf2', $value );
						if ( is_countable( $value ) && 1 === count( $value ) ) {
							$value = array_pop( $value );
						}
					} elseif ( isset( $value['type'] ) ) {
						$value = mf2_to_jf2( $value );
					}
				}
				$jf2[ $key ] = $value;
			}
		}
		return $jf2;
	}
}


if ( ! function_exists( 'jf2_location' ) ) {
	/*
	 Flatten nested location properties.
	*/
	function jf2_location( $data ) {
		if ( ! array_key_exists( 'location', $data ) ) {
			return $data;
		}
		$location = $data['location'];
		if ( is_string( $location ) ) {
			return $data;
		}
		foreach ( array( 'latitude', 'longitude', 'altitude' ) as $prop ) {
			if ( array_key_exists( $prop, $location ) ) {
				$data[ $prop ] = $location[ $prop ];
			}
		}
		if ( array_key_exists( 'label', $location ) ) {
			$data['location'] = $location['label'];
		} elseif ( array_key_exists( 'name', $location ) ) {
			$data['location'] = $location['name'];
		} else {
			unset( $data['location'] );
		}
		if ( array_key_exists( 'checkin', $data ) && is_array( $data['checkin'] ) ) {
			foreach ( $location as $key => $value ) {
				if ( ! array_key_exists( $key, $data['checkin'] ) ) {
					$data['checkin'][ $key ] = $value;
				}
			}
		}
		return $data;
	}
}


if ( ! function_exists( 'jf2_references' ) ) {
	/*
	 Turns nested properties into references per the jf2 spec
	*/
	function jf2_references( $data ) {
		foreach ( $data as $key => $val ) {
			if ( ! is_array( $val ) ) {
				continue;
			}
			if ( ! wp_is_numeric_array( $val ) ) {
				$val = array( $val );
			}
			if ( wp_is_numeric_array( $val ) ) {
				foreach ( $val as $value ) {
					// Indicates nested type
					if ( is_array( $value ) && array_key_exists( 'type', $value ) && 'cite' === $value['type'] ) {
						if ( ! isset( $data['refs'] ) ) {
							$data['refs'] = array();
						}
						if ( isset( $value['url'] ) ) {
							$data['refs'][ $value['url'] ] = $value;
							$data[ $key ]                  = array( $value['url'] );
						}
					}
					if ( 'category' === $key ) {
						foreach ( $value as $k => $v ) {
							if ( is_array( $v ) && array_key_exists( 'type', $v ) ) {
								if ( ! isset( $data['refs'] ) ) {
									$data['refs'] = array();
								}
								if ( isset( $v['url'] ) ) {
									$data['refs'][ $v['url'] ] = $v;
									$data['category'][ $k ]    = $v['url'];
								}
							}
						}
					}
				}
			}
		}
		return $data;
	}
}

if ( ! function_exists( 'url_to_author' ) ) {
	/**
	 * Examine a url and try to determine the author ID it represents.
	 *
	 * @param string $url Permalink to check.
	 *
	 * @return WP_User, or null on failure.
	 */
	function url_to_author( $url ) {
		global $wp_rewrite;
		// check if url hase the same host
		if ( wp_parse_url( site_url(), PHP_URL_HOST ) !== wp_parse_url( $url, PHP_URL_HOST ) ) {
			return null;
		}
		// first, check to see if there is a 'author=N' to match against
		if ( preg_match( '/[?&]author=(\d+)/i', $url, $values ) ) {
			$id = absint( $values[1] );
			if ( $id ) {
				return get_user_by( 'id', $id );
			}
		}
		// check to see if we are using rewrite rules
		$rewrite = $wp_rewrite->wp_rewrite_rules();
		// not using rewrite rules, and 'author=N' method failed, so we're out of options
		if ( empty( $rewrite ) ) {
			return null;
		}
		// generate rewrite rule for the author url
		$author_rewrite = $wp_rewrite->get_author_permastruct();
		$author_regexp  = str_replace( '%author%', '', $author_rewrite );
		// match the rewrite rule with the passed url
		if ( preg_match( '/https?:\/\/(.+)' . preg_quote( $author_regexp, '/' ) . '([^\/]+)/i', $url, $match ) ) {
			$user = get_user_by( 'slug', $match[2] );
			if ( $user ) {
				return $user;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'url_to_user' ) ) {
	/**
	 * Get the user associated with a URL.
	 *
	 * @param string $url url to match
	 * @return WP_User $user Associated user, or null if no associated user
	 */
	function url_to_user( $url ) {
		if ( empty( $url ) ) {
			return null;
		}
		// Ensure has trailing slash
		$url = trailingslashit( $url );
		if ( ( 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) ) && ( wp_parse_url( home_url(), PHP_URL_HOST ) === wp_parse_url( $url, PHP_URL_HOST ) ) ) {
			$url = set_url_scheme( $url, 'https' );
		}
		// Try to save the expense of a search query if the URL is the site URL
		if ( home_url( '/' ) === $url ) {
			// Use the Indieweb settings to set the default author
			if ( class_exists( 'Indieweb_Plugin' ) && ( get_option( 'iw_single_author' ) || ! is_multi_author() ) ) {
				return get_user_by( 'id', get_option( 'iw_default_author' ) );
			}
			$users = get_users( array( 'who' => 'authors' ) );
			if ( 1 === count( $users ) ) {
				return $users[0];
			}
			return null;
		}
		// Check if this is a author post URL
		$user = url_to_author( $url );
		if ( $user instanceof WP_User ) {
			return $user;
		}
		$args  = array(
			'search'         => $url,
			'search_columns' => array( 'user_url' ),
		);
		$users = get_users( $args );
		// check result
		if ( ! empty( $users ) ) {
			return $users[0];
		}
		return null;
	}
}

if ( ! function_exists( 'ifset' ) ) {
		/**
		 * If set, return otherwise false.
		 *
		 * @param type $var Check if set.
		 * @return $var|false Return either $var or $return.
		 */
	function ifset( &$var, $return = false ) {

			return isset( $var ) ? $var : $return;
	}
}


/*
 Inverse of wp_parse_url
 *
 * Slightly modified from p3k-utils (https://github.com/aaronpk/p3k-utils)
 * Copyright 2017 Aaron Parecki, used with permission under MIT License
 *
 * @link http://php.net/parse_url
 * @param  string $parsed_url the parsed URL (wp_parse_url)
 * @return string             the final URL
 */
if ( ! function_exists( 'build_url' ) ) {
	function build_url( $parsed_url ) {
			$scheme   = ! empty( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
			$host     = ! empty( $parsed_url['host'] ) ? $parsed_url['host'] : '';
			$port     = ! empty( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
			$user     = ! empty( $parsed_url['user'] ) ? $parsed_url['user'] : '';
			$pass     = ! empty( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
			$pass     = ( $user || $pass ) ? "$pass@" : '';
			$path     = ! empty( $parsed_url['path'] ) ? $parsed_url['path'] : '';
			$query    = ! empty( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
			$fragment = ! empty( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

			return "$scheme$user$pass$host$port$path$query$fragment";
	}
}


if ( ! function_exists( 'normalize_url' ) ) {
	// Adds slash if no path is in the URL, and convert hostname to lowercase
	function normalize_url( $url, $strip = false ) {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['path'] ) ) {
				$parts['path'] = '/';
		}
		if ( $strip ) {
			$parts['query'] = '';
		}
		if ( isset( $parts['host'] ) ) {
				$parts['host'] = strtolower( $parts['host'] );
				return build_url( $parts );
		}
	}
}

if ( ! function_exists( 'normalize_iso8601' ) ) {
	// Tries to normalizes dates to a standard iso8601 string
	function normalize_iso8601( $string ) {
		$date = new DateTime( $string );
		if ( $date ) {
			return $date->format( DATE_W3C );
		}
		return $string;
	}
}

if ( ! function_exists( 'post_type_discovery' ) ) {
	function post_type_discovery( $jf2 ) {
		if ( ! is_array( $jf2 ) ) {
			return '';
		}
		if ( array_key_exists( 'properties', $jf2 ) ) {
			$jf2 = mf2_to_jf2( $jf2 );
		}
		if ( ! array_key_exists( 'type', $jf2 ) ) {
			return '';
		}
		if ( 'event' === $jf2['type'] ) {
			return 'event';
		}
		if ( 'entry' === $jf2['type'] ) {
			$map = array(
				'rsvp'      => array( 'rsvp' ),
				'checkin'   => array( 'checkin' ),
				'itinerary' => array( 'itinerary' ),
				'repost'    => array( 'repost-of' ),
				'like'      => array( 'like-of' ),
				'follow'    => array( 'follow-of' ),
				'tag'       => array( 'tag-of' ),
				'favorite'  => array( 'favorite-of' ),
				'bookmark'  => array( 'bookmark-of' ),
				'watch'     => array( 'watch-of' ),
				'jam'       => array( 'jam-of' ),
				'listen'    => array( 'listen-of' ),
				'read'      => array( 'read-of' ),
				'play'      => array( 'play-of' ),
				'eat'       => array( 'ate', 'pk-ate' ),
				'drink'     => array( 'drank', 'pk-drank' ),
				'reply'     => array( 'in-reply-to' ),
				'video'     => array( 'video' ),
				'photo'     => array( 'photo' ),
				'audio'     => array( 'audio' ),
			);
			foreach ( $map as $key => $value ) {
				$diff = array_intersect( array_keys( $jf2 ), $value );
				if ( ! empty( $diff ) ) {
					return $key;
				}
			}
			if ( isset( $jf2['name'] ) && ! empty( $jf2['name'] ) ) {
				$jf2['name'] = $jf2['name'];
				$content     = ifset( $jf2['content'] );
				if ( ! $content ) {
					$content = ifset( $jf2['summary'] );
				}
				if ( is_array( $content ) && array_key_exists( 'text', $content ) ) {
					$content = $content['text'];
				}
				if ( is_string( $content ) ) {
					$content = trim( $content );
					if ( 0 !== strpos( $content, $jf2['name'] ) ) {
						return 'article';
					}
				}
			}
				return 'note';
		}
		return '';
	}
}

if ( ! function_exists( 'seconds_to_iso8601' ) ) {
	function seconds_to_iso8601( $second ) {
		$h   = intval( $second / 3600 );
		$m   = intval( ( $second - $h * 3600 ) / 60 );
		$s   = $second - ( $h * 3600 + $m * 60 );
		$ret = 'PT';
		if ( $h ) {
			$ret .= $h . 'H';
		}
		if ( $m ) {
			$ret .= $m . 'M';
		}
		if ( ( ! $h && ! $m ) || $s ) {
			$ret .= $s . 'S';
		}
		return $ret;
	}
}

if ( ! function_exists( 'pt_load_domdocument' ) ) {
	function pt_load_domdocument( $content ) {
		if ( ! class_exists( '\Masterminds\HTML5', false ) ) {
			$file = plugin_dir_path( __DIR__ ) . 'lib/html5/autoloader.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
		if ( class_exists( 'Masterminds\\HTML5' ) ) {
			$doc = new \Masterminds\HTML5( array( 'disable_html_ns' => true ) );
			$doc = $doc->loadHTML( $content );
		} else {
			$doc = new DOMDocument();
			libxml_use_internal_errors( true );
			if ( function_exists( 'mb_convert_encoding' ) ) {
				$content = mb_convert_encoding( $content, 'HTML-ENTITIES', mb_detect_encoding( $content ) );
			}
			$doc->loadHTML( $content );
			libxml_use_internal_errors( false );
		}
		return $doc;
	}
}
if ( ! function_exists( 'pt_secure_rewrite' ) ) {
	function pt_secure_rewrite( $url ) {
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		$host   = preg_replace( '/^([a-zA-Z0-9].*\.)?([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z.]{2,})$/', '$2', $host );
		$secure = array(
			'blogger.com',
			'creativecommons.org',
			'dailymotion.com',
			'debian.org',
			'facebook.com',
			'foursquare.com',
			'feedburner.com',
			'fsf.org',
			'fsfe.org',
			'github.com',
			'gitlab.com',
			'gnu.org',
			'google.com',
			'gravatar.com',
			'gstatic.com',
			'kernel.org',
			'lwn.net',
			'tumblr.com',
			'twitter.com',
			'vimeo.com',
			'wikipedia.org',
			'wordpress.com',
			'youtube.com',
		);
		$secure = apply_filters( 'pt_rewrite_secure', $secure );
		if ( in_array( $host, $secure, true ) ) {
			$url = preg_replace( '/^http:/i', 'https:', $url );
		}
		return $url;
	}
}
