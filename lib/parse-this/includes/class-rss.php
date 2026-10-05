<?php
/**
 * RSS class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Converts RSS and Atom feeds, as parsed by SimplePie, into jf2.
 *
 * @since 1.0.0
 */
class RSS extends Base {

	/**
	 * Converts a SimplePie feed into a jf2 feed.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie $feed Initialized SimplePie feed.
	 * @param string    $url  URL the feed was fetched from. Unused.
	 * @return array jf2 feed with type 'feed', '_feed_type' ('RSS', 'atom' or
	 *               'unknown'), name, summary, url, photo, author, 'items', and the
	 *               '_last_published'/'_last_updated' dates of its items.
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
				'type'            => 'feed',
				'_feed_type'      => self::get_type( $feed ),
				'_last_published' => self::find_last_published( $items ),
				'_last_updated'   => self::find_last_updated( $items ),
				'summary'         => $feed->get_description(),
				'author'          => self::get_authors( $feed->get_author() ),
				'name'            => htmlspecialchars_decode( $title, ENT_QUOTES ),
				'url'             => $feed->get_permalink(),
				'photo'           => $feed->get_image_url(),
				'items'           => $items,
			)
		);
	}

	/**
	 * Returns the channel-level last-updated date of a feed.
	 *
	 * Reads lastBuildDate for RSS 2.0 and updated for Atom 1.0.
	 *
	 * @since 2.0.0
	 *
	 * @param SimplePie $feed Initialized SimplePie feed.
	 * @return string|null The date in W3C format, or null if not present.
	 */
	public static function last_updated( $feed ) {
		$type    = self::get_type( $feed );
		$updated = null;
		if ( 'RSS' === $type ) {
			$updated = $feed->get_channel_tags( SIMPLEPIE_NAMESPACE_RSS_20, 'lastBuildDate' );
		} elseif ( 'atom' === $type ) {
			$updated = $feed->get_channel_tags( SIMPLEPIE_NAMESPACE_ATOM_10, 'updated' );
		}
		if ( $updated && isset( $updated[0]['data'] ) ) {
			return normalize_iso8601( $updated[0]['data'] );
		}

		return null;
	}

	/**
	 * Returns the format of a feed.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie $feed Initialized SimplePie feed.
	 * @return string|null 'RSS', 'atom' or 'unknown', or null if SimplePie reports
	 *                     another type.
	 */
	public static function get_type( $feed ) {
		if ( $feed->get_type() & SIMPLEPIE_TYPE_NONE ) {
			return 'unknown';
		} elseif ( $feed->get_type() & SIMPLEPIE_TYPE_RSS_ALL ) {
			return 'RSS';
		} elseif ( $feed->get_type() & SIMPLEPIE_TYPE_ATOM_ALL ) {
			return 'atom';
		}
	}

