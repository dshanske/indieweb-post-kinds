<?php
// Parse This Global Functions


if ( ! function_exists( 'jf2_to_mf2' ) ) {
	function jf2_to_mf2( $entry ) {
		if ( ! $entry || ! is_array( $entry ) | isset( $entry['properties'] ) ) {
			return $entry;
		}
		$return               = array();
		$return['type']       = array( 'h-' . $entry['type'] );
		$return['properties'] = array();
		unset( $entry['type'] );
		foreach ( $entry as $key => $value ) {
			// Exclude  values
			if ( empty( $value ) || ( '_raw' === $key ) ) {
				continue;
			}
			if ( ! wp_is_numeric_array( $value ) && is_array( $value ) && array_key_exists( 'type', $value ) ) {
				$value = jf2_to_mf2( $value );
			} elseif ( wp_is_numeric_array( $value ) && is_array( $value[0] ) && array_key_exists( 'type', $value[0] ) ) {
				foreach ( $value as $item ) {
					$items[] = jf2_to_mf2( $item );
				}
				$value = $items;
			} elseif ( ! wp_is_numeric_array( $value ) ) {
				$value = array( $value );
			} else {
				continue;
			}
			$return['properties'][ $key ] = $value;
		}
		return $return;
	}
}

if ( ! function_exists( 'mf2_to_jf2' ) ) {

	function mf2_to_jf2( $entry ) {
		if ( empty( $entry ) ) {
			return $entry;
		}
		if ( wp_is_numeric_array( $entry ) || ! isset( $entry['properties'] ) ) {
			return $entry;
		}
		$jf2         = array();
		$type        = is_array( $entry['type'] ) ? array_pop( $entry['type'] ) : $entry['type'];
		$jf2['type'] = str_replace( 'h-', '', $type );
		if ( isset( $entry['properties'] ) && is_array( $entry['properties'] ) ) {
			foreach ( $entry['properties'] as $key => $value ) {
				if ( is_array( $value ) && 1 === count( $value ) && wp_is_numeric_array( $value ) ) {
					$value = array_pop( $value );
				}
				if ( ! wp_is_numeric_array( $value ) && isset( $value['type'] ) ) {
					$value = mf2_to_jf2( $value );
				}
				$jf2[ $key ] = $value;
			}
		} elseif ( isset( $entry['items'] ) ) {
			$jf2['children'] = array();
			foreach ( $entry['items'] as $item ) {
				$jf2['children'][] = mf2_to_jf2( $item );
			}
		}
		return $jf2;
	}
}


if ( ! function_exists( 'jf2_references' ) ) {
	/* Turns nested properties into references per the jf2 spec
	*/
	function jf2_references( $data ) {
		foreach ( $data as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}
			// Indicates nested type
			if ( array_key_exists( 'type', $value ) && 'cite' === $value['type'] ) {
				if ( ! isset( $data['references'] ) ) {
					$data['references'] = array();
				}
				if ( isset( $value['url'] ) ) {
					$data['references'][ $value['url'] ] = $value;
					$data[ $key ]                        = array( $value['url'] );
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


/* Inverse of wp_parse_url
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
	function normalize_url( $url ) {
			$parts = wp_parse_url( $url );
		if ( empty( $parts['path'] ) ) {
				$parts['path'] = '/';
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
			$date->format( DATE_W3C );
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
