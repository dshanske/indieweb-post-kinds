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
				'author'     => self::get_author( $feed->get_author() ),
				'name'       => $title,
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
	public static function get_author( $author ) {
		if ( ! $author ) {
			return array();
		}
		$return = array_filter(
			array(
				'type'  => 'card',
				'name'  => $author->get_name(),
				'url'   => $author->get_link(),
				'email' => $author->get_email(),
			)
		);
		return $return;
	}

	/*
	 * Takes a SimplePie_Item object and Turns it into a JF2 entry
	 * @param SimplePie_Item $item
	 * @return JF2
	 */
	public static function get_item( $item, $title = '' ) {
		$return     = array(
			'type'        => 'entry',
			'name'        => htmlspecialchars_decode( $item->get_title(), ENT_QUOTES ),
			'author'      => self::get_author( $item->get_author() ),
			'publication' => $title,
			'summary'     => $item->get_description( true ),
			'content'     => array_filter(
				array(
					'html' => htmlspecialchars( $item->get_content( true ) ),
					'text' => wp_strip_all_tags( $item->get_content( true ) ),
				)
			),
			'published'   => $item->get_date( DATE_W3C ),
			'updated'     => $item->get_updated_date( DATE_W3C ),
			'url'         => $item->get_permalink(),
			'uid'         => $item->get_id(),
			'location'    => self::get_location( $item ),
			'category'    => self::get_categories( $item->get_categories() ),
		);
		$enclosures = $item->get_enclosures();
		foreach ( $enclosures as $enclosure ) {
			$medium = $enclosure->get_medium();
			if ( 'image' === $medium ) {
				$medium = 'photo';
			}
			if ( ! $medium ) {
				$medium = $enclosure->get_type();
				switch ( $medium ) {
					case 'audio/mpeg':
						$medium = 'audio';
						break;
					case 'image/jpeg':
					case 'image/png':
					case 'image/gif':
						$medium = 'photo';
						break;
				}
			}
			if ( array_key_exists( $medium, $return ) ) {
				if ( is_string( $return[ $medium ] ) ) {
					$return[ $medium ] = array( $return[ $medium ] );
				}
				$return[ $medium ][] = $enclosure->get_link();
			} else {
				$return[ $medium ] = $enclosure->get_link();
			}
		}
		// If there is just one photo it is probably the featured image
		if ( isset( $return['photo'] ) && is_string( $return['photo'] ) ) {
			$return['featured'] = $return['photo'];
			unset( $return['photo'] );
		}
		$return['post_type'] = post_type_discovery( $return );
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
		$return = $item->get_item_tags( SIMPLEPIE_NAMESPACE_GEORSS, 'featureName' );
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
