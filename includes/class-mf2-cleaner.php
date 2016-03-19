<?php

/*
  helpers for processing canonical microformats2 array structures.
 *	https://github.com/barnabywalters/php-mf-cleaner
 * Rewritten as a class for WordPress
*/

class mf2_cleaner {
	public static function hasNumericKeys(array $arr) {
		foreach ( $arr as $key => $val ) { if ( is_numeric( $key ) ) { return true; }
		}
		return false;
	}
	public static function isMicroformat($mf) {
		return (is_array( $mf ) and ! self::hasNumericKeys( $mf ) and ! empty( $mf['type'] ) and isset( $mf['properties'] ));
	}
	public static function isMicroformatCollection($mf) {
		return (is_array( $mf ) and isset( $mf['items'] ) and is_array( $mf['items'] ));
	}
	public static function isEmbeddedHtml($p) {
		return is_array( $p ) and ! self::hasNumericKeys( $p ) and isset( $p['value'] ) and isset( $p['html'] );
	}
	public static function hasProp(array $mf, $propName) {
		return ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] );
	}
	/** shortcut for getPlaintext, use getPlaintext from now on */
	public static function getProp(array $mf, $propName, $fallback = null) {
		return self::getPlaintext( $mf, $propName, $fallback );
	}
	public static function toPlaintext($v) {
		if ( self::isMicroformat( $v ) or self::isEmbeddedHtml( $v ) ) {
			return $v['value']; }
		return $v;
	}
	public static function getPlaintext(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return self::toPlaintext( current( $mf['properties'][$propName] ) );
		}
		return $fallback;
	}
	public static function getPlaintextArray(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return array_map( __NAMESPACE__ . '\toPlaintext', $mf['properties'][$propName] ); }
		return $fallback;
	}
	public static function toHtml($v) {
		if ( self::isEmbeddedHtml( $v ) ) {
			return $v['html']; } elseif ( self::isMicroformat( $v ) ) {
			return htmlspecialchars( $v['value'] ); }
			return htmlspecialchars( $v );
	}
	public static function getHtml(array $mf, $propName, $fallback = null) {
		if ( ! empty( $mf['properties'][$propName] ) and is_array( $mf['properties'][$propName] ) ) {
			return self::toHtml( current( $mf['properties'][$propName] ) ); }
		return $fallback;
	}
	/** @deprecated as not often used **/
	public static function getSummary(array $mf) {
		if ( hasProp( $mf, 'summary' ) ) {
			return getProp( $mf, 'summary' ); }
		if ( ! empty( $mf['properties']['content'] ) ) {
			return substr( strip_tags( getPlaintext( $mf, 'content' ) ), 0, 19 ) . '…'; }
	}
	public static function getPublished(array $mf, $ensureValid = false, $fallback = null) {
		return self::getDateTimeProperty( 'published', $mf, $ensureValid, $fallback );
	}
	public static function getUpdated(array $mf, $ensureValid = false, $fallback = null) {
		return self::getDateTimeProperty( 'updated', $mf, $ensureValid, $fallback );
	}
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
	public static function sameHostname($u1, $u2) {
		return parse_url( $u1, PHP_URL_HOST ) === parse_url( $u2, PHP_URL_HOST );
	}
	// TODO: maybe split some bits of this out into separate public static functions
	// TODO: this needs to be just part of an indiewebcamp.com/authorship algorithm, at the moment it tries to do too much
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
	public static function parseUrl($url) {
		$r = parse_url( $url );
		$r['pathname'] = empty( $r['path'] ) ? '/' : $r['path'];
		return $r;
	}
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
	public static function findMicroformatsByType(array $mfs, $name, $flatten = true) {
		return self::findMicroformatsByCallable($mfs, function ($mf) use ($name) {
			return in_array( $name, $mf['type'] );
		}, $flatten);
	}
	public static function findMicroformatsByProperty(array $mfs, $propName, $propValue, $flatten = true) {
		return findMicroformatsByCallable($mfs, function ($mf) use ($propName, $propValue) {
			if ( ! hasProp( $mf, $propName ) ) {
				return false; }

			if ( in_array( $propValue, $mf['properties'][$propName] ) ) {
				return true; }

			return false;
		}, $flatten);
	}
	public static function findMicroformatsByCallable(array $mfs, $callable, $flatten = true) {
		if ( ! is_callable( $callable ) ) {
			throw new \InvalidArgumentException( '$callable must be callable' ); }

		if ( $flatten and (self::isMicroformat( $mfs ) or self::isMicroformatCollection( $mfs )) ) {
			$mfs = self::flattenMicroformats( $mfs ); }

		return array_values( array_filter( $mfs, $callable ) );
	}

}
