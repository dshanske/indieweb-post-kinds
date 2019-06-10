<?php
/**
 * Helpers for Turning RSS/Atom into JF2
**/

class Parse_This_RSS {

	/*
	 * Parse RSS/Atom into JF2
	 *
	 * @param SimplePie $feed
	 * @return JF2 array
	 */
	public static function parse( $feed, $url ) {
		$items     = array();
		$rss_items = $feed->get_items();
		$title     = $feed->get_title();
		foreach ( $rss_items as $item ) {
			$items[] = self::get_item( $item, $title );
		}
		return array_filter(
			array(
				'type'       => 'feed',
				'_feed_type' => self::get_type( $feed ),
				'summary'    => $feed->get_description(),
				'author'     => self::get_authors( $feed->get_author() ),
				'name'       => htmlspecialchars_decode( $title, ENT_QUOTES ),
				'url'        => $feed->get_permalink(),
				'photo'      => $feed->get_image_url(),
				'items'      => $items,
			)
		);
	}

	public static function get_type( $feed ) {
		if ( $feed->get_type() & SIMPLEPIE_TYPE_NONE ) {
			return 'unknown';
		} elseif ( $feed->get_type() & SIMPLEPIE_TYPE_RSS_ALL ) {
			return 'RSS';
		} elseif ( $feed->get_type() & SIMPLEPIE_TYPE_ATOM_ALL ) {
			return 'atom';
		}
	}

	/*
	 * Takes a SimplePie_Author object and Turns it into a JF2 Author property
	 * @param SimplePie_Author $author
	 * @return JF2 array
	 */
	public static function get_authors( $author ) {
		if ( ! $author ) {
			return array();
		}
		if ( $author instanceof SimplePie_Author ) {
			$author = array( $author );
		}
		$return = array();
		foreach ( $author as $a ) {
			$r   = array(
				'type'  => 'card',
				'name'  => htmlspecialchars_decode( $a->get_name() ),
				'url'   => $a->get_link(),
				'email' => $a->get_email(),
			);
			$dom = new DOMDocument();
			$dom->loadHTML( $r['name'] );
			$links = $dom->getElementsByTagName( 'a' );
			$names = array();
			foreach ( $links as $link ) {
					$names[ wp_strip_all_tags( $link->nodeValue ) ] = $link->getAttribute( 'href' ); // phpcs:ignore
			}
			if ( ! empty( $names ) ) {
				if ( 1 === count( $names ) ) {
					reset( $names );
					$r['name'] = key( $names );
				} else {
					foreach ( $names as $name => $url ) {
						$return[] = array(
							'type' => 'card',
							'name' => $name,
							'url'  => $url,
						);
					}
				}
			} else {
				$r['name'] = wp_strip_all_tags( $r['name'] );
				$return[]  = array_filter( $r );
			}
		}
		if ( 1 === count( $return ) ) {
			$return = array_shift( $return );
		}
		return $return;
	}

	public static function credit_to_card( $credit ) {
		if ( ! $credit instanceof SimplePie_Credit ) {
			return null;
		}
		return array(
			'type' => 'card',
			'role' => $credit->get_role(),
			'name' => $credit->get_name(),
		);
	}

	public static function source_to_cite( $source ) {
		if ( ! $source instanceof SimplePie_Source ) {
			return null;
		}
		return array_filter(
			array(
				'type'    => 'cite',
				'name'    => $source->get_title(),
				'summary' => $source->get_description(),
				'url'     => $source->get_permalink(),
			)
		);
	}


	public function get_source( $item ) {
		$return = $item->get_item_tags( SIMPLEPIE_NAMESPACE_RSS_20, 'source' );
		if ( $return ) {
			return array(
				'url'  => $return[0]['attribs']['']['url'],
				'name' => $return[0]['data'],
			);
		}
		return self::source_to_cite( $item->get_source() );
	}

	public function get_thumbnail( $item ) {
		if ( method_exists( $item, 'get_thumbnail' ) ) {
			$return = $item->get_thumbnail();
			if ( is_string( $return ) ) {
				return $return;
			}
			if ( is_array( $return ) && isset( $return['url'] ) ) {
				return $return['url'];
			}
		}
		return null;
	}

