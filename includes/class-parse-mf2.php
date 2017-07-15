<?php
/**
 * Helpers for processing microformats2 array structures.
 * Derived from https://github.com/barnabywalters/php-mf-cleaner
 * and https://github.com/aaronpk/XRay/blob/master/lib/Formats/Mf2.php
 * and https://github.com/pfefferle/wordpress-semantic-linkbacks/blob/master/includes/class-linkbacks-mf2-handler.php
 **/

class Parse_MF2 {

	public static function fetch($url) {
		global $wp_version;
		if ( ! isset( $url ) || ! self::is_url( $url ) ) {
			return new WP_Error( 'invalid-url', __( 'A valid URL was not provided.' ) );
		}
		$args = array(
			'timeout' => 10,
			'limit_response_size' => 1048576,
			'redirection' => 20,
			// Use an explicit user-agent for Post Kinds
			'user-agent' => 'Post Kinds (WordPress/' . $wp_version . '); ' . get_bloginfo( 'url' ),
		);
		$response = wp_safe_remote_head( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( preg_match( '#(image|audio|video|model)/#is', wp_remote_retrieve_header( $response, 'content-type' ) ) ) {
			return new WP_Error( 'content-type', 'Content Type is Media' );
		}
		$response = wp_safe_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );
		return $body;
	}

	/**
	 * Is string a URL.
	 *
	 * @param array $string
	 * @return bool
	 */
	public static function is_url( $string ) {
		return preg_match( '/^https?:\/\/.+\..+$/', $string );
	}

	/**
	 * Is this an h-card.
	 *
	 * @param array $mf Parsed Microformats Array.
	 * @return bool
	 */
	public static function is_hcard( $mf ) {
		return is_array( $mf ) and ! empty( $mf['type'] ) and is_array( $mf['type'] ) and in_array( 'h-card', $mf['type'] );
	}

	/**
	 * Parse Content
	 *
	 * @param array $mf Parsed Microformats Array.
	 * @return array $data Content array consisting of text and html properties.
	 */
	public static function parse_html_value( $mf, $property ) {
		if ( ! array_key_exists( $property, $mf['properties'] ) ) {
			return null;
		}
		$textcontent = false;
		$htmlcontent = false;
		$content = $mf['properties'][$property][0];
		if ( is_string( $content ) ) {
			 $textcontent = $content;
		} elseif ( ! is_string( $content ) && is_array( $content ) && array_key_exists( 'value', $content ) ) {
			if ( array_key_exists( 'html', $content ) ) {
				$htmlcontent = trim( wp_kses_post( $content['html'] ) );
				$textcontent = trim( str_replace( '&#xD;', "\r", $content['value'] ) );
			} else {
				$textcontent = trim( $content['value'] );
			}
		}
		$data = array( 'text' => $textcontent );
		if ( $htmlcontent && $textcontent != $htmlcontent ) {
			$data['html'] = $htmlcontent;
		}
		return $data;
	}


	/**
	 * Iterates over array keys, returns true if has numeric keys.
	 *
	 * @param array $arr
	 * @return bool
	 */
	public static function has_numeric_keys(array $arr) {
		foreach ( $arr as $key => $val ) { if ( is_numeric( $key ) ) { return true; }
		}
		return false;
	}

	/**
	 * Verifies if $mf is an array without numeric keys, and has a 'properties' key.
	 *
	 * @param $mf
	 * @return bool
	 */
	public static function is_microformat($mf) {
		return (is_array( $mf ) and ! self::has_numeric_keys( $mf ) and ! empty( $mf['type'] ) and isset( $mf['properties'] ));
	}


	/**
	 * Verifies if $mf has an 'items' key which is also an array, returns true.
	 *
	 * @param $mf
	 * @return bool
	 */
	public static function is_microformat_collection($mf) {
		return (is_array( $mf ) and isset( $mf['items'] ) and is_array( $mf['items'] ));
	}

