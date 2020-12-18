<?php
/**
 * Helpers for processing microformats2 array structures.
 * Derived from https://github.com/barnabywalters/php-mf-cleaner
 * and https://github.com/aaronpk/XRay/blob/master/lib/Formats/Mf2.php
 * and https://github.com/pfefferle/wordpress-semantic-linkbacks/blob/master/includes/class-linkbacks-mf2-handler.php
 **/

class Parse_This_MF2 extends Parse_This_MF2_Utils {

	public static function find_hfeed( $input, $url ) {
		if ( ! class_exists( 'Mf2\Parser' ) ) {
					require_once plugin_dir_path( __DIR__ ) . 'lib/mf2/Parser.php';
		}
		if ( is_string( $input ) || is_a( $input, 'DOMDocument' ) ) {
			$parser = new Mf2\Parser( $input, $url );
			$input  = $parser->parse();
		}

		$feeds = array();

		if ( array_key_exists( 'items', $input ) ) {
			foreach ( $input['items'] as $item ) {
				if ( self::is_type( $item, 'h-feed' ) ) {
					$feeds[] = $item;
				} elseif ( self::has_children( $item ) ) {
					foreach ( $item['children'] as $child ) {
						if ( self::is_type( $child, 'h-feed' ) ) {
							$feeds[] = $child;
						}
					}
				}
			}
			if ( empty( $feeds ) && 1 <= count( $input['items'] ) ) {
				$feeds[] = array(
					'type'       => 'h-feed',
					'properties' => array(
						'url' => array( $url ),
					),
				);
			}
		}
		foreach ( $feeds as $key => $feed ) {
			if ( ! array_key_exists( 'url', $feed['properties'] ) ) {
				if ( array_key_exists( 'id', $feed ) ) {
					$feeds[ $key ]['properties']['url'] = array( $url . '#' . $feed['id'] );
				} else {
					$feeds[ $key ]['properties']['url'] = array( $url );
				}
			}
		}
		return $feeds;
	}

