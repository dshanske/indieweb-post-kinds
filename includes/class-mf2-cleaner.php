<?php

/*
  helpers for processing canonical microformats2 array structures.
 *	https://github.com/barnabywalters/php-mf-cleaner
 * Rewritten as a class for WordPress
*/

class mf2_cleaner {

	/**
	 * Iterates over array keys, returns true if has numeric keys.
	 *
	 * @param array $arr
	 * @return bool
	 */
	public static function hasNumericKeys(array $arr) {
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
	public static function isMicroformat($mf) {
		return (is_array( $mf ) and ! self::hasNumericKeys( $mf ) and ! empty( $mf['type'] ) and isset( $mf['properties'] ));
	}


	/**
	 * Verifies if $mf has an 'items' key which is also an array, returns true.
	 *
	 * @param $mf
	 * @return bool
	 */
	public static function isMicroformatCollection($mf) {
		return (is_array( $mf ) and isset( $mf['items'] ) and is_array( $mf['items'] ));
	}

	/**
	 * Verifies if $p is an array without numeric keys and has key 'value' and 'html' set.
	 *
	 * @param $p
	 * @return bool
	 */
	public static function isEmbeddedHtml($p) {
		return is_array( $p ) and ! self::hasNumericKeys( $p ) and isset( $p['value'] ) and isset( $p['html'] );
	}

	/**
	 * Verifies if property named $propName is in array $mf.
	 *
	 * @param array    $mf
	 * @param $propName
	 * @return bool
	 */
	public static function hasProp(array $mf, $propName) {
		return ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] );
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
	public static function getProp(array $mf, $propName, $fallback = null) {
		return self::getPlaintext( $mf, $propName, $fallback );
	}

