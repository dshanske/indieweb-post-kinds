<?php
/**
 * Helpers for processing microformats2 array structures.
 * Derived from https://github.com/barnabywalters/php-mf-cleaner
 * and https://github.com/aaronpk/XRay/blob/master/lib/Formats/Mf2.php
 * and https://github.com/pfefferle/wordpress-semantic-linkbacks/blob/master/includes/class-linkbacks-mf2-handler.php
 **/

class Parse_This_MF2 {

	/**
	 * is this what type
	 *
	 * @param array $mf Parsed Microformats Array
	 * @param string $type Type
	 * @return bool
	 */
	public static function is_type( $mf, $type ) {
		return is_array( $mf ) && ! empty( $mf['type'] ) && is_array( $mf['type'] ) && in_array( $type, $mf['type'], true );
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
		$content     = $mf['properties'][ $property ][0];
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
		$data = array(
			'text' => $textcontent,
		);
		if ( $htmlcontent && $textcontent !== $htmlcontent ) {
			$data['html'] = $htmlcontent;
		}
		return $data;
	}

	/**
	 * Verifies if $mf is an array without numeric keys, and has a 'properties' key.
	 *
	 * @param $mf
	 * @return bool
	 */
	public static function is_microformat( $mf ) {
		return ( is_array( $mf ) && ! wp_is_numeric_array( $mf ) && ! empty( $mf['type'] ) && isset( $mf['properties'] ) );
	}


	/**
	 * Verifies if $mf has an 'items' key which is also an array, returns true.
	 *
	 * @param $mf
	 * @return bool
	 */
	public static function is_microformat_collection( $mf ) {
		return ( is_array( $mf ) && isset( $mf['items'] ) && is_array( $mf['items'] ) );
	}

	/**
	 * Verifies if $p is an array without numeric keys and has key 'value' and 'html' set.
	 *
	 * @param $p
	 * @return bool
	 */
	public static function is_embedded_html( $p ) {
		return is_array( $p ) && ! wp_is_numeric_array( $p ) && isset( $p['value'] ) && isset( $p['html'] );
	}