	/**
	 * Verifies if $p is an array without numeric keys and has key 'value' and 'html' set.
	 *
	 * @param $p
	 * @return bool
	 */
	public static function is_embedded_html($p) {
		return is_array( $p ) and ! self::hasNumericKeys( $p ) and isset( $p['value'] ) and isset( $p['html'] );
	}

	/**
	 * Verifies if property named $propname is in array $mf.
	 *
	 * @param array    $mf
	 * @param $propname
	 * @return bool
	 */
	public static function has_prop(array $mf, $propname) {
		return ! empty( $mf['properties'][$propname] ) and is_array( $mf['properties'][$propname] );
	}


	/**
	 * Verifies if rel named $relname is in array $mf.
	 *
	 * @param array    $mf
	 * @param $relname
	 * @return bool
	 */
	public static function has_rel(array $mf, $relname) {
		return ! empty( $mf['rels'][$relname] ) and is_array( $mf['rels'][$relname] );
	}


	/**
	 * shortcut for getPlaintext.
	 *
	 * @deprecated use getPlaintext from now on
	 * @param array       $mf
	 * @param $propName
	 * @param null|string $fallback
	 * @return mixed|null
	 */
	public static function get_prop(array $mf, $propName, $fallback = null) {
		return self::get_plaintext( $mf, $propName, $fallback );
	}

	/**
	 * If $v is a microformat or embedded html, return $v['value']. Else return v.
	 *
	 * @param $v
	 * @return mixed
	 */
	public static function to_plaintext($v) {
		if ( self::is_microformat( $v ) or self::is_embedded_html( $v ) ) {
			return $v['value']; }
		return $v;
	}