	/*
	 * Takes a SimplePie_Item object and Turns it into a JF2 entry
	 * @param SimplePie_Item $item
	 * @return JF2
	 */
	public static function get_item( $item, $title = '' ) {
		$return = array(
			'type'         => 'entry',
			'name'         => $item->get_title(),
			'author'       => self::get_authors( $item->get_authors() ),
			'contributors' => self::get_authors( $item->get_contributors() ),
			'publication'  => $title,
			'summary'      => wp_strip_all_tags( $item->get_description( true ) ),
			'content'      => array_filter(
				array(
					'html' => parse_this_clean_content( $item->get_content( true ) ),
					'text' => wp_strip_all_tags( htmlspecialchars_decode( $item->get_content( true ) ) ),
				)
			),
			'_source'      => self::get_source( $item ),
			'published'    => $item->get_date( DATE_W3C ),
			'updated'      => $item->get_updated_date( DATE_W3C ),
			'url'          => $item->get_permalink(),
			'uid'          => $item->get_id(),
			'location'     => self::get_location( $item ),
			'category'     => self::get_categories( $item->get_categories() ),
			'featured'     => self::get_thumbnail( $item ),
		);

		if ( ! is_array( $return['category'] ) ) {
			$return['category'] = array();
		}

		$enclosures = $item->get_enclosures();
		foreach ( $enclosures as $enclosure ) {
			$medium = $enclosure->get_type();
			if ( ! $medium ) {
				$medium = $enclosure->get_medium();
			} else {
				$medium = explode( '/', $medium );
				$medium = array_shift( $medium );
			}
			switch ( $medium ) {
				case 'audio':
					$medium = 'audio';
					break;
				case 'image':
					$medium = 'photo';
					break;
				case 'video':
					$medium = 'video';
					break;
			}
			if ( array_key_exists( $medium, $return ) ) {
				if ( is_string( $return[ $medium ] ) ) {
					$return[ $medium ] = array( $return[ $medium ] );
				}
				$return[ $medium ][] = $enclosure->get_link();
			} else {
				$return[ $medium ] = $enclosure->get_link();
			}
			if ( isset( $return['category'] ) && is_array( $return['category'] ) ) {
				$return['category'] = array_merge( $return['category'], $enclosure->get_keywords() );
			} else {
				$return['category'] = $enclosure->get_keywords();
			}
			if ( ! isset( $return['duration'] ) ) {
				$duration = $enclosure->get_duration();
				if ( 0 < $duration ) {
					$return['duration'] = seconds_to_iso8601( $duration );
				}
			}
			$credits = $enclosure->get_credits();
			foreach ( $credits as $credit ) {
				if ( ! isset( $return['credits'] ) ) {
					$return['credits'] = array();
				}
				$return['credits'][] = self::credit_to_card( $credit );
			}
		}
		// If there is just one photo it is probably the featured image
		if ( isset( $return['photo'] ) && is_string( $return['photo'] ) && empty( $return['featured'] ) ) {
			$return['featured'] = $return['photo'];
			unset( $return['photo'] );
		}
		if ( empty( $return['featured'] ) ) {
			$i = $item->get_item_tags( SIMPLEPIE_NAMESPACE_ITUNES, 'image' );
			if ( is_array( $i ) ) {
				$i = array_shift( $i );
				if ( isset( $i['attribs'] ) && is_array( $i['attribs'] ) ) {
					$i = array_shift( $i['attribs'] );
					if ( isset( $i['href'] ) ) {
						$i = $i['href'];
					}
				}
			}
			if ( is_string( $i ) ) {
				$return['featured'] = $i;
			}
		}
		$return['post_type'] = post_type_discovery( $return );
		foreach ( array( 'category', 'video', 'audio' ) as $prop ) {
			$return[ $prop ] = array_unique( $return[ $prop ] );
		}
		return array_filter( $return );
	}

	private static function get_categories( $categories ) {
		if ( ! is_array( $categories ) ) {
			return array();
		}
		$return = array();
		foreach ( $categories as $category ) {
			$return[] = $category->get_label();
		}
		return $return;
	}

	private static function get_location_name( $item ) {
		$return = $item->get_item_tags( SIMPLEPIE_NAMESPACE_W3C_BASIC_GEO, 'featureName' );
		if ( $return ) {
			return $return[0]['data'];
		}
	}


	public static function get_location( $item ) {
		return array_filter(
			array(
				'latitude'  => $item->get_latitude(),
				'longitude' => $item->get_longitude(),
				'name'      => self::get_location_name( $item ),
			)
		);
	}


}