	/**
	 * Verifies if property named $propname is in array $mf.
	 *
	 * @param array    $mf
	 * @param $propname
	 * @return bool
	 */
	public static function has_prop( array $mf, $propname ) {
		return ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] );
	}


	/**
	 * Verifies if rel named $relname is in array $mf.
	 *
	 * @param array   $mf
	 * @param $relname
	 * @return bool
	 */
	public static function has_rel( array $mf, $relname ) {
		return ! empty( $mf['rels'][ $relname ] ) && is_array( $mf['rels'][ $relname ] );
	}


	/**
	 * shortcut for getPlaintext.
	 *
	 * @deprecated use getPlaintext from now on
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return mixed|null
	 */
	public static function get_prop( array $mf, $propname, $fallback = null ) {
		return self::get_plaintext( $mf, $propname, $fallback );
	}

	/**
	 * If $v is a microformat or embedded html, return $v['value']. Else return v.
	 *
	 * @param $v
	 * @return mixed
	 */
	public static function to_plaintext( $v ) {
		if ( self::is_microformat( $v ) || self::is_embedded_html( $v ) ) {
			return $v['value']; }
		return $v;
	}

	/**
	 * Returns plaintext of $propname with optional $fallback
	 *
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return mixed|null
	 * @link http://php.net/manual/en/function.current.php
	 */
	public static function get_plaintext( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return self::to_plaintext( current( $mf['properties'][ $propname ] ) );
		}
		return $fallback;
	}

	/**
	 * Converts $propname in $mf into array_map plaintext, or $fallback if not valid.
	 *
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return null
	 */
	public static function get_plaintext_array( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return array_map( array( 'Parse_Mf2', 'to_plaintext' ), $mf['properties'][ $propname ] ); }
		return $fallback;
	}

	/**
	 *  Return an array of properties, and may contain plaintext content
	 *
	 * @param array       $mf
	 * @param array       $properties
	 * @param null|string $fallback
	 * @return null|array
	 */
	public static function get_prop_array( array $mf, $properties, $fallback = null ) {
		if ( ! self::is_microformat( $mf ) ) {
			return array();
		}

		$data = array();
		foreach ( $properties as $p ) {
			if ( array_key_exists( $p, $mf['properties'] ) ) {
				foreach ( $mf['properties'][ $p ] as $v ) {
					if ( is_string( $v ) ) {
						$data[ $p ] = $v;
					} elseif ( self::is_microformat( $v ) ) {
						if ( self::is_type( $v, 'h-card' ) ) {
							$data[ $p ] = self::parse_hcard( $v, $mf );
						} elseif ( self::is_type( $v, 'h-adr' ) ) {
							$data[ $p ] = self::parse_hadr( $v, $mf );
						} elseif ( self::is_type( $v, 'h-cite' ) ) {
							$data[ $p ] = self::parse_hcite( $v, $mf );
						} else {
							$u = self::get_plaintext( $v, 'url' );
							if ( ( $u ) && wp_http_validate_url( $u ) ) {
								$data[ $p ] = $u;
							}
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
	public static function to_html( $v ) {
		if ( self::is_embedded_html( $v ) ) {
			return $v['html']; } elseif ( self::is_microformat( $v ) ) {
			return htmlspecialchars( $v['value'] ); }
			return htmlspecialchars( $v );
	}

	/**
	 * Gets HTML of $propname or if not, $fallback
	 *
	 * @param array       $mf
	 * @param $propname
	 * @param null|string $fallback
	 * @return mixed|null
	 */
	public static function get_html( array $mf, $propname, $fallback = null ) {
		if ( ! empty( $mf['properties'][ $propname ] ) && is_array( $mf['properties'][ $propname ] ) ) {
			return self::to_html( current( $mf['properties'][ $propname ] ) ); }
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
	 * @param bool        $ensurevalid
	 * @param null|string $fallback optional result if date not available
	 * @return mixed|null
	 */
	public static function get_published( array $mf, $ensurevalid = false, $fallback = null ) {
		return self::get_datetime_property( 'published', $mf, $ensurevalid, $fallback );
	}

	/**
	 * Gets the date updated of $mf array.
	 *
	 * @param array $mf
	 * @param bool  $ensurevalid
	 * @param null  $fallback
	 * @return mixed|null
	 */
	public static function get_updated( array $mf, $ensurevalid = false, $fallback = null ) {
		return self::get_datetime_property( 'updated', $mf, $ensurevalid, $fallback );
	}

	/**
	 * Gets the DateTime properties including published or updated, depending on params.
	 *
	 * @param $name string updated or published
	 * @param array                            $mf
	 * @param bool                             $ensurevalid
	 * @param null|string                      $fallback
	 * @return mixed|null
	 */
	public static function get_datetime_property( $name, array $mf, $ensurevalid = false, $fallback = null ) {
		$compliment = 'published' === $name ? 'updated' : 'published';
		if ( self::has_prop( $mf, $name ) ) {
			$return = self::get_prop( $mf, $name ); } elseif ( self::has_prop( $mf, $compliment ) ) {
			$return = self::get_prop( $mf, $compliment );
			} else {
				return $fallback; }
			if ( ! $ensurevalid ) {
				return $return; } else {
				try {
					new DateTime( $return );
					return $return;
				} catch ( Exception $e ) {
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
	public static function same_hostname( $u1, $u2 ) {
		return wp_parse_url( $u1, PHP_URL_HOST ) === wp_parse_url( $u2, PHP_URL_HOST );
	}


	/**
	 * Large function for fishing out author of $mf from various possible array elements.
	 *
	 * @param array      $mf
	 * @param array|null $context
	 * @param null       $url
	 * @param bool       $matchname
	 * @param bool       $matchhostname
	 * @return mixed|null
	 * @todo: this needs to be just part of an indiewebcamp.com/authorship algorithm, at the moment it tries to do too much
	 * @todo: maybe split some bits of this out into separate functions
	 */
	public static function get_author( array $mf, array $context = null, $url = null, $matchname = true, $matchhostname = true ) {
		$entryauthor = null;

		if ( null === $url && self::has_prop( $mf, 'url' ) ) {
			$url = self::get_prop( $mf, 'url' ); }

		if ( self::has_prop( $mf, 'author' ) && self::is_microformat( current( $mf['properties']['author'] ) ) ) {
			$entryauthor = current( $mf['properties']['author'] ); } elseif ( self::has_prop( $mf, 'reviewer' ) && self::is_microformat( current( $mf['properties']['author'] ) ) ) {
			$entryauthor = current( $mf['properties']['reviewer'] ); } elseif ( self::has_prop( $mf, 'author' ) ) {
				$entryauthor = self::get_plaintext( $mf, 'author' ); }

			// If we have no context that’s the best we can do
			if ( null === $context ) {
				return $entryauthor; }

			// Whatever happens after this we’ll need these
			$flattenedmf = self::flatten_microformats( $context );
			$hcards      = self::find_microformats_by_type( $flattenedmf, 'h-card', false );
			if ( is_string( $entryauthor ) ) {
				// look through all page h-cards for one with this URL
				$authorhcards = self::find_microformats_by_property( $hcards, 'url', $entryauthor, false );
				if ( ! empty( $authorhcards ) ) {
					$entryauthor = current( $authorhcards ); }
			}
			if ( is_string( $entryauthor ) && $matchname ) {
				// look through all page h-cards for one with this name
				$authorhcards = self::find_microformats_by_property( $hcards, 'name', $entryauthor, false );

				if ( ! empty( $authorhcards ) ) {
					$entryauthor = current( $authorhcards ); }
			}

			if ( null !== $entryauthor ) {
				return $entryauthor; }

			// look for page-wide rel-author, h-card with that
			if ( ! empty( $context['rels'] ) && ! empty( $context['rels']['author'] ) ) {
				// Grab first href with rel=author
				$relauthorhref = current( $context['rels']['author'] );

				$relauthorhcards = self::find_microformats_by_property( $hcards, 'url', $relauthorhref );

				if ( ! empty( $relauthorhcards ) ) {
					return current( $relauthorhcards ); }
			}
			// look for h-card with same hostname as $url if given
			if ( null !== $url && $matchhostname ) {
				$samehostnamehcards = self::find_microformats_by_callable(
					$hcards,
					function ( $mf ) use ( $url ) {
						if ( ! has_prop( $mf, 'url' ) ) {
							return false; }
						foreach ( $mf['properties']['url'] as $u ) {
							if ( same_hostname( $url, $u ) ) {
								return true; }
						}
					},
					false
				);
				if ( ! empty( $samehostnamehcards ) ) {
					return current( $samehostnamehcards ); }
			}
			// Without fetching, this is the best we can do. Return the found string value, or null.
			return empty( $relauthorhref )
			? null
			: $relauthorhref;
	}

	public static function find_author( $item, $mf2 ) {
		$author = array(
			'type'  => 'card',
			'name'  => null,
			'url'   => null,
			'photo' => null,
		);
		// Author Discovery
		// http://indieweb,org/authorship
		$authorpage = false;
		if ( array_key_exists( 'author', $item['properties'] ) ) {
			// Check if any of the values of the author property are an h-card
			foreach ( $item['properties']['author'] as $a ) {
				if ( self::is_type( $a, 'h-card' ) ) {
					// 5.1 "if it has an h-card, use it, exit."
					return $a;
				} elseif ( is_string( $a ) ) {
					if ( wp_http_validate_url( $a ) ) {
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
	public static function parse_url( $url ) {
		$r             = wp_parse_url( $url );
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
	public static function urls_match( $url1, $url2 ) {
		$u1 = wp_parse_url( $url1 );
		$u2 = wp_parse_url( $url2 );
		foreach ( array_merge( array_keys( $u1 ), array_keys( $u2 ) ) as $component ) {
			if ( ! array_key_exists( $component, $u1 ) || ! array_key_exists( $component, $u1 ) ) {
				return false;
			}
			if ( $u1[ $component ] !== $u2[ $component ] ) {
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
	public static function get_representative_hcard( array $mfs, $url ) {
		$hcardsmatchinguidurlpageurl = find_microformats_by_callable(
			$mfs,
			function ( $hcard ) use ( $url ) {
				return has_prop( $hcard, 'uid' ) && has_prop( $hcard, 'url' )
				&& urls_match( get_plaintext( $hcard, 'uid' ), $url )
				&& count(
					array_filter(
						$hcard['properties']['url'],
						function ( $u ) use ( $url ) {
							return urls_match( $u, $url );
						}
					)
				) > 0;
			}
		);
		if ( ! empty( $hcardsmatchinguidurlpageurl ) ) {
			return $hcardsmatchinguidurlpageurl[0]; }
		if ( ! empty( $mfs['rels']['me'] ) ) {
			$hcardsmatchingurlrelme = self::find_microformats_by_callable(
				$mfs,
				function ( $hcard ) use ( $mfs ) {
					if ( hasProp( $hcard, 'url' ) ) {
						foreach ( $mfs['rels']['me'] as $relurl ) {
							foreach ( $hcard['properties']['url'] as $url ) {
								if ( urlsMatch( $url, $relurl ) ) {
									return true;
								}
							}
						}
					}
					return false;
				}
			);
			if ( ! empty( $hcardsmatchingurlrelme ) ) {
				return $hcardsmatchingurlrelme[0]; }
		}
		$hcardsmatchingurlpageurl = find_microformats_by_callable(
			$mfs,
			function ( $hcard ) use ( $url ) {
				return has_prop( $hcard, 'url' )
				&& count(
					array_filter(
						$hcard['properties']['url'],
						function ( $u ) use ( $url ) {
							return urls_match( $u, $url );
						}
					)
				) > 0;
			}
		);
		if ( count( $hcardsmatchingurlpageurl ) === 1 ) {
			return $hcardsmatchingurlpageurl[0]; }
		// Otherwise, no representative h-card could be found.
		return null;
	}

	/**
	 * Flattens microformats. Can intake multiple Microformats including possible MicroformatCollection.
	 *
	 * @param array $mfs
	 * @return array
	 */
	public static function flatten_microformat_properties( array $mf ) {
		$items = array();

		if ( ! self::is_microformat( $mf ) ) {
			return $items; }

		foreach ( $mf['properties'] as $proparray ) {
			foreach ( $proparray as $prop ) {
				if ( self::is_microformat( $prop ) ) {
					$items[] = $prop;
					$items   = array_merge( $items, self::flatten_microformat_properties( $prop ) );
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
	public static function flatten_microformats( array $mfs ) {
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
					$items   = array_merge( $items, self::flatten_microformat_properties( $child ) );
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
	public static function find_microformats_by_type( array $mfs, $name, $flatten = true ) {
		return self::find_microformats_by_callable(
			$mfs,
			function ( $mf ) use ( $name ) {
				return in_array( $name, $mf['type'], true );
			},
			$flatten
		);
	}


	/**
	 * Can determine if a microformat key with value exists in $mf. Returns true if so.
	 *
	 * @param array     $mfs
	 * @param $propname
	 * @param $propvalue
	 * @param bool      $flatten
	 * @return mixed
	 * @see findMicroformatsByCallable()
	 */
	public static function find_microformats_by_property( array $mfs, $propname, $propvalue, $flatten = true ) {
		return find_microformats_by_callable(
			$mfs,
			function ( $mf ) use ( $propname, $propvalue ) {
				if ( ! hasProp( $mf, $propname ) ) {
					return false; }

				if ( in_array( $propvalue, $mf['properties'][ $propname ], true ) ) {
					return true; }

				return false;
			},
			$flatten
		);
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
	public static function find_microformats_by_callable( array $mfs, $callable, $flatten = true ) {
		if ( ! is_callable( $callable ) ) {
			throw new \InvalidArgumentException( '$callable must be callable' ); }

		if ( $flatten && ( self::is_microformat( $mfs ) || self::is_microformat_collection( $mfs ) ) ) {
			$mfs = self::flatten_microformats( $mfs ); }

		return array_values( array_filter( $mfs, $callable ) );
	}

	/*
	 * Parse MF2 into JF2
	 *
	 * @param string|DOMDocument|array $input HTML marked up content, HTML in DOMDocument, or array of already parsed MF2 JSON
	 */
	public static function parse( $input, $url, $alternate = true ) {
		if ( is_string( $input ) || is_a( $input, 'DOMDocument' ) ) {
			$input = Mf2\parse( $input, $url );
			if ( $alternate ) {
				// Check for rel-alternate jf2 or mf2 feed
				if ( isset( $input['rel-urls'] ) ) {
					foreach ( $input['rel-urls'] as $rel => $info ) {
						if ( isset( $info['rels'] ) && in_array( 'alternate', $info['rels'], true ) ) {
							if ( isset( $info['type'] ) ) {
								if ( 'application/jf2+json' === $info['type'] ) {
									$parse = new Parse_This( $rel );
									$parse->fetch();
									return $parse->get();
								}
								if ( 'application/mf2+json' === $info['type'] ) {
									$parse = new Parse_This( $rel );
									$parse->fetch();
									$input = $parse->get( 'content' );
									break;
								}
							}
						}
					}
				}
			}
		}
		if ( ! is_array( $input ) ) {
			return array();
		}

		$count = count( $input['items'] );
		if ( 0 === $count ) {
			return array();
		}
		if ( 1 === $count ) {
			$item = $input['items'][0];
			if ( in_array( 'h-feed', $item['type'], true ) ) {
				return parse_hfeed( $item, $input );
			} elseif ( in_array( 'h-card', $item['type'], true ) ) {
				return self::parse_hcard( $item, $input, $url );
			} elseif ( in_array( 'h-entry', $item['type'], true ) || in_array( 'h-cite', $item['type'], true ) ) {
				return self::parse_hentry( $item, $input );
			} elseif ( in_array( 'h-event', $item['type'], true ) ) {
				return self::parse_hevent( $item, $input );
			} elseif ( in_array( 'h-review', $item['type'], true ) ) {
				return self::parse_hreview( $item, $input );
			} elseif ( in_array( 'h-recipe', $item['type'], true ) ) {
				return self::parse_hrecipe( $item, $input );
			} elseif ( in_array( 'h-listing', $item['type'], true ) ) {
				return self::parse_hlisting( $item, $input );
			} elseif ( in_array( 'h-product', $item['type'], true ) ) {
				return self::parse_hproduct( $item, $input );
			} elseif ( in_array( 'h-resume', $item['type'], true ) ) {
				return self::parse_hresume( $item, $input );
			} elseif ( in_array( 'h-item', $item['type'], true ) ) {
				return self::parse_hitem( $item, $input );
			}
		}

		foreach ( $input['items'] as $item ) {
			if ( array_key_exists( 'url', $item['properties'] ) ) {
				$urls = $item['properties']['url'];
				if ( in_array( $url, $urls, true ) ) {
					if ( in_array( 'h-card', $item['type'], true ) ) {
						return self::parse_hcard( $item, $input, $url );
					} elseif ( in_array( 'h-entry', $item['type'], true ) || in_array( 'h-cite', $item['type'], true ) ) {
						return self::parse_hentry( $item, $input );
					}
				}
			}
		}
		// No matching URLs so assume the first h-entry
		foreach ( $input['items'] as $item ) {
			if ( in_array( 'h-feed', $item['type'], true ) ) {
				if ( in_array( 'children', $item, true ) ) {
					return array(
						'type' => 'feed',
					);
				}
			}
			if ( in_array( 'h-entry', $item['type'], true ) || in_array( 'h-cite', $item['type'], true ) ) {
				return self::parse_hentry( $item, $input );
			}
		}

		return array();
	}

	public static function parse_hfeed( $entry, $mf ) {
		$data = array(
			'type'  => 'feed',
			'items' => array(),
		);
		return array_filter( $data );

	}

	public static function parse_hcite( $entry, $mf ) {
		$data         = self::get_prop_array( $entry, array_keys( $entry['properties'] ) );
		$data['type'] = 'cite';
		return $data;
	}

	public static function parse_h( $entry, $mf ) {
		$data              = array();
		$data['name']      = self::get_plaintext( $entry, 'name' );
		$data['published'] = self::get_published( $entry );
		$data['updated']   = self::get_updated( $entry );
		$data['url']       = self::get_plaintext( $entry, 'url' );
		$author            = self::find_author( $entry, $mf );
		if ( $author ) {
			if ( is_array( $author['type'] ) ) {
				$data['author'] = self::parse_hcard( $author, $mf );
			} else {
				$data['author'] = $author;
			}
		}
		$data['content'] = self::parse_html_value( $entry, 'content' );
		$data['summary'] = self::get_summary( $entry, $data['content'] );

		if ( isset( $mf['rels']['syndication'] ) ) {
			if ( isset( $data['syndication'] ) ) {
				if ( is_string( $data['syndication'] ) ) {
					$data['syndication'] = array( $data['syndication'] );
				}
				$data['syndication'] = array_unique( array_merge( $data['syndication'], $mf['rels']['syndication'] ) );
			} else {
				$data['syndication'] = $mf['rels']['syndication'];
			}
			if ( 1 === count( $data['syndication'] ) ) {
				$data['syndication'] = array_pop( $data['syndication'] );
			}
		}
		return array_filter( $data );
	}

	public static function parse_hentry( $entry, $mf ) {
		// Array Values
		$properties   = array(
			'checkin',
			'category',
			'invitee',
			'photo',
			'video',
			'audio',
			'syndication',
			'in-reply-to',
			'like-of',
			'repost-of',
			'bookmark-of',
			'favorite-of',
			'listen-of',
			'quotation-of',
			'watch-of',
			'read-of',
			'play-of',
			'jam-of',
			'itinerary',
			'tag-of',
			'location',
			'checked-in-by',
		);
		$data         = self::get_prop_array( $entry, $properties );
		$data['type'] = 'entry';
		$properties   = array( 'url', 'weather', 'temperature', 'rsvp', 'featured', 'name', 'swarm-coins' );
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $entry, $property );
		}
		$data              = array_filter( $data );
		$data              = array_merge( $data, self::parse_h( $entry, $mf ) );
		$data['post-type'] = self::post_type_discovery( $entry );
		return $data;
	}

	public static function parse_hcard( $hcard, $mf, $authorurl = false ) {
		// If there is a matching author URL, use that one
		$data = array(
			'type'  => 'card',
			'name'  => null,
			'url'   => null,
			'photo' => null,
		);
		// Possible Nested Values
		$properties = array( 'org', 'location' );
		$data       = array_merge( $data, self::get_prop_array( $hcard, $properties ) );
		// Single Values
		$properties = array( 'url', 'name', 'photo', 'latitude', 'longitude', 'note', 'uid', 'bday', 'role', 'locality', 'region', 'country' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $hcard, $p );
			if ( 'url' === $p && $authorurl ) {
				// If there is a matching author URL, use that one
				$found = false;
				foreach ( $hcard['properties']['url'] as $url ) {
					if ( wp_http_validate_url( $url ) ) {
						if ( $url === $authorurl ) {
							$data['url'] = $url;
							$found       = true;
						}
					}
				}
				if ( ! $found && wp_http_validate_url( $hcard['properties']['url'][0] ) ) {
					$data['url'] = $hcard['properties']['url'][0];
				}
			} elseif ( null !== $v ) {
				// Make sure the URL property is actually a URL
				if ( 'url' === $p || 'photo' === $p ) {
					if ( wp_http_validate_url( $v ) ) {
						$data[ $p ] = $v;
					}
				} else {
					$data[ $p ] = $v;
				}
			}
		}
		return array_filter( $data );
	}

	public static function parse_hevent( $entry, $mf ) {
		$data       = array(
			'type' => 'event',
			'name' => null,
			'url'  => null,
		);
		$data       = array_merge( $data, self::parse_h( $entry, $mf ) );
		$properties = array( 'location', 'start', 'end', 'photo' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		return array_filter( $data );
	}

	public static function parse_hreview( $entry, $mf ) {
		$data       = array(
			'type' => 'review',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array( 'summary', 'published', 'rating', 'best', 'worst' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf ) );
		return array_filter( $data );
	}


	public static function parse_hproduct( $entry, $mf ) {
		$data       = array(
			'type' => 'product',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'brand', 'photo', 'audio', 'video' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array( 'identifier', 'price', 'description' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf ) );
		return array_filter( $data );
	}


	public static function parse_hresume( $entry, $mf ) {
		$data       = array(
			'type' => 'resume',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf ) );
		return array_filter( $data );
	}

	public static function parse_hlisting( $entry, $mf ) {
		$data       = array(
			'type' => 'listing',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf ) );
		return array_filter( $data );
	}

	public static function parse_hrecipe( $entry, $mf ) {
		$data       = array(
			'type' => 'recipe',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf ) );
		return array_filter( $data );
	}

	public static function parse_hitem( $entry, $mf ) {
		$data       = array(
			'type' => 'item',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $entry, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf ) );
		return array_filter( $data );
	}

	public static function parse_hadr( $hadr, $mf ) {
		$data       = array(
			'type' => 'adr',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'url', 'name', 'photo', 'location', 'latitude', 'longitude', 'note', 'uid', 'locality', 'region', 'country' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $hadr, $p );
			if ( null !== $v ) {
				// Make sure the URL property is actually a URL
				if ( 'url' === $p || 'photo' === $p ) {
					if ( wp_http_validate_url( $v ) ) {
						$data[ $p ] = $v;
					}
				} else {
					$data[ $p ] = $v;
				}
			}
		}
		return array_filter( $data );
	}

	public static function post_type_discovery( $mf ) {
		if ( ! self::is_microformat( $mf ) ) {
			return false;
		}
		$properties = array_keys( $mf['properties'] );
		if ( self::is_type( $mf, 'h-entry' ) ) {
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
				'ate'       => array( 'eat', 'p3k-food' ),
				'drink'     => array( 'drank' ),
				'reply'     => array( 'in-reply-to' ),
				'video'     => array( 'video' ),
				'photo'     => array( 'photo' ),
				'audio'     => array( 'audio' ),
			);
			foreach ( $map as $key => $value ) {
				$diff = array_intersect( $properties, $value );
				if ( ! empty( $diff ) ) {
					return $key;
				}
			}
			$name = static::get_plaintext( $mf, 'name' );
			if ( ! empty( $name ) ) {
				$name    = trim( $name );
				$content = trim( static::get_plaintext( $mf, 'content' ) );
				if ( 0 !== strpos( $content, $name ) ) {
					return 'article';
				}
			}
			return 'note';
		}
		return '';
	}

}