	/**
	 * Converts SimplePie authors into jf2 cards.
	 *
	 * If an author name contains HTML links, each link becomes its own card with
	 * the link text as name and the href as url.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Author|SimplePie_Author[]|null $author One author or a list.
	 * @return array A single card, a list of cards, or an empty array.
	 */
	public static function get_authors( $author ) {
		if ( ! $author ) {
			return array();
		}
		if ( $author instanceof \SimplePie\Author || $author instanceof \SimplePie_Author ) {
			$author = array( $author );
		}
		$return = array();
		foreach ( $author as $a ) {
			$r     = array(
				'type'  => 'card',
				'name'  => htmlspecialchars_decode( $a->get_name() ),
				'url'   => $a->get_link(),
				'email' => self::validate_email( $a->get_email() ),
			);
			$dom   = pt_load_domdocument( $r['name'] );
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

	/**
	 * Converts a media credit into a jf2 card.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Credit $credit Credit from a media enclosure.
	 * @return array|null Card with role and name, or null if $credit is not a
	 *                    SimplePie_Credit.
	 */
	public static function credit_to_card( $credit ) {
		if ( ! ( $credit instanceof \SimplePie\Credit || $credit instanceof \SimplePie_Credit ) ) {
			return null;
		}
		return array(
			'type' => 'card',
			'role' => $credit->get_role(),
			'name' => $credit->get_name(),
		);
	}

	/**
	 * Converts an item's source feed into a jf2 cite.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Source $source Source feed of an item.
	 * @return array|null Cite with name, summary, url, author and photo, or null if
	 *                    $source is not a SimplePie_Source.
	 */
	public static function source_to_cite( $source ) {
		if ( ! ( $source instanceof \SimplePie\Source || $source instanceof \SimplePie_Source ) ) {
			return null;
		}
		return array_filter(
			array(
				'type'    => 'cite',
				'name'    => $source->get_title(),
				'summary' => $source->get_description(),
				'url'     => $source->get_permalink(),
				'author'  => self::get_authors( $source->get_authors() ),
				'photo'   => $source->get_image_url(),
			)
		);
	}


	/**
	 * Returns the feed an item was originally published in.
	 *
	 * Uses the RSS 2.0 <source> element if present, otherwise SimplePie's
	 * source (for example Atom <source>).
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return array|null Array with url and name, a cite from source_to_cite(), or
	 *                    null.
	 */
	public static function get_source( $item ) {
		$return = $item->get_item_tags( SIMPLEPIE_NAMESPACE_RSS_20, 'source' );
		if ( $return ) {
			return array(
				'url'  => $return[0]['attribs']['']['url'],
				'name' => $return[0]['data'],
			);
		}
		return self::source_to_cite( $item->get_source() );
	}

	/**
	 * Returns the thumbnail URL of an item or enclosure.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Item|SimplePie_Enclosure $item Item or enclosure.
	 * @return string|null The thumbnail URL, or null if there is none.
	 */
	public static function get_thumbnail( $item ) {
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

	/**
	 * Converts a SimplePie item into a jf2 entry.
	 *
	 * Enclosures are mapped by MIME type to audio, photo or video, with their
	 * keywords, durations, descriptions, thumbnails and credits merged into the
	 * entry. A single photo enclosure becomes the featured image, with the
	 * iTunes image as a fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Item $item  Feed item.
	 * @param string         $title Optional. Feed title, used as the publication.
	 * @return array jf2 entry.
	 */
	public static function get_item( $item, $title = '' ) {
		$content = Parser::clean_content( $item->get_content( true ) );
		$return  = array(
			'type'         => 'entry',
			'name'         => $item->get_title(),
			'author'       => self::get_authors( $item->get_authors() ),
			'contributors' => self::get_authors( $item->get_contributors() ),
			'publication'  => $title,
			'summary'      => wp_strip_all_tags( $item->get_description( true ) ),
			'content'      => array_filter(
				array(
					'html' => $content,
					'text' => wp_strip_all_tags( $content ),
				)
			),
			'_source'      => self::get_source( $item ),
			'published'    => self::get_date( $item ),
			'updated'      => self::get_updated_date( $item ),
			'url'          => $item->get_permalink(),
			'uid'          => $item->get_id(),
			'location'     => self::get_location( $item ),
			'category'     => self::get_categories( $item->get_categories() ),
			'featured'     => self::get_thumbnail( $item ),
		);

		if ( ! is_array( $return['category'] ) ) {
			$return['category'] = array();
		}

		// A truncated title that only repeats the start of the content (or the
		// description), or a title that is just the link, is not a title.
		$name = is_string( $return['name'] ) ? trim( $return['name'] ) : '';
		$text      = $return['content']['text'] ?? $return['summary'];
		$truncated = preg_match( '/(\.\.\.|…)$/u', $name ) && name_is_content_prefix( $name, $text );
		if ( '' !== $name && ( $truncated || $name === $return['url'] ) ) {
			unset( $return['name'] );
		}

		// To cover the non obvious types.
		$medium_map = array(
			'application/x-shockwave-flash' => 'video',
		);

		// Newer SimplePie returns null rather than an empty array when there are none.
		$enclosures = $item->get_enclosures();
		if ( ! is_array( $enclosures ) ) {
			$enclosures = array();
		}
		foreach ( $enclosures as $enclosure ) {
			$medium = $enclosure->get_type();
			if ( ! $medium ) {
				$medium = $enclosure->get_medium();
			} elseif ( array_key_exists( $medium, $medium_map ) ) {
					$medium = $medium_map[ $medium ];
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
				$keywords = $enclosure->get_keywords();
				if ( ! $keywords ) {
					$keywords = array();
				}
				$return['category'] = array_merge( $return['category'], $keywords );
			} else {
				$return['category'] = $enclosure->get_keywords();
			}
			if ( ! isset( $return['duration'] ) ) {
				$duration = $enclosure->get_duration();
				if ( 0 < $duration ) {
					$return['duration'] = seconds_to_iso8601( $duration );
				}
			}
			if ( empty( $return['summary'] ) ) {
				$return['summary'] = $enclosure->get_description();
			}
			if ( empty( $return['featured'] ) ) {
				$return['featured'] = self::get_thumbnail( $enclosure );
			}
			$credits = $enclosure->get_credits();
			if ( ! $credits ) {
				$credits = array();
			}
			foreach ( $credits as $credit ) {
				if ( ! isset( $return['credits'] ) ) {
					$return['credits'] = array();
				}
				$return['credits'][] = self::credit_to_card( $credit );
			}
		}
		// If there is just one photo it is probably the featured image.
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
		$return['post-type'] = post_type_discovery( $return );
		foreach ( array( 'category', 'video', 'audio' ) as $prop ) {
			if ( array_key_exists( $prop, $return ) && is_array( $return[ $prop ] ) ) {
				$return[ $prop ] = array_unique( $return[ $prop ] );
			}
		}
		return array_filter( $return );
	}

	/**
	 * Returns the labels of a list of categories.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Category[]|null $categories Item categories.
	 * @return string[] Category labels.
	 */
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

	/**
	 * Returns an item's W3C Basic Geo featureName.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string|null The place name, or null if not present.
	 */
	private static function get_location_name( $item ) {
		$return = $item->get_item_tags( SIMPLEPIE_NAMESPACE_W3C_BASIC_GEO, 'featureName' );
		if ( $return ) {
			return $return[0]['data'];
		}
	}


	/**
	 * Returns an item's geographic location.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return array Any of latitude, longitude and name that are present.
	 */
	public static function get_location( $item ) {
		return array_filter(
			array(
				'latitude'  => $item->get_latitude(),
				'longitude' => $item->get_longitude(),
				'name'      => self::get_location_name( $item ),
			)
		);
	}

	/**
	 * Returns an item's published date.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string|null The date in W3C format, null if the item has none, or the
	 *                     raw value if it cannot be parsed.
	 */
	public static function get_date( $item ) {
		return normalize_iso8601( $item->get_date( '' ) );
	}

	/**
	 * Returns an item's updated date.
	 *
	 * @since 1.0.0
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string|null The date in W3C format, null if the item has none, or the
	 *                     raw value if it cannot be parsed.
	 */
	public static function get_updated_date( $item ) {
		return normalize_iso8601( $item->get_updated_date( '' ) );
	}
}