	/**
	 * Returns plaintext of $propName with optional $fallback
	 *
	 * @param array       $mf
	 * @param $propName
	 * @param null|string $fallback
	 * @return mixed|null
	 * @link http://php.net/manual/en/function.current.php
	 */
	public static function get_plaintext(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return self::to_plaintext( current( $mf['properties'][$propName] ) );
		}
		return $fallback;
	}

	/**
	 * Converts $propName in $mf into array_map plaintext, or $fallback if not valid.
	 *
	 * @param array       $mf
	 * @param $propName
	 * @param null|string $fallback
	 * @return null
	 */
	public static function get_plaintext_array(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return array_map( array( 'Parse_Mf2', 'to_plaintext' ), $mf['properties'][$propName] ); }
		return $fallback;
	}

	/**
	 *  Return an array of properties, and may contain plaintext content
	 *
	 * @param array       $mf
	 * @param array $properties
	 * @param null|string $fallback
	 * @return null|array
	 */
	public static function get_prop_array(array $mf, $properties, $fallback = null) {
		$data = array();
		foreach ( $properties as $p ) {
			if ( array_key_exists( $p, $mf['properties'] ) ) {
				foreach ( $mf['properties'][$p] as $v ) {
					if ( is_string( $v ) ) {
						if ( ! array_key_exists( $p, $data ) ) {
							$data[$p] = array();
						}
						$data[$p][] = $v;
					} elseif ( self::is_microformat( $v ) ) {
						if ( ($u = self::get_plaintext( $v, 'url' )) && self::is_URL( $u ) ) {
							if ( ! array_key_exists( $p, $data ) ) {
								$data[$p] = array();
							}
							$data[$p][] = $u;
						}
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Returns ['html'] element of $v, or ['value'] or just $v, in order of availablility.
	 *
	 * @param $v
	 * @return mixed
	 */
	public static function to_html($v) {
		if ( self::is_embedded_html( $v ) ) {
			return $v['html']; } elseif ( self::is_microformat( $v ) ) {
			return htmlspecialchars( $v['value'] ); }
			return htmlspecialchars( $v );
	}

	/**
	 * Gets HTML of $propName or if not, $fallback
	 *
	 * @param array       $mf
	 * @param $propName
	 * @param null|string $fallback
	 * @return mixed|null
	 */
	public static function get_html(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return self::to_html( current( $mf['properties'][$propName] ) ); }
		return $fallback;
	}



	/**
	 * Returns 'summary' element of $mf or a truncated Plaintext of $mf['properties']['content'] with 19 chars and ellipsis.
	 *
	 * @deprecated as not often used
	 * @param array $mf
	 * @param array $content
	 * @return mixed|null|string
	 */
	public static function get_summary( array $mf, $content = null ) {
		if ( self::has_prop( $mf, 'summary' ) ) {
			return self::get_prop( $mf, 'summary' );
		}
		if ( ! $content ) {
			$content = self::parse_html_value( $mf, 'content' );
		}
		$summary = substr( $content['text'], 0, 300 );
		if ( 300 < strlen( $content['text'] ) ) {
			$summary .= '...';
		}
		return $summary;
	}


	/**
	 * Gets the date published of $mf array.
	 *
	 * @param array       $mf
	 * @param bool        $ensureValid
	 * @param null|string $fallback optional result if date not available
	 * @return mixed|null
	 */
	public static function get_published(array $mf, $ensureValid = false, $fallback = null) {
		return self::get_datetime_property( 'published', $mf, $ensureValid, $fallback );
	}

	/**
	 * Gets the date updated of $mf array.
	 *
	 * @param array $mf
	 * @param bool  $ensureValid
	 * @param null  $fallback
	 * @return mixed|null
	 */
	public static function get_updated(array $mf, $ensureValid = false, $fallback = null) {
		return self::get_datetime_property( 'updated', $mf, $ensureValid, $fallback );
	}

	/**
	 * Gets the DateTime properties including published or updated, depending on params.
	 *
	 * @param $name string updated or published
	 * @param array                            $mf
	 * @param bool                             $ensureValid
	 * @param null|string                      $fallback
	 * @return mixed|null
	 */
	public static function get_datetime_property($name, array $mf, $ensureValid = false, $fallback = null) {
		$compliment = 'published' === $name ? 'updated' : 'published';
		if ( self::has_prop( $mf, $name ) ) {
			$return = self::get_prop( $mf, $name ); } elseif ( self::has_prop( $mf, $compliment ) ) {
			$return = self::get_prop( $mf, $compliment );
			} else { 		return $fallback; }
			if ( ! $ensureValid ) {
				return $return; } else {
				try {
					new DateTime( $return );
					return $return;
				} catch (Exception $e) {
					return $fallback;
				}
				}
	}

	/**
	 * True if same hostname is parsed on both
	 *
	 * @param $u1 string url
	 * @param $u2 string url
	 * @return bool
	 * @link http://php.net/manual/en/function.parse-url.php
	 */
	public static function same_hostname($u1, $u2) {
		return wp_parse_url( $u1, PHP_URL_HOST ) === wp_parse_url( $u2, PHP_URL_HOST );
	}


	/**
	 * Large function for fishing out author of $mf from various possible array elements.
	 *
	 * @param array      $mf
	 * @param array|null $context
	 * @param null       $url
	 * @param bool       $matchName
	 * @param bool       $matchHostname
	 * @return mixed|null
	 * @todo: this needs to be just part of an indiewebcamp.com/authorship algorithm, at the moment it tries to do too much
	 * @todo: maybe split some bits of this out into separate functions
	 */
	public static function get_author(array $mf, array $context = null, $url = null, $matchName = true, $matchHostname = true) {
		$entryAuthor = null;

		if ( null === $url and self::has_prop( $mf, 'url' ) ) {
			$url = self::get_prop( $mf, 'url' ); }

		if ( self::has_prop( $mf, 'author' ) and self::is_microformat( current( $mf['properties']['author'] ) ) ) {
			$entryAuthor = current( $mf['properties']['author'] ); } elseif ( self::has_prop( $mf, 'reviewer' ) and self::is_microformat( current( $mf['properties']['author'] ) ) ) {
			$entryAuthor = current( $mf['properties']['reviewer'] ); } elseif ( self::has_prop( $mf, 'author' ) ) {
				$entryAuthor = self::get_plaintext( $mf, 'author' ); }

			// If we have no context that’s the best we can do
			if ( null === $context ) {
				return $entryAuthor; }

			// Whatever happens after this we’ll need these
			$flattenedMf = self::flatten_microformats( $context );
			$hCards = self::find_microformats_by_type( $flattenedMf, 'h-card', false );
			if ( is_string( $entryAuthor ) ) {
				// look through all page h-cards for one with this URL
				$authorHCards = self::find_microformats_by_property( $hCards, 'url', $entryAuthor, false );
				if ( ! empty( $authorHCards ) ) {
					$entryAuthor = current( $authorHCards ); }
			}
			if ( is_string( $entryAuthor ) and $matchName ) {
				// look through all page h-cards for one with this name
				$authorHCards = self::find_microformats_by_property( $hCards, 'name', $entryAuthor, false );

				if ( ! empty( $authorHCards ) ) {
					$entryAuthor = current( $authorHCards ); }
			}

			if ( null !== $entryAuthor ) {
				return $entryAuthor; }

			// look for page-wide rel-author, h-card with that
			if ( ! empty( $context['rels'] ) and ! empty( $context['rels']['author'] ) ) {
				// Grab first href with rel=author
				$relAuthorHref = current( $context['rels']['author'] );

				$relAuthorHCards = self::find_microformats_by_property( $hCards, 'url', $relAuthorHref );

				if ( ! empty( $relAuthorHCards ) ) {
					return current( $relAuthorHCards ); }
			}
			// look for h-card with same hostname as $url if given
			if ( null !== $url and $matchHostname ) {
				$sameHostnameHCards = self::find_microformats_by_callable($hCards, function ($mf) use ($url) {
					if ( ! has_prop( $mf, 'url' ) ) {
						return false; }
					foreach ( $mf['properties']['url'] as $u ) {
						if ( same_hostname( $url, $u ) ) {
							return true; }
					}
				}, false);
				if ( ! empty( $sameHostnameHCards ) ) {
					return current( $sameHostnameHCards ); }
			}
			// Without fetching, this is the best we can do. Return the found string value, or null.
			return empty( $relAuthorHref )
			? null
			: $relAuthorHref;
	}

	public static function find_author($item, $mf2 ) {
		$author = array(
			'type' => 'card',
			'name' => null,
			'url' => null,
			'photo' => null,
		);
		// Author Discovery
		// http://indieweb,org/authorship
		$authorpage = false;
		if ( array_key_exists( 'author', $item['properties'] ) ) {
			// Check if any of the values of the author property are an h-card
			foreach ( $item['properties']['author'] as $a ) {
				if ( self::is_hcard( $a ) ) {
					// 5.1 "if it has an h-card, use it, exit."
					return $a;
				} elseif ( is_string( $a ) ) {
					if ( self::is_url( $a ) ) {
						// 5.2 "otherwise if author property is an http(s) URL, let the author-page have that URL"
						$authorpage = $a;
					} else {
						// 5.3 "otherwise use the author property as the author name, exit"
						// We can only set the name, no h-card or URL was found
						$author['name'] = self::get_plaintext( $item, 'author' );
						return $author;
					}
				} else {
					// This case is only hit when the author property is an mf2 object that is not an h-card
					$author['name'] = self::get_plaintext( $item, 'author' );
					return $author;
				}
			}
		}
		// 6. "if no author page was found" ... check for rel-author link
		if ( ! $authorpage ) {
			if ( isset( $mf2['rels'] ) && isset( $mf2['rels']['author'] ) ) {
				$authorpage = $mf2['rels']['author'][0];
			}
		}
		// 7. "if there is an author-page URL" ...
		if ( $authorpage ) {
			$author['url'] = $authorpage;
			return $author;
		}
	}



	/**
	 * Returns array per parse_url standard with pathname key added.
	 *
	 * @param $url
	 * @return mixed
	 * @link http://php.net/manual/en/function.parse-url.php
	 */
	public static function parse_url($url) {
		$r = wp_parse_url( $url );
		$r['pathname'] = empty( $r['path'] ) ? '/' : $r['path'];
		return $r;
	}


	/**
	 * See if urls match for each component of parsed urls. Return true if so.
	 *
	 * @param $url1
	 * @param $url2
	 * @return bool
	 * @see parseUrl()
	 */
	public static function urls_match($url1, $url2) {
		$u1 = parse_url( $url1 );
		$u2 = parse_url( $url2 );
		foreach ( array_merge( array_keys( $u1 ), array_keys( $u2 ) ) as $component ) {
			if ( ! array_key_exists( $component, $u1 ) or ! array_key_exists( $component, $u1 ) ) {
				return false;
			}
			if ( $u1[$component] != $u2[$component] ) {
				return false;
			}
		}
		return true;
	}
	/**
	 * Representative h-card
	 *
	 * Given the microformats on a page representing a person or organisation (h-card), find the single h-card which is
	 * representative of the page, or null if none is found.
	 *
	 * @see http://microformats.org/wiki/representative-h-card-parsing
	 *
	 * @param array  $mfs The parsed microformats of a page to search for a representative h-card
	 * @param string $url The URL the microformats were fetched from
	 * @return array|null Either a single h-card array structure, or null if none was found
	 */
	public static function get_representative_hcard(array $mfs, $url) {
		$hCardsMatchingUidUrlPageUrl = find_microformats_by_callable($mfs, function ($hCard) use ($url) {
			return has_prop( $hCard, 'uid' ) and has_prop( $hCard, 'url' )
			and urls_match( get_plaintext( $hCard, 'uid' ), $url )
			and count(array_filter($hCard['properties']['url'], function ($u) use ($url) {
				return urls_match( $u, $url );
			})) > 0;
		});
		if ( ! empty( $hCardsMatchingUidUrlPageUrl ) ) { return $hCardsMatchingUidUrlPageUrl[0]; }
		if ( ! empty( $mfs['rels']['me'] ) ) {
			$hCardsMatchingUrlRelMe = self::find_microformats_by_callable($mfs, function ($hCard) use ($mfs) {
				if ( hasProp( $hCard, 'url' ) ) {
					foreach ( $mfs['rels']['me'] as $relUrl ) {
						foreach ( $hCard['properties']['url'] as $url ) {
							if ( urlsMatch( $url, $relUrl ) ) {
								return true;
							}
						}
					}
				}
				return false;
			});
			if ( ! empty( $hCardsMatchingUrlRelMe ) ) { return $hCardsMatchingUrlRelMe[0]; }
		}
		$hCardsMatchingUrlPageUrl = find_microformats_by_callable($mfs, function ($hCard) use ($url) {
			return has_prop( $hCard, 'url' )
			and count(array_filter($hCard['properties']['url'], function ($u) use ($url) {
				return urls_match( $u, $url );
			})) > 0;
		});
		if ( count( $hCardsMatchingUrlPageUrl ) === 1 ) { return $hCardsMatchingUrlPageUrl[0]; }
		// Otherwise, no representative h-card could be found.
		return null;
	}

	/**
	 * Flattens microformats. Can intake multiple Microformats including possible MicroformatCollection.
	 *
	 * @param array $mfs
	 * @return array
	 */
	public static function flatten_microformat_properties(array $mf) {
		$items = array();

		if ( ! self::is_microformat( $mf ) ) {
			return $items; }

		foreach ( $mf['properties'] as $propArray ) {
			foreach ( $propArray as $prop ) {
				if ( self::is_microformat( $prop ) ) {
					$items[] = $prop;
					$items = array_merge( $items, self::flatten_microformat_properties( $prop ) );
				}
			}
		}

		return $items;
	}

	/**
	 * Flattens microformats. Can intake multiple Microformats including possible MicroformatCollection.
	 *
	 * @param array $mfs
	 * @return array
	 */
	public static function flatten_microformats(array $mfs) {
		if ( self::is_microformat_collection( $mfs ) ) {
			$mfs = $mfs['items']; } elseif ( self::is_microformat( $mfs ) ) {
			$mfs = array( $mfs ); }

			$items = array();

			foreach ( $mfs as $mf ) {
				$items[] = $mf;

				$items = array_merge( $items, self::flatten_microformat_properties( $mf ) );

				if ( empty( $mf['children'] ) ) {
					continue; }

				foreach ( $mf['children'] as $child ) {
					$items[] = $child;
					$items = array_merge( $items, self::flatten_microformat_properties( $child ) );
				}
			}

			return $items;
	}

	/**
	 *
	 * @param array $mfs
	 * @param $name
	 * @param bool  $flatten
	 * @return mixed
	 */
	public static function find_microformats_by_type(array $mfs, $name, $flatten = true) {
		return self::find_microformats_by_callable($mfs, function ($mf) use ($name) {
			return in_array( $name, $mf['type'] );
		}, $flatten);
	}


	/**
	 * Can determine if a microformat key with value exists in $mf. Returns true if so.
	 *
	 * @param array     $mfs
	 * @param $propName
	 * @param $propValue
	 * @param bool      $flatten
	 * @return mixed
	 * @see findMicroformatsByCallable()
	 */
	public static function find_microformats_by_property(array $mfs, $propName, $propValue, $flatten = true) {
		return find_microformats_by_callable($mfs, function ($mf) use ($propName, $propValue) {
			if ( ! hasProp( $mf, $propName ) ) {
				return false; }

			if ( in_array( $propValue, $mf['properties'][$propName] ) ) {
				return true; }

			return false;
		}, $flatten);
	}

	/**
	 * $callable should be a function or an exception will be thrown. $mfs can accept microformat collections.
	 * If $flatten is true then the result will be flattened.
	 *
	 * @param array    $mfs
	 * @param $callable
	 * @param bool     $flatten
	 * @return mixed
	 * @link http://php.net/manual/en/function.is-callable.php
	 * @see flattenMicroformats()
	 */
	public static function find_microformats_by_callable(array $mfs, $callable, $flatten = true) {
		if ( ! is_callable( $callable ) ) {
			throw new \InvalidArgumentException( '$callable must be callable' ); }

		if ( $flatten and (self::is_microformat( $mfs ) or self::is_microformat_collection( $mfs )) ) {
			$mfs = self::flatten_microformats( $mfs ); }

		return array_values( array_filter( $mfs, $callable ) );
	}

	/* Parses marked up HTML using MF2.
	*
	* @param string $content HTML marked up content.
	*/
	public static function mf2parse($content, $url) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		switch ( $host ) {
			case 'twitter.com':
			case 'www.twitter.com':
				$parsed = Mf2\Shim\parseTwitter( $content, $url );
				break;
			default:
				$parsed = Mf2\parse( $content, $url );
		}
		if ( ! is_array( $parsed ) ) {
			return array();
		}
		$count = count( $parsed['items'] );
		if ( 0 == $count ) {
			return array();
		}
		if ( 1 == $count ) {
			$item = $parsed['items'][0];
			if ( in_array( 'h-feed', $item['type'] ) ) {
				return array( 'type' => 'feed' );
			}
			if ( in_array( 'h-card', $item['type'] ) ) {
				return self::parse_hcard( $item, $parsed, $url );
			} elseif ( in_array( 'h-entry', $item['type'] ) || in_array( 'h-cite', $item['type'] ) ) {
				return self::parse_hentry( $item, $parsed );
			}
		}
		foreach ( $parsed['items'] as $item ) {
			if ( array_key_exists( 'url', $item['properties'] ) ) {
				$urls = $item['properties']['url'];
				if ( in_array( $url, $urls ) ) {
					if ( in_array( 'h-card', $item['type'] ) ) {
						return self::parse_hcard( $item, $parsed, $url );
					} elseif ( in_array( 'h-entry', $item['type'] ) || in_array( 'h-cite', $item['type'] ) ) {
						return self::parse_hentry( $item, $parsed );
					}
				}
			}
		}
	}

	private static function parse_hentry( $entry, $mf ) {
		// Array Values
		$properties = array( 'category', 'invitee', 'photo','video','audio','syndication','in-reply-to','like-of','repost-of','bookmark-of', 'tag-of', 'location', 'featured' );
		$data = self::get_prop_array( $entry, $properties );
		$data['type'] = 'entry';
		$data['published'] = self::get_published( $entry );
		$data['updated'] = self::get_updated( $entry );
		$properties = array( 'url', 'rsvp', 'featured', 'name' );
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $entry, $property );
		}
		$data['content'] = self::parse_html_value( $entry, 'content' );
		$data['summary'] = self::get_summary( $entry, $data['content'] );
		if ( isset( $data['name'] ) ) {
			$data['name'] = trim( preg_replace( '/https?:\/\/([^ ]+|$)/', '', $data['name'] ) );
		}
		if ( isset( $mf['rels']['syndication'] ) ) {
			if ( isset( $data['syndication'] ) ) {
				$data['syndication'] = array_unique( array_merge( $data['syndication'], $mf['rels']['syndication'] ) );
			} else {
				$data['syndication'] = $mf['rels']['syndication'];
			}
		}
		$author = self::find_author( $entry, $mf );
		if ( $author ) {
			if ( is_array( $author['type'] ) ) {
				$data['author'] = self::parse_hcard( $author, $mf );
			} else {
				$author = array_filter( $author );
				if ( ! isset( $author['name'] ) && isset( $author['url'] ) ) {
					$content = self::fetch( $author['url'] );
					$parsed = Mf2\parse( $content, $author['url'] );
					$hcard = self::find_microformats_by_type( $parsed, 'h-card' );
					if ( is_array( $hcard ) && ! empty( $hcard ) ) {
						$hcard = $hcard[0];
					}
					$data['author'] = self::parse_hcard( $hcard, $parsed, $author['url'] );
				} else {
					$data['author'] = $author;
				}
			}
		}
		$data = array_filter( $data );
		if ( array_key_exists( 'name', $data ) ) {
			if ( ! array_key_exists( 'summary', $data ) || ! array_key_exists( 'content', $data ) ) {
				unset( $data['name'] );
			}
		}
		if ( isset( $data['name'] ) && isset( $data['summary'] ) ) {
			if ( $data['name'] == $data[ 'summary' ] ) {
				unset( $data['name'] );
			}
		}
		return $data;
	}

	private static function parse_hcard( $hcard, $mf, $authorurl = false ) {
		// If there is a matching author URL, use that one
		$data = array(
			'type' => 'card',
			'name' => null,
			'url' => null,
			'photo' => null,
		);
		$properties = array( 'url','name','photo' );
		foreach ( $properties as $p ) {
			if ( 'url' == $p && $authorurl ) {
				// If there is a matching author URL, use that one
				$found = false;
				foreach ( $hcard['properties']['url'] as $url ) {
					if ( self::is_url( $url ) ) {
						if ( $url == $authorurl ) {
							$data['url'] = $url;
							$found = true;
						}
					}
				}
				if ( ! $found && self::is_url( $hcard['properties']['url'][0] ) ) {
					$data['url'] = $hcard['properties']['url'][0];
				}
			} else if ( ( $v = self::get_plaintext( $hcard, $p ) ) !== null ) {
				// Make sure the URL property is actually a URL
				if ( 'url' == $p || 'photo' == $p ) {
					if ( self::is_url( $v ) ) {
						$data[ $p ] = $v;
					}
				} else {
					$data[ $p ] = $v;
				}
			}
		}
		return array_filter( $data );
	}




}