	/**
	 * Large function for fishing out author of $mf from various possible array elements.
	 *
	 * @param array   $item Individual item
	 * @param array   $mf2 Overall Microformats array
	 * @param boolean $follow Follow author arrays
	 */
	public static function find_author( $item, $mf2, $follow = false ) {
		// Author Discovery
		// http://indieweb,org/authorship
		$authorpage = false;
		if ( self::has_prop( $item, 'author' ) ) {
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
						$author = self::get_plaintext( $item, 'author' );
					}
				} else {
					// This case is only hit when the author property is an mf2 object that is not an h-card
					$author = self::get_plaintext( $item, 'author' );
				}
				if ( ! $authorpage ) {
					return array(
						'type'       => array( 'h-card' ),
						'properties' => array(
							'name' => array( $author ),
						),
					);
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
			if ( $follow && ! self::urls_match( $authorpage, self::get_plaintext( $mf2, 'url' ) ) ) {
				$parse = new Parse_This( $authorpage );
				$parse->fetch();
				$parse->parse();
				return $parse->get();
			} else {
				return array(
					'type'       => array( 'h-card' ),
					'properties' => array(
						'url' => array( $authorpage ),
					),
				);
			}
		}
	}

	/**
	 *  Return an array of properties, and may contain plaintext content
	 *
	 * @param array $mf
	 * @param array $properties
	 * @return null|array
	 */
	public static function get_prop_array( array $mf, $properties, $args = null ) {
		if ( ! self::is_microformat( $mf ) ) {
			return array();
		}

		$data = array();
		foreach ( $properties as $p ) {
			if ( array_key_exists( $p, $mf['properties'] ) ) {
				foreach ( $mf['properties'][ $p ] as $v ) {
					if ( self::is_microformat( $v ) ) {
						$v = self::parse_item( $v, $mf, $args );
					}
					$data[ $p ] = $v;
				}
			}
		}
		return $data;
	}

	/*
	 * Parse MF2 into JF2
	 *
	 * @param string|DOMDocument|array $input HTML marked up content, HTML in DOMDocument, or array of already parsed MF2 JSON
	 */
	public static function parse( $input, $url, $args = array() ) {
		$defaults    = array(
			'alternate' => true, // Use rel-alternate if set for jf2 or mf2
			'return'    => 'single',
			'follow'    => false, // Follow author links and return parsed data
		);
		$args        = wp_parse_args( $args, $defaults );
		$args['url'] = $url;
		if ( ! in_array( $args['return'], array( 'single', 'feed' ), true ) ) {
			$args['return'] = 'single';
		}
		// Normalize all urls to ensure comparisons
		$url = normalize_url( $url );
		if ( ! class_exists( 'Mf2\Parser' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'lib/mf2/Parser.php';
		}
		if ( is_string( $input ) || is_a( $input, 'DOMDocument' ) ) {
			$parser = new Mf2\Parser( $input, $url );
			$input  = $parser->parse();
			if ( $args['alternate'] ) {
				// Check for rel-alternate jf2 or mf2 feed
				if ( isset( $input['rel-urls'] ) ) {
					foreach ( $input['rel-urls'] as $rel => $info ) {
						if ( isset( $info['rels'] ) && in_array( 'alternate', $info['rels'], true ) ) {
							if ( isset( $info['type'] ) ) {
								if ( 'application/jf2feed+json' === $info['type'] ) {
									$parse = new Parse_This( $rel );
									$parse->fetch();
									return $parse->get();
								}
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

		if ( 'feed' === $args['return'] && $count > 1 ) {
			$input = self::normalize_feed( $input );
			$count = count( $input['items'] );
		}

		if ( 1 === $count ) {
			return self::parse_item( $input['items'][0], $input, $args );
		}

		$return = array();
		$card   = null;
		foreach ( $input['items'] as $key => $item ) {
			$parsed = self::parse_item( $item, $input, $args );
			if ( isset( $parsed['url'] ) ) {
				if ( is_array( $parsed['url'] ) ) {
					$check = in_array( $url, $parsed['url'], true );
				} elseif ( is_string( $parsed['url'] ) ) {
					$check = self::urls_match( $url, $parsed['url'] );
				}
				if ( $check ) {
					if ( 'feed' !== $args['return'] ) {
						return $parsed;
					}
				}
			}
			$return[] = $parsed;
		}
		return $return;
	}

	// Tries to normalize a set of items into a feed
	public static function normalize_feed( $input ) {
		$hcard = array();
		foreach ( $input['items'] as $key => $item ) {
			if ( self::is_type( $item, 'h-card' ) ) {
				$hcard = $item;
				unset( $input['items'][ $key ] );
				break;
			}
		}
		if ( 1 === count( $input['items'] ) ) {
			if ( self::has_prop( $input['items'][0], 'author' ) ) {
				$input['items'][0]['properties']['author'] = array( $hcard );
			}
			return $input;
		}
		return array(
			'items' => array(
				array(
					'type'       => array( 'h-feed' ),
					'properties' => array(
						'author' => array( $hcard ),
					),
					'children'   => $input['items'],
				),
			),
		);
	}

	public static function parse_hfeed( $entry, $mf, $args ) {
		$data         = array(
			'type'  => 'feed',
			'items' => array(),
		);
		$data['name'] = self::get_plaintext( $entry, 'name' );
		$author       = self::find_author( $entry, $args['follow'] );
		if ( self::is_microformat( $author ) ) {
			$data['author'] = self::parse_hcard( $author, $mf, $args );
		} else {
			$data['author'] = $author;
		}
		$data['uid'] = self::get_plaintext( $entry, 'uid' );
		if ( isset( $entry['id'] ) && isset( $args['url'] ) && ! $data['uid'] ) {
			$data['uid'] = $args['url'] . '#' . $entry['id'];
		}

		if ( isset( $entry['children'] ) && 'feed' === $args['return'] ) {
			$data['items'] = self::parse_children( $entry['children'], $mf, $args );
		}
		$data    = array_filter( $data );
		$authors = array();
		if ( isset( $data['author'] ) ) {
			$authors[] = $data['author'];
		}
		if ( isset( $data['items'] ) ) {
			foreach ( $data['items'] as $key => $item ) {
				foreach ( $authors as $author ) {
					if ( is_string( $author['url'] ) ) {
						$author['url'] = array( $author['url'] );
					}
					if ( array_key_exists( 'author', $item ) && in_array( $item['author']['url'], $author['url'], true ) ) {
						$item['author'] = $author;
						break;
					}
				}
				$data['items'][ $key ] = $item;
			}
		}
		return $data;
	}

	public static function parse_children( $children, $mf, $args ) {
		$items = array();
		$index = 0;
		foreach ( $children as $child ) {
			if ( isset( $args['limit'] ) && $args['limit'] === $index ) {
				continue;
			}
			$item = self::parse_item( $child, $mf, $args );
			if ( isset( $item['type'] ) ) {
				$items[] = $item;
			}
			$index++;
		}
		return array_filter( $items );
	}

	public static function parse_item( $item, $mf, $args ) {
		if ( self::is_type( $item, 'h-feed' ) ) {
			return self::parse_hfeed( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-card' ) ) {
			return self::parse_hcard( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-entry' ) || self::is_type( $item, 'h-cite' ) ) {
			return self::parse_hentry( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-event' ) ) {
			return self::parse_hevent( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-review' ) ) {
			return self::parse_hreview( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-recipe' ) ) {
			return self::parse_hrecipe( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-listing' ) ) {
			return self::parse_hlisting( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-product' ) ) {
			return self::parse_hproduct( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-resume' ) ) {
			return self::parse_hresume( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-item' ) ) {
			return self::parse_hitem( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-leg' ) ) {
			return self::parse_hleg( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-adr' ) ) {
			return self::parse_hadr( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-geo' ) ) {
			return self::parse_hadr( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-measure' ) ) {
			return self::parse_hmeasure( $item, $mf, $args );
		}
		return self::parse_hunknown( $item, $mf, $args );
	}

	public static function compare( $string1, $string2 ) {
		if ( empty( $string1 ) || empty( $string2 ) ) {
			return false;
		}
		$string1 = trim( $string1 );
		$string2 = trim( $string2 );
		return ( 0 === strpos( $string1, $string2 ) );
	}

	public static function parse_hunknown( $unknown, $mf, $args ) {
		// Parse unknown h property
		$data         = self::parse_h( $unknown, $mf, $args );
		$data['type'] = $unknown['type'][0];
		return $data;
	}

	public static function parse_h( $entry, $mf, $args ) {
		$data              = array();
		$data['name']      = self::get_plaintext( $entry, 'name' );
		$data['published'] = self::get_published( $entry, true, null );
		$data['updated']   = self::get_updated( $entry, true, null );
		$data['url']       = normalize_url( self::get_plaintext( $entry, 'url' ) );
		$author            = self::find_author( $entry, $mf, $args['follow'] );
		if ( self::is_microformat( $author ) ) {
			$data['author'] = self::parse_hcard( $author, $mf, $args, $data['url'] );
		} else {
			$data['author'] = $author;
		}
		$data['content'] = self::parse_html_value( $entry, 'content' );
		$data['summary'] = self::get_summary( $entry, $data['content'] );

		// If name and content are equal remove name
		if ( is_array( $data['content'] ) && array_key_exists( 'text', $data['content'] ) ) {
			if ( self::compare( $data['name'], $data['content']['text'] ) ) {
				unset( $data['name'] );
			}
		}

		if ( isset( $mf['rels']['syndication'] ) ) {
			if ( isset( $data['syndication'] ) ) {
				if ( is_string( $data['syndication'] ) ) {
					$data['syndication'] = array( $data['syndication'] );
				}
				$data['syndication'] = array_unique( array_merge( $data['syndication'], $mf['rels']['syndication'] ) );
			} else {
				$data['syndication'] = $mf['rels']['syndication'];
			}
		}
		return array_filter( $data );
	}

	public static function parse_hmeasure( $measure, $mf, $args ) {
		$data       = array(
			'type' => 'measure',
		);
		$properties = array(
			'num',
			'unit',
		);
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $measure, $property );
		}
		return array_filter( $data );
	}

	public static function parse_hleg( $leg, $mf, $args ) {
		// The aaronpk special
		$data       = array();
		$properties = array(
			'url',
			'name',
			'origin',
			'destination',
			'operator',
			'transit-type',
			'number',
		);
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $leg, $property );
		}

		$data['departure'] = self::get_datetime_property( 'departure', $leg, true, null )->format( DATE_W3C );
		$data['arrival']   = self::get_datetime_property( 'arrival', $leg, true, null )->format( DATE_W3C );
		$data              = array_filter( $data );
		return $data;
	}

	public static function parse_hentry( $entry, $mf, $args ) {
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
			'pk-ate',
			'pk-drank',
			'item',
		);
		$data         = self::get_prop_array( $entry, $properties );
		$data['type'] = self::is_type( $entry, 'h-entry' ) ? 'entry' : 'cite';
		$properties   = array( 'url', 'weather', 'temperature', 'rsvp', 'featured', 'swarm-coins', 'latitude', 'longitude' );
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $entry, $property );
		}
		$data = array_filter( $data );
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		if ( $args['references'] ) {
			$data = jf2_references( $data );
		}
		$data['post-type'] = post_type_discovery( $data );
		return array_filter( $data );
	}

	public static function parse_hcard( $hcard, $mf, $args, $url = false ) {
		if ( ! self::is_microformat( $hcard ) ) {
			return;
		}
		$data       = array();
		$properties = array(
			'url',
			'uid',
			'name',
			'note',
			'photo',
			'bday',
			'callsign',
			'latitude',
			'longitude',
			'street-address',
			'extended-address',
			'locality',
			'region',
			'country-name',
			'label',
			'post-office-box',
			'given-name',
			'honoric-prefix',
			'additional-name',
			'family-name',
			'honorifix-suffix',
			'email',
			'postal-code',
			'altitude',
			'location',
		);
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $hcard, $property );
		}
		$data = array_filter( $data );
		$data = array_merge( self::get_prop_array( $hcard, array_keys( $hcard['properties'] ) ), $data );

		$data['type'] = 'card';
		if ( isset( $hcard['children'] ) ) {
			// In the case of sites like tantek.com where multiple feeds are nested inside h-card if it is a feed request return only the first feed
			if ( 'feed' === $args['return'] && self::is_type( $hcard['children'][0], 'h-feed' ) ) {
				$feed = self::parse_hfeed( $hcard['children'][0], $mf, $args );
				unset( $data['children'] );
				$feed['author'] = $data;
				return array_filter( $feed );
			} else {
				$data['items'] = self::parse_children( $hcard['children'], $mf, $args );
			}
		}
		return array_filter( $data );
	}

	public static function parse_hevent( $event, $mf, $args ) {
		if ( ! self::is_microformat( $event ) ) {
			return;
		}
		$data       = array(
			'type' => 'event',
		);
		$data       = array_merge( $data, self::parse_h( $event, $mf, $args ) );
		$properties = array( 'category', 'attendee', 'organizer', 'location', 'start', 'end', 'photo', 'uid', 'url' );
		$data       = array_merge( $data, self::get_prop_array( $event, $properties ) );
		return array_filter( $data );
	}

	public static function parse_hreview( $entry, $mf, $args ) {
		if ( ! self::is_microformat( $entry ) ) {
			return;
		}
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
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}


	public static function parse_hproduct( $entry, $mf, $args ) {
		if ( ! self::is_microformat( $entry ) ) {
			return;
		}
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
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}


	public static function parse_hresume( $entry, $mf, $args ) {
		if ( ! self::is_microformat( $entry ) ) {
			return;
		}
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

	public static function parse_hlisting( $entry, $mf, $args ) {
		if ( ! self::is_microformat( $entry ) ) {
			return;
		}
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
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return array_filter( $data );
	}

	public static function parse_hrecipe( $recipe, $mf, $args ) {
		if ( ! self::is_microformat( $recipe ) ) {
			return;
		}
		$data       = array(
			'type' => 'recipe',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $recipe, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $recipe, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $recipe, $mf, $args ) );
		return array_filter( $data );
	}

	public static function parse_hitem( $item, $mf, $args ) {
		if ( ! self::is_microformat( $item ) ) {
			return;
		}
		$data       = array(
			'type' => 'item',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item' );
		$data       = self::get_prop_array( $item, $properties );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $item, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $item, $mf, $args ) );
		return array_filter( $data );
	}

	public static function parse_hadr( $hadr, $mf, $args ) {
		if ( ! self::is_microformat( $hadr ) ) {
			return;
		}
		$data       = array(
			'type' => 'adr',
		);
		$properties = array( 'weather', 'latitude', 'longitude', 'altitude', 'label', 'street-address', 'extended-address', 'locality', 'region', 'country-name' );
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $hadr, $property );
		}
		$properties = array( 'temperature', 'geo' );
		$props      = self::get_prop_array( $hadr, $properties );
		$data       = array_merge( $data, $props );
		return array_filter( $data );
	}

	public static function parse_hgeo( $hgeo, $mf, $args ) {
		if ( ! self::is_microformat( $hgeo ) ) {
			return;
		}
		$data       = array(
			'type' => 'geo',
		);
		$properties = array( 'latitude', 'longitude', 'altitude' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $hadr, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		return array_filter( $data );
	}

}