	/**
	 * If $v is a microformat or embedded html, return $v['value']. Else return v.
	 *
	 * @param $v
	 * @return mixed
	 */
	public static function toPlaintext($v) {
		if ( self::isMicroformat( $v ) or self::isEmbeddedHtml( $v ) ) {
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
	public static function getPlaintext(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return self::toPlaintext( current( $mf['properties'][$propName] ) );
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
	public static function getPlaintextArray(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return array_map( __NAMESPACE__ . '\toPlaintext', $mf['properties'][$propName] ); }
		return $fallback;
	}


	/**
	 * Returns ['html'] element of $v, or ['value'] or just $v, in order of availablility.
	 *
	 * @param $v
	 * @return mixed
	 */
	public static function toHtml($v) {
		if ( self::isEmbeddedHtml( $v ) ) {
			return $v['html']; } elseif ( self::isMicroformat( $v ) ) {
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
	public static function getHtml(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return self::toHtml( current( $mf['properties'][$propName] ) ); }
		return $fallback;
	}



	/**
	 * Returns 'summary' element of $mf or a truncated Plaintext of $mf['properties']['content'] with 19 chars and ellipsis.
	 *
	 * @deprecated as not often used
	 * @param array $mf
	 * @return mixed|null|string
	 */
	public static function getSummary(array $mf) {
		if ( hasProp( $mf, 'summary' ) ) {
			return getProp( $mf, 'summary' ); }
		if ( ! empty( $mf['properties']['content'] ) ) {
			return substr( strip_tags( getPlaintext( $mf, 'content' ) ), 0, 19 ) . '…'; }
	}

	/**
	 * Gets the date published of $mf array.
	 *
	 * @param array       $mf
	 * @param bool        $ensureValid
	 * @param null|string $fallback optional result if date not available
	 * @return mixed|null
	 */
	public static function getPublished(array $mf, $ensureValid = false, $fallback = null) {
		return self::getDateTimeProperty( 'published', $mf, $ensureValid, $fallback );
	}

	/**
	 * Gets the date updated of $mf array.
	 *
	 * @param array $mf
	 * @param bool  $ensureValid
	 * @param null  $fallback
	 * @return mixed|null
	 */
	public static function getUpdated(array $mf, $ensureValid = false, $fallback = null) {
		return self::getDateTimeProperty( 'updated', $mf, $ensureValid, $fallback );
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
	public static function getDateTimeProperty($name, array $mf, $ensureValid = false, $fallback = null) {
		$compliment = 'published' === $name ? 'updated' : 'published';
		if ( self::hasProp( $mf, $name ) ) {
			$return = self::getProp( $mf, $name ); } elseif ( self::hasProp( $mf, $compliment ) ) {
			$return = self::getProp( $mf, $compliment );
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
	public static function sameHostname($u1, $u2) {
		return parse_url( $u1, PHP_URL_HOST ) === parse_url( $u2, PHP_URL_HOST );
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
	public static function getAuthor(array $mf, array $context = null, $url = null, $matchName = true, $matchHostname = true) {
		$entryAuthor = null;

		if ( null === $url and self::hasProp( $mf, 'url' ) ) {
			$url = self::getProp( $mf, 'url' ); }

		if ( self::hasProp( $mf, 'author' ) and self::isMicroformat( current( $mf['properties']['author'] ) ) ) {
			$entryAuthor = current( $mf['properties']['author'] ); } elseif ( self::hasProp( $mf, 'reviewer' ) and self::isMicroformat( current( $mf['properties']['author'] ) ) ) {
			$entryAuthor = current( $mf['properties']['reviewer'] ); } elseif ( self::hasProp( $mf, 'author' ) ) {
				$entryAuthor = self::getPlaintext( $mf, 'author' ); }

			// If we have no context that’s the best we can do
			if ( null === $context ) {
				return $entryAuthor; }

			// Whatever happens after this we’ll need these
			$flattenedMf = self::flattenMicroformats( $context );
			$hCards = self::findMicroformatsByType( $flattenedMf, 'h-card', false );
			if ( is_string( $entryAuthor ) ) {
				// look through all page h-cards for one with this URL
				$authorHCards = self::findMicroformatsByProperty( $hCards, 'url', $entryAuthor, false );
				if ( ! empty( $authorHCards ) ) {
					$entryAuthor = current( $authorHCards ); }
			}
			if ( is_string( $entryAuthor ) and $matchName ) {
				// look through all page h-cards for one with this name
				$authorHCards = self::findMicroformatsByProperty( $hCards, 'name', $entryAuthor, false );

				if ( ! empty( $authorHCards ) ) {
					$entryAuthor = current( $authorHCards ); }
			}

			if ( null !== $entryAuthor ) {
				return $entryAuthor; }

			// look for page-wide rel-author, h-card with that
			if ( ! empty( $context['rels'] ) and ! empty( $context['rels']['author'] ) ) {
				// Grab first href with rel=author
				$relAuthorHref = current( $context['rels']['author'] );

				$relAuthorHCards = self::findMicroformatsByProperty( $hCards, 'url', $relAuthorHref );

				if ( ! empty( $relAuthorHCards ) ) {
					return current( $relAuthorHCards ); }
			}
			// look for h-card with same hostname as $url if given
			if ( null !== $url and $matchHostname ) {
				$sameHostnameHCards = self::findMicroformatsByCallable($hCards, function ($mf) use ($url) {
					if ( ! hasProp( $mf, 'url' ) ) {
						return false; }
					foreach ( $mf['properties']['url'] as $u ) {
						if ( sameHostname( $url, $u ) ) {
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

	/**
	 * Returns array per parse_url standard with pathname key added.
	 *
	 * @param $url
	 * @return mixed
	 * @link http://php.net/manual/en/function.parse-url.php
	 */
	public static function parseUrl($url) {
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
	public static function urlsMatch($url1, $url2) {
		$u1 = parseUrl( $url1 );
		$u2 = parseUrl( $url2 );
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
	public static function getRepresentativeHCard(array $mfs, $url) {
		$hCardsMatchingUidUrlPageUrl = findMicroformatsByCallable($mfs, function ($hCard) use ($url) {
			return hasProp( $hCard, 'uid' ) and hasProp( $hCard, 'url' )
			and urlsMatch( getPlaintext( $hCard, 'uid' ), $url )
			and count(array_filter($hCard['properties']['url'], function ($u) use ($url) {
				return urlsMatch( $u, $url );
			})) > 0;
		});
		if ( ! empty( $hCardsMatchingUidUrlPageUrl ) ) { return $hCardsMatchingUidUrlPageUrl[0]; }
		if ( ! empty( $mfs['rels']['me'] ) ) {
			$hCardsMatchingUrlRelMe = self::findMicroformatsByCallable($mfs, function ($hCard) use ($mfs) {
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
		$hCardsMatchingUrlPageUrl = findMicroformatsByCallable($mfs, function ($hCard) use ($url) {
			return hasProp( $hCard, 'url' )
			and count(array_filter($hCard['properties']['url'], function ($u) use ($url) {
				return urlsMatch( $u, $url );
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
	public static function flattenMicroformatProperties(array $mf) {
		$items = array();

		if ( ! self::isMicroformat( $mf ) ) {
			return $items; }

		foreach ( $mf['properties'] as $propArray ) {
			foreach ( $propArray as $prop ) {
				if ( self::isMicroformat( $prop ) ) {
					$items[] = $prop;
					$items = array_merge( $items, self::flattenMicroformatProperties( $prop ) );
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
	public static function flattenMicroformats(array $mfs) {
		if ( self::isMicroformatCollection( $mfs ) ) {
			$mfs = $mfs['items']; } elseif ( self::isMicroformat( $mfs ) ) {
			$mfs = array( $mfs ); }

			$items = array();

			foreach ( $mfs as $mf ) {
				$items[] = $mf;

				$items = array_merge( $items, self::flattenMicroformatProperties( $mf ) );

				if ( empty( $mf['children'] ) ) {
					continue; }

				foreach ( $mf['children'] as $child ) {
					$items[] = $child;
					$items = array_merge( $items, self::flattenMicroformatProperties( $child ) );
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
	public static function findMicroformatsByType(array $mfs, $name, $flatten = true) {
		return self::findMicroformatsByCallable($mfs, function ($mf) use ($name) {
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
	public static function findMicroformatsByProperty(array $mfs, $propName, $propValue, $flatten = true) {
		return findMicroformatsByCallable($mfs, function ($mf) use ($propName, $propValue) {
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
	public static function findMicroformatsByCallable(array $mfs, $callable, $flatten = true) {
		if ( ! is_callable( $callable ) ) {
			throw new \InvalidArgumentException( '$callable must be callable' ); }

		if ( $flatten and (self::isMicroformat( $mfs ) or self::isMicroformatCollection( $mfs )) ) {
			$mfs = self::flattenMicroformats( $mfs ); }

		return array_values( array_filter( $mfs, $callable ) );
	}

}
