<?php
/**
 * MF2 class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Converts microformats2 into jf2.
 *
 * Parses HTML with the bundled php-mf2 parser (or accepts already-parsed
 * mf2), then converts each supported h-* type into its jf2 form. Derived from
 * php-mf-cleaner, XRay's Mf2 format and Semantic Linkbacks' mf2 handler.
 *
 * @since 1.0.0
 *
 * @link https://github.com/barnabywalters/php-mf-cleaner
 * @link https://github.com/aaronpk/XRay/blob/master/lib/Formats/Mf2.php
 * @link https://github.com/pfefferle/wordpress-semantic-linkbacks/blob/master/includes/class-linkbacks-mf2-handler.php
 */
class MF2 extends MF2_Utils {

	/**
	 * Properties the type parsers read, whether they output them as they are,
	 * under another name (description becomes content) or not at all (a name
	 * that repeats the content). Anything else is passed through by
	 * add_unknown_properties().
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const KNOWN_PROPERTIES = array(
		'additional-name',
		'altitude',
		'attendee',
		'audio',
		'author',
		'bday',
		'best',
		'bookmark-of',
		'brand',
		'callsign',
		'category',
		'checked-in-by',
		'checkin',
		'content',
		'country-name',
		'description',
		'destination',
		'duration',
		'email',
		'end',
		'extended-address',
		'family-name',
		'favorite-of',
		'featured',
		'follow-of',
		'geo',
		'given-name',
		'honorific-prefix',
		'honorific-suffix',
		'identifier',
		'in-reply-to',
		'ingredient',
		'instructions',
		'invitee',
		'item',
		'itinerary',
		'jam-of',
		'label',
		'latitude',
		'like-of',
		'listen-of',
		'locality',
		'location',
		'longitude',
		'name',
		'note',
		'num',
		'number',
		'nutrition',
		'operator',
		'organizer',
		'origin',
		'photo',
		'pk-ate',
		'pk-drank',
		'play-of',
		'post-office-box',
		'postal-code',
		'price',
		'published',
		'quotation-of',
		'rating',
		'read-of',
		'region',
		'repost-of',
		'review-of',
		'rsvp',
		'start',
		'street-address',
		'summary',
		'swarm-coins',
		'syndication',
		'tag-of',
		'temperature',
		'transit-type',
		'uid',
		'unit',
		'updated',
		'url',
		'video',
		'watch-of',
		'weather',
		'worst',
		'yield',
	);

	/**
	 * Author pages already fetched during this request, keyed by URL.
	 *
	 * @since 2.0.0
	 * @var array[]
	 */
	private static $author_pages = array();

	/**
	 * Finds the h-feeds in a document.
	 *
	 * Top-level h-feeds and h-feeds nested one level inside another item are
	 * returned. If the document has items but no h-feed, an implied h-feed for
	 * $url is returned. Feeds without a url get $url, plus #id if they have one.
	 *
	 * @since 1.0.0
	 *
	 * @param string|DOMDocument|array $input HTML, a parsed DOM document, or parsed mf2.
	 * @param string                   $url   URL of the document.
	 * @return array[] The h-feed microformats found.
	 */
	public static function find_hfeed( $input, $url ) {
		if ( ! class_exists( 'Mf2\Parser' ) ) {
					require_once plugin_dir_path( __DIR__ ) . 'lib/mf2/Parser.php';
		}
		if ( is_string( $input ) || is_a( $input, 'DOMDocument' ) ) {
			$parser = new \Mf2\Parser( $input, $url );
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
	 * Finds the author of an item using the IndieWeb authorship algorithm.
	 *
	 * Uses the item's author h-card if it has one; otherwise an author URL, the
	 * author name, or the document's rel=author link. With an author page:
	 * when $follow is true and the page is on another URL, it is fetched and
	 * its h-card chosen as the algorithm says (url and uid both the page, else
	 * url matching a rel=me link), else an h-card on this page whose url is the
	 * author page, else the author page's own item if that is a card. Without
	 * $follow, an h-card on this page whose url is the author page is used.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Implements steps 7.2 to 7.4 of the algorithm. Added $args.
	 *
	 * @param array      $item   Microformat to find the author of.
	 * @param array|bool $mf2    Parsed mf2 document the item came from.
	 * @param bool       $follow Optional. Whether to fetch the author page.
	 *                            Default false.
	 * @param array      $args   Optional. Parse arguments, for parsing a fetched
	 *                            author page.
	 * @return array|null An h-card microformat, jf2 from the fetched author page, or null
	 *                     if no author was found.
	 */
	public static function find_author( $item, $mf2, $follow = false, $args = array() ) {
		// Follows the authorship algorithm at https://indieweb.org/authorship (steps numbered below).
		$authorpage = false;
		if ( self::has_prop( $item, 'author' ) ) {
			// Check if any of the values of the author property are an h-card.
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
					// This case is only hit when the author property is an mf2 object that is not an h-card.
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
			// Feed items often share an author, so fetch each author page once per request.
			$key = normalize_url( $authorpage );
			if ( $follow && ! self::urls_match( $authorpage, self::get_plaintext( $mf2, 'url' ) ) && ( isset( self::$author_pages[ $key ] ) || Parser::use_request_budget() ) ) {
				if ( ! isset( self::$author_pages[ $key ] ) ) {
					self::$author_pages[ $key ] = self::author_from_page( $authorpage, $mf2, $args );
				}
				return self::$author_pages[ $key ];
			}
			// 7.4 "if the h-entry's page has 1+ h-card with url == author-page URL, use first such h-card"
			$card = self::find_card_by_url( $mf2, $authorpage );
			if ( $card ) {
				return $card;
			}
			$rel = self::get_rel_urls( $mf2, $authorpage );
			if ( $rel ) {
				return array(
					'type'       => array( 'h-card' ),
					'properties' => $rel,
				);
			}
			return array(
				'type'       => array( 'h-card' ),
				'properties' => array(
					'url' => array( $authorpage ),
				),
			);
		}
	}

	/**
	 * Returns the default parse arguments.
	 *
	 * @since 2.0.0
	 *
	 * @return array Default arguments; see parse().
	 */
	public static function default_args() {
		return array(
			'alternate'  => true, // Use rel-alternate if set for jf2 or mf2.
			'return'     => 'single',
			'follow'     => false, // Follow author links and return parsed data.
			'references' => true, // Move nested citations into refs.
		);
	}

	/**
	 * Returns the values of several properties.
	 *
	 * Nested microformats are converted to jf2 with parse_item(). A property
	 * with one value gets that value; one with several gets a list of them,
	 * as jf2 requires.
	 *
	 * @since 1.0.0
	 *
	 * @param array      $mf         Microformat.
	 * @param string[]   $properties Property names to read.
	 * @param array|null $args       Optional. Parse arguments for nested items.
	 *                               Defaults to default_args().
	 * @return array Values keyed by property name. Empty if $mf is not a microformat.
	 */
	public static function get_prop_array( array $mf, $properties, $args = null ) {
		if ( ! self::is_microformat( $mf ) ) {
			return array();
		}
		$args = wp_parse_args( (array) $args, self::default_args() );

		$data = array();
		foreach ( $properties as $p ) {
			if ( array_key_exists( $p, $mf['properties'] ) ) {
				$values = array();
				foreach ( $mf['properties'][ $p ] as $v ) {
					if ( self::is_microformat( $v ) ) {
						$v = self::parse_item( $v, $mf, $args );
					}
					$values[] = $v;
				}
				// Per jf2, a single value is not wrapped in an array.
				$data[ $p ] = ( 1 === count( $values ) ) ? $values[0] : $values;
			}
		}
		return $data;
	}

	/**
	 * Parses microformats2 into jf2.
	 *
	 * With 'alternate' set, a rel=alternate jf2feed, jf2 or mf2 JSON version
	 * of the page is fetched and used instead. Documents with no items but a
	 * rel=author link return that author. With 'return' => 'feed', several
	 * top-level items are combined into one h-feed. Otherwise the item whose URL
	 * matches $url is returned, or the list of all items.
	 *
	 * @since 1.0.0
	 *
	 * @param string|DOMDocument|array $input HTML, a parsed DOM document, or parsed mf2.
	 * @param string                   $url   URL of the document.
	 * @param array                    $args {
	 *     Optional. Parse arguments; see Parser::parse() for the full set.
	 *
	 *     @type bool   $alternate Whether to use a rel=alternate jf2/mf2 version.
	 *                             Default true.
	 *     @type string $return    'single' or 'feed'. Default 'single'.
	 *     @type bool   $follow    Whether to fetch author pages. Default false.
	 * }
	 * @return array jf2 for one item, a list of jf2 items, or an empty array.
	 */
	public static function parse( $input, $url, $args = array() ) {
		$args        = wp_parse_args( $args, self::default_args() );
		$args['url'] = $url;
		if ( ! in_array( $args['return'], array( 'single', 'feed' ), true ) ) {
			$args['return'] = 'single';
		}
		// Normalize all urls to ensure comparisons.
		$url = normalize_url( $url );
		if ( ! class_exists( 'Mf2\Parser' ) ) {
			require_once plugin_dir_path( __DIR__ ) . 'lib/mf2/Parser.php';
		}
		if ( is_string( $input ) || is_a( $input, 'DOMDocument' ) ) {
			$parser = new \Mf2\Parser( $input, $url );
			$input  = $parser->parse();
			if ( $args['alternate'] ) {
				// Check for rel-alternate jf2 or mf2 feed.
				if ( isset( $input['rel-urls'] ) ) {
					foreach ( $input['rel-urls'] as $rel => $info ) {
						if ( isset( $info['rels'] ) && in_array( 'alternate', $info['rels'], true ) ) {
							if ( isset( $info['type'] ) ) {
								if ( 'application/jf2feed+json' === $info['type'] ) {
									$parse = new Parser( $rel );
									$parse->fetch();
									return $parse->get();
								}
								if ( 'application/jf2+json' === $info['type'] ) {
									$parse = new Parser( $rel );
									$parse->fetch();
									return $parse->get();
								}
								if ( 'application/mf2+json' === $info['type'] ) {
									$parse = new Parser( $rel );
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

		if ( ! isset( $input['items'] ) || ! is_array( $input['items'] ) ) {
			$input['items'] = array();
		}
		$count = count( $input['items'] );
		if ( 0 === $count ) {
			if ( self::has_rel( $input, 'author' ) ) {
				$author = self::get_rel( $input, 'author' );
				if ( is_array( $author ) ) {
					$author = array_pop( $author );
				}
				$author_url = $author;
				// A jf2 card: get_rel_urls() returns mf2-style property arrays.
				$rel    = self::get_rel_urls( $input, $author_url );
				$author = array(
					'type' => 'card',
					'url'  => $author_url,
				);
				if ( is_array( $rel ) && isset( $rel['name'][0] ) && is_string( $rel['name'][0] ) && '' !== trim( $rel['name'][0] ) ) {
					$author['name'] = trim( $rel['name'][0] );
				}
				if ( ! self::urls_match( $url, $author_url ) ) {
					return array(
						'author' => $author,
					);
				} else {
					return $author;
				}
			}
			return array();
		}

		if ( 'feed' === $args['return'] && $count > 1 ) {
			$input = self::normalize_feed( $input );
			$count = count( $input['items'] );
		}

		// A page with a single top-level item is that item.
		if ( 1 === $count ) {
			$return = self::parse_item( $input['items'][0], $input, $args );
			if ( is_array( $return ) && self::has_rel( $input, 'alternate' ) ) {
				$return['_alternate'] = self::get_rel( $input, 'alternate' );
			}
			return $return;
		}

		// Several top-level items, and one is wanted: work out which is the page's.
		if ( 'feed' !== $args['return'] ) {
			$main = self::select_main_item( $input, $url );
			if ( 'feed' === $main ) {
				$args['return'] = 'feed';
				return self::parse( $input, $url, $args );
			}
			if ( is_array( $main ) ) {
				$return = self::parse_item( $main, $input, $args );
				if ( is_array( $return ) && self::has_rel( $input, 'alternate' ) ) {
					$return['_alternate'] = self::get_rel( $input, 'alternate' );
				}
				return $return;
			}
		}

		$return = array();
		$card   = null;
		foreach ( $input['items'] as $key => $item ) {
			$parsed = self::parse_item( $item, $input, $args );
			$check  = false;
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

		return array_filter( $return );
	}

	/**
	 * Combines a document's top-level items into a single h-feed.
	 *
	 * The first h-card is removed from the items and used as the feed's author.
	 * If only one item remains, the document is returned with that h-card as the
	 * item's author instead.
	 *
	 * @since 1.0.0
	 *
	 * @param array $input Parsed mf2 document.
	 * @return array Parsed mf2 document with one h-feed item.
	 */
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

	/**
	 * Converts an h-feed into a jf2 feed.
	 *
	 * Children are only parsed when $args['return'] is 'feed'. Items whose
	 * author URL matches the feed author get the full feed author card.
	 *
	 * @since 1.0.0
	 *
	 * @param array $entry h-feed microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array jf2 feed with name, author, uid, items and the items'
	 *               '_last_published'/'_last_updated' dates.
	 */
	public static function parse_hfeed( $entry, $mf, $args ) {
		$data         = array(
			'type'  => 'feed',
			'items' => array(),
		);
		$data['name'] = self::get_plaintext( $entry, 'name' );
		// JF2 Feed: url SHOULD be defined and MUST be a single string. Fall back to the page.
		$data['url'] = self::get_plaintext( $entry, 'url' );
		if ( ! is_string( $data['url'] ) && isset( $args['url'] ) ) {
			$data['url'] = $args['url'];
		}
		$data['url'] = normalize_url( $data['url'] );
		$author      = self::find_author( $entry, $mf, $args['follow'], $args );
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
		$data    = self::filter_empty( $data );
		$authors = array();
		if ( isset( $data['author'] ) ) {
			$authors[] = $data['author'];
		}
		if ( isset( $data['items'] ) ) {
			foreach ( $data['items'] as $key => $item ) {
				// An entry in a feed with no author of its own is by the feed's author.
				if ( empty( $item['author'] ) && ! empty( $data['author'] ) && 'card' !== ( $item['type'] ?? '' ) ) {
					$item['author'] = $data['author'];
				}
				foreach ( $authors as $author ) {
					if ( ! is_array( $author ) || empty( $author['url'] ) || ! isset( $item['author']['url'] ) ) {
						continue;
					}
					// Compare against a list of URLs, but leave the card itself unchanged.
					$author_urls = (array) $author['url'];
					if ( in_array( $item['author']['url'], $author_urls, true ) ) {
						$item['author'] = $author;
						break;
					}
				}
				$data['items'][ $key ] = $item;
			}
			$data['_last_published'] = self::find_last_published( $data['items'] );
			$data['_last_updated']   = self::find_last_updated( $data['items'] );
		}
		return $data;
	}

	/**
	 * Converts a list of child microformats into jf2, up to $args['limit'].
	 *
	 * @since 1.0.0
	 *
	 * @param array $children Child microformats.
	 * @param array $mf       Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array jf2 items that have a type.
	 */
	public static function parse_children( $children, $mf, $args ) {
		$items = array();
		$index = 0;
		$limit = isset( $args['limit'] ) ? (int) $args['limit'] : 0;
		foreach ( $children as $child ) {
			if ( $limit > 0 && $index >= $limit ) {
				break;
			}
			$item = self::parse_item( $child, $mf, $args );
			if ( isset( $item['type'] ) ) {
				$items[] = $item;
			}
			$index++;
		}
		return array_filter( $items );
	}

	/**
	 * Converts a microformat into jf2 according to its type.
	 *
	 * Handles h-feed, h-card, h-entry, h-cite, h-event, h-review, h-recipe,
	 * h-listing, h-product, h-resume, h-item, h-leg, h-adr, h-geo and
	 * h-measure. Anything else goes to parse_hunknown(). Properties no parser
	 * knows are then passed through (see add_unknown_properties()), and with
	 * $args['references'], nested objects with a URL are moved to refs (see
	 * jf2_references()).
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Applies references to every type, not only entries, and
	 *              passes unknown properties through.
	 *
	 * @param array $item Microformat.
	 * @param array $mf   Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array|null jf2 for the item.
	 */
	public static function parse_item( $item, $mf, $args ) {
		$data = self::parse_item_by_type( $item, $mf, $args );
		$data = self::add_unknown_properties( $data, $item, $args );
		if ( is_array( $data ) && ! empty( $args['references'] ) ) {
			$data = jf2_references( $data );
		}
		return $data;
	}

	/**
	 * Fetches an author page and finds the author's h-card on it.
	 *
	 * Steps 7.1 to 7.3 of https://indieweb.org/authorship: an h-card whose url
	 * and uid are both the author page, else one whose url is a rel=me link on
	 * the page. Then step 7.4 (an h-card on the original page whose url is the
	 * author page), then the author page's own main item if it is a card.
	 *
	 * @since 2.0.0
	 *
	 * @param string $authorpage Author page URL.
	 * @param array  $mf2        Parsed mf2 document of the original page.
	 * @param array  $args       Parse arguments.
	 * @return array An h-card microformat, or jf2 for the author page's card.
	 */
	private static function author_from_page( $authorpage, $mf2, $args ) {
		$fallback = array(
			'type'       => array( 'h-card' ),
			'properties' => array( 'url' => array( $authorpage ) ),
		);
		$response = pt_remote_get( strtok( $authorpage, '#' ) );
		if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) >= 400 ) {
			// The author page is missing or an error: keep its URL.
			return self::find_card_by_url( $mf2, $authorpage ) ?? $fallback;
		}
		$body = wp_remote_retrieve_body( $response );
		$type = pt_sniff_content_type( strtok( (string) wp_remote_retrieve_header( $response, 'content-type' ), ';' ), $body );
		if ( 'application/mf2+json' === $type ) {
			$page = json_decode( $body, true );
		} else {
			if ( ! class_exists( 'Mf2\Parser' ) ) {
				require_once plugin_dir_path( __DIR__ ) . 'lib/mf2/Parser.php';
			}
			$page = ( new \Mf2\Parser( $body, $authorpage ) )->parse();
		}
		if ( ! is_array( $page ) || empty( $page['items'] ) ) {
			return self::find_card_by_url( $mf2, $authorpage ) ?? $fallback;
		}

		$cards  = self::find_all_cards( $page );
		$target = self::url_key( $authorpage );
		// 7.2 "if author-page has 1+ h-card with url == uid == author-page's URL, then use first such h-card"
		foreach ( $cards as $card ) {
			$urls = array_map( array( __CLASS__, 'url_key' ), array_filter( (array) ( $card['properties']['url'] ?? array() ), 'is_string' ) );
			$uids = array_map( array( __CLASS__, 'url_key' ), array_filter( (array) ( $card['properties']['uid'] ?? array() ), 'is_string' ) );
			if ( in_array( $target, $urls, true ) && in_array( $target, $uids, true ) ) {
				return self::card_with_url( $card, $authorpage );
			}
		}
		// 7.3 "else if author-page has 1+ h-card with url property which matches the href of a rel-me link on the author-page"
		$me = array_map( array( __CLASS__, 'url_key' ), array_filter( (array) ( $page['rels']['me'] ?? array() ), 'is_string' ) );
		if ( $me ) {
			foreach ( $cards as $card ) {
				$urls = array_map( array( __CLASS__, 'url_key' ), array_filter( (array) ( $card['properties']['url'] ?? array() ), 'is_string' ) );
				if ( array_intersect( $urls, $me ) ) {
					return self::card_with_url( $card, $authorpage );
				}
			}
		}
		// 7.4 "if the h-entry's page has 1+ h-card with url == author-page URL, use first such h-card"
		$card = self::find_card_by_url( $mf2, $authorpage );
		if ( $card ) {
			return $card;
		}
		// Otherwise the author page itself, if it is a card (a profile page).
		$args['follow'] = false;
		$args['return'] = 'single';
		$main           = self::parse( $page, $authorpage, $args );
		if ( is_array( $main ) && isset( $main['type'] ) && 'card' === $main['type'] ) {
			// A profile page's card is about the page, whatever its own url says.
			if ( empty( $main['url'] ) || ! is_string( $main['url'] ) || ! preg_match( '#^https?://#i', $main['url'] ) ) {
				$main['url'] = $authorpage;
			}
			return $main;
		}
		return $fallback;
	}

	/**
	 * Puts an author page URL first among a card's urls.
	 *
	 * A card can list several urls (an xmpp: address, say, before the web
	 * page), and the first one becomes its url, so the one that is the author
	 * page goes first; a card with no http(s) url gets the author page.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $card       h-card microformat.
	 * @param string $authorpage Author page URL.
	 * @return array The card with its urls reordered.
	 */
	private static function card_with_url( $card, $authorpage ) {
		$urls   = array_values( array_filter( (array) ( $card['properties']['url'] ?? array() ), 'is_string' ) );
		$target = self::url_key( $authorpage );
		$first  = null;
		foreach ( $urls as $i => $url ) {
			if ( self::url_key( $url ) === $target ) {
				$first = $url;
				unset( $urls[ $i ] );
				break;
			}
		}
		if ( null === $first ) {
			$web   = preg_grep( '#^https?://#i', $urls );
			$first = $web ? null : $authorpage;
		}
		if ( null !== $first ) {
			array_unshift( $urls, $first );
		}
		$card['properties']['url'] = array_values( $urls );
		return $card;
	}

	/**
	 * Finds the first h-card in a document whose url is a given URL.
	 *
	 * Looks at top-level items, items nested in their properties (such as an
	 * h-feed's author) and children.
	 *
	 * @since 2.0.0
	 *
	 * @param array|mixed $mf2 Parsed mf2 document.
	 * @param string      $url URL to match.
	 * @return array|null The h-card microformat, or null.
	 */
	private static function find_card_by_url( $mf2, $url ) {
		$target = self::url_key( $url );
		foreach ( self::find_all_cards( $mf2 ) as $card ) {
			foreach ( (array) ( $card['properties']['url'] ?? array() ) as $card_url ) {
				if ( is_string( $card_url ) && self::url_key( $card_url ) === $target ) {
					return $card;
				}
			}
		}
		return null;
	}

	/**
	 * Collects the h-cards in a document, at any depth.
	 *
	 * @since 2.0.0
	 *
	 * @param array|mixed $mf2 Parsed mf2 document, or a list of microformats.
	 * @return array[] h-card microformats, in document order, without their
	 *                 children.
	 */
	private static function find_all_cards( $mf2 ) {
		$items = ( is_array( $mf2 ) && isset( $mf2['items'] ) && is_array( $mf2['items'] ) ) ? $mf2['items'] : ( wp_is_numeric_array( $mf2 ) ? $mf2 : array() );
		$cards = array();
		foreach ( $items as $item ) {
			if ( ! self::is_microformat( $item ) ) {
				continue;
			}
			if ( self::is_type( $item, 'h-card' ) ) {
				// As an author, a card is just the card: what it contains (such as the
				// feed of the author's posts, whose entries point back to it) is not.
				$card = $item;
				unset( $card['children'] );
				$cards[] = $card;
			}
			foreach ( (array) $item['properties'] as $values ) {
				$cards = array_merge( $cards, self::find_all_cards( array_values( array_filter( (array) $values, array( __CLASS__, 'is_microformat' ) ) ) ) );
			}
			if ( isset( $item['children'] ) && is_array( $item['children'] ) ) {
				$cards = array_merge( $cards, self::find_all_cards( $item['children'] ) );
			}
		}
		return $cards;
	}

	/**
	 * Chooses the item a page with several top-level items is about.
	 *
	 * Follows XRay: breadcrumbs are ignored; if only one item is left once
	 * h-cards for other URLs are set aside, it is that one; otherwise an item
	 * whose url is the page; otherwise, when the page's rel=author matches an
	 * h-card on it (a sidebar profile), the first item that isn't a card; and
	 * when entries remain, the page is a feed. Failing those, an h-feed, then
	 * the first item of a known type.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $input Parsed mf2 document.
	 * @param string $url   URL of the page.
	 * @return array|string|null The chosen microformat, 'feed' if the page is a
	 *                           feed, or null if none stands out.
	 */
	private static function select_main_item( $input, $url ) {
		$page  = self::url_key( $url );
		$items = array_values(
			array_filter(
				$input['items'],
				function ( $item ) {
					return is_array( $item ) && ! self::is_type( $item, 'h-breadcrumb' );
				}
			)
		);

		// Set aside h-cards for other URLs: a sidebar or footer profile.
		$candidates = array_values(
			array_filter(
				$items,
				function ( $item ) use ( $page ) {
					$card_url = $item['properties']['url'][0] ?? null;
					return ! ( self::is_type( $item, 'h-card' ) && is_string( $card_url ) && self::url_key( $card_url ) !== $page );
				}
			)
		);
		if ( 1 === count( $candidates ) ) {
			return $candidates[0];
		}

		// An item whose url is this page.
		foreach ( $items as $item ) {
			foreach ( (array) ( $item['properties']['url'] ?? array() ) as $item_url ) {
				if ( is_string( $item_url ) && self::url_key( $item_url ) === $page ) {
					return $item;
				}
			}
		}

		// The page's author has an h-card here, so the first other item is the content.
		$authors = array_map( array( __CLASS__, 'url_key' ), array_filter( (array) ( $input['rels']['author'] ?? array() ), 'is_string' ) );
		if ( $authors ) {
			foreach ( $items as $card ) {
				if ( ! self::is_type( $card, 'h-card' ) ) {
					continue;
				}
				foreach ( (array) ( $card['properties']['url'] ?? array() ) as $card_url ) {
					if ( is_string( $card_url ) && in_array( self::url_key( $card_url ), $authors, true ) ) {
						foreach ( $items as $item ) {
							if ( ! self::is_type( $item, 'h-card' ) ) {
								return $item;
							}
						}
					}
				}
			}
		}

		// Several entries and nothing else to go on: a list of posts.
		foreach ( $candidates as $item ) {
			if ( self::is_type( $item, 'h-entry' ) ) {
				return 'feed';
			}
		}

		foreach ( $candidates as $item ) {
			if ( self::is_type( $item, 'h-feed' ) ) {
				return $item;
			}
		}
		foreach ( $candidates as $item ) {
			foreach ( array( 'h-entry', 'h-cite', 'h-event', 'h-review', 'h-recipe', 'h-product', 'h-item', 'h-app', 'h-x-app' ) as $type ) {
				if ( self::is_type( $item, $type ) ) {
					return $item;
				}
			}
		}
		return null;
	}

	/**
	 * Normalizes a URL for comparing it with a page's URL.
	 *
	 * Lowercases the host, adds a missing path, and drops the fragment and
	 * utm_* tracking parameters, which shared links often carry.
	 *
	 * @since 2.0.0
	 *
	 * @param string $url URL.
	 * @return string The comparable URL.
	 */
	private static function url_key( $url ) {
		$url   = strtok( (string) $url, '#' );
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( $query ) {
			wp_parse_str( $query, $params );
			foreach ( array_keys( $params ) as $key ) {
				if ( 0 === strpos( $key, 'utm_' ) ) {
					unset( $params[ $key ] );
				}
			}
			$url = strtok( $url, '?' );
			if ( $params ) {
				$url .= '?' . http_build_query( $params );
			}
		}
		return (string) normalize_url( $url );
	}

	/**
	 * Adds the properties of a microformat that no type parser reads.
	 *
	 * Microformats parsing doesn't depend on a vocabulary, so properties
	 * Parse This doesn't know (new or experimental ones, h-x-* extensions, an
	 * h-card's org or nickname) are kept, converted like any other property
	 * by get_prop_array(): one value as itself, several as a list, nested
	 * microformats as jf2. Properties in KNOWN_PROPERTIES, and any the type
	 * parser already set, are left alone. An h-card returned as the feed it
	 * contains gets nothing added.
	 *
	 * @since 2.0.0
	 *
	 * @param array|null $data jf2 from the type parser.
	 * @param array      $item The microformat it came from.
	 * @param array      $args Parse arguments (see Parser::parse()).
	 * @return array|null $data with the unknown properties added.
	 */
	private static function add_unknown_properties( $data, $item, $args ) {
		if ( ! is_array( $data ) || empty( $item['properties'] ) || ! is_array( $item['properties'] ) ) {
			return $data;
		}
		if ( isset( $data['type'] ) && 'feed' === $data['type'] && ! self::is_type( $item, 'h-feed' ) ) {
			return $data;
		}
		$unknown = array();
		foreach ( array_keys( $item['properties'] ) as $property ) {
			if ( is_string( $property ) && ! in_array( $property, self::KNOWN_PROPERTIES, true ) && ! array_key_exists( $property, $data ) ) {
				$unknown[] = $property;
			}
		}
		if ( $unknown ) {
			$data = array_merge( $data, self::filter_empty( self::get_prop_array( $item, $unknown, $args ) ) );
		}
		return $data;
	}

	/**
	 * Converts a microformat into jf2 with the parser for its type.
	 *
	 * @since 2.0.0
	 *
	 * @param array $item Microformat.
	 * @param array $mf   Parsed mf2 document.
	 * @param array $args Parse arguments (see Parser::parse()).
	 * @return array|null jf2 for the item.
	 */
	private static function parse_item_by_type( $item, $mf, $args ) {
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
			return self::parse_hgeo( $item, $mf, $args );
		} elseif ( self::is_type( $item, 'h-measure' ) ) {
			return self::parse_hmeasure( $item, $mf, $args );
		}
		return self::parse_hunknown( $item, $mf, $args );
	}

	/**
	 * Checks whether one string starts with another, ignoring surrounding whitespace.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string1 String to check.
	 * @param string $string2 Prefix to look for.
	 * @return bool True if $string1 starts with $string2. False if either is empty.
	 */
	public static function compare( $string1, $string2 ) {
		if ( empty( $string1 ) || empty( $string2 ) ) {
			return false;
		}
		$string1 = trim( $string1 );
		$string2 = trim( $string2 );
		return ( 0 === strpos( $string1, $string2 ) );
	}

	/**
	 * Converts a microformat of an unrecognized type into jf2.
	 *
	 * Microformats2 parsing is vocabulary-agnostic, so any h-* root gets the
	 * generic properties from parse_h() and its type without the h- prefix,
	 * as jf2 does for known types (h-org becomes org).
	 *
	 * @since 1.0.0
	 *
	 * @param array $unknown Microformat.
	 * @param array $mf      Parsed mf2 document.
	 * @param array $args    Parse arguments (see Parser::parse()).
	 * @return array jf2 with the generic properties and the type, or an empty
	 *               array if the type isn't h-* or no properties were found.
	 */
	public static function parse_hunknown( $unknown, $mf, $args ) {
		$type = $unknown['type'][0];
		if ( ! is_string( $type ) || 0 !== strpos( $type, 'h-' ) || 'h-' === $type ) {
			return array();
		}
		// Parse unknown h property.
		$data = self::parse_h( $unknown, $mf, $args );
		if ( empty( $data ) ) {
			return array();
		}
		$data['type'] = substr( $type, 2 );

		return $data;
	}

	/**
	 * Returns the properties common to most microformat types.
	 *
	 * Reads name, published, updated, url, author, content and summary, drops
	 * the name when it only repeats the start of the content (see
	 * name_is_content_prefix()), and adds the document's rel=syndication links.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Drops a name that is a prefix of the content, including a
	 *              truncated one, not only one that contains all of it.
	 *
	 * @param array $entry Microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array jf2 properties.
	 */
	public static function parse_h( $entry, $mf, $args ) {
		$data              = array();
		$data['name']      = self::get_plaintext( $entry, 'name' );
		$data['published'] = self::get_published( $entry, true, null );
		$data['updated']   = self::get_updated( $entry, true, null );
		$data['url']       = normalize_url( self::get_plaintext( $entry, 'url' ) );
		$author            = self::find_author( $entry, $mf, $args['follow'], $args );
		if ( self::is_microformat( $author ) ) {
			$data['author'] = self::parse_hcard( $author, $mf, $args, $data['url'] );
		} else {
			$data['author'] = $author;
		}
		$data['content'] = self::parse_html_value( $entry, 'content' );
		$data['summary'] = self::get_summary( $entry, $data['content'] );

		// A name that only repeats the start of the content (often implied, or
		// truncated with an ellipsis) is not a title.
		if ( is_array( $data['content'] ) && array_key_exists( 'text', $data['content'] ) ) {
			if ( name_is_content_prefix( $data['name'], $data['content']['text'] ) ) {
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
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-measure into jf2.
	 *
	 * @since 1.0.0
	 *
	 * @param array $measure h-measure microformat.
	 * @param array $mf      Parsed mf2 document. Unused.
	 * @param array $args    Parse arguments. Unused.
	 * @return array jf2 with type 'measure', num and unit.
	 */
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
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-leg (a leg of a trip) into jf2.
	 *
	 * @since 1.0.0
	 *
	 * @param array $leg  h-leg microformat.
	 * @param array $mf   Parsed mf2 document. Unused.
	 * @param array $args Parse arguments. Unused.
	 * @return array jf2 with url, name, origin, destination, operator, transit-type,
	 *               number, departure and arrival where present.
	 */
	public static function parse_hleg( $leg, $mf, $args ) {
		// The aaronpk special.
		$data       = array(
			'type' => 'leg',
		);
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

		foreach ( array( 'departure', 'arrival' ) as $property ) {
			$datetime = self::get_datetime_property( $property, $leg, true, null );
			if ( $datetime instanceof \DateTimeInterface ) {
				$data[ $property ] = $datetime->format( DATE_W3C );
			}
		}
		$data              = self::filter_empty( $data );
		return $data;
	}

	/**
	 * Converts an h-entry or h-cite into jf2.
	 *
	 * Reads the response properties (in-reply-to, like-of, repost-of and so on),
	 * media, location and check-in data, then the common properties from
	 * parse_h(). Adds the post type from post_type_discovery() as 'post-type'.
	 *
	 * Reviews may be published as an h-entry with review-of (a URL, or a nested
	 * h-cite, h-card, h-event, h-item or h-product) and rating, best and worst,
	 * as proposed in microformats/h-entry#32, or as an h-entry that is also an
	 * h-review (which keeps h-review's item). Both get the post type review.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Reads review-of, rating, best and worst, and recognizes
	 *              h-entry h-review.
	 *
	 * @param array $entry h-entry or h-cite microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array jf2 with type 'entry' or 'cite'.
	 */
	public static function parse_hentry( $entry, $mf, $args ) {
		// Array Values.
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
			'follow-of',
			'listen-of',
			'quotation-of',
			'review-of',
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
		$data         = self::get_prop_array( $entry, $properties, $args );
		$data['type'] = self::is_type( $entry, 'h-entry' ) ? 'entry' : 'cite';
		$properties   = array( 'url', 'weather', 'temperature', 'rsvp', 'featured', 'swarm-coins', 'latitude', 'longitude', 'rating', 'best', 'worst' );
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $entry, $property );
		}
		$data = self::filter_empty( $data );
		// rsvp values are an enumeration (yes, no, maybe, interested); compare without case.
		if ( isset( $data['rsvp'] ) && is_string( $data['rsvp'] ) ) {
			$data['rsvp'] = strtolower( trim( $data['rsvp'] ) );
		}
		$data              = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		$data['post-type'] = post_type_discovery( $data );
		// Published as both h-entry and h-review, for consumers of either (microformats/h-entry#32).
		if ( 'entry' === $data['type'] && self::is_type( $entry, 'h-review' ) ) {
			$data['post-type'] = 'review';
		}
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-card into jf2.
	 *
	 * When $args['return'] is 'feed' and the card's first child is an h-feed
	 * (as on sites that nest their feed inside their h-card), that feed is
	 * returned with the card as its author.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $hcard h-card microformat.
	 * @param array       $mf    Parsed mf2 document.
	 * @param array       $args  Parse arguments (see Parser::parse()).
	 * @param string|bool $url   Optional. Unused.
	 * @return array|null jf2 card (or feed, see above), or null if $hcard is not a
	 *                     microformat.
	 */
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
			'honorific-prefix',
			'additional-name',
			'family-name',
			'honorific-suffix',
			'email',
			'postal-code',
			'altitude',
			'location',
		);
		foreach ( $properties as $property ) {
			$data[ $property ] = self::get_plaintext( $hcard, $property );
		}
		$data = self::filter_empty( $data );
		$data = array_merge( self::get_prop_array( $hcard, array_keys( $hcard['properties'] ), $args ), $data );

		$data['type'] = 'card';
		if ( isset( $hcard['children'] ) ) {
			// In the case of sites like tantek.com where multiple feeds are nested inside h-card if it is a feed request return only the first feed.
			if ( 'feed' === $args['return'] && self::is_type( $hcard['children'][0], 'h-feed' ) ) {
				$feed = self::parse_hfeed( $hcard['children'][0], $mf, $args );
				unset( $data['children'] );
				$feed['author'] = $data;
				return self::filter_empty( $feed );
			} else {
				$data['items'] = self::parse_children( $hcard['children'], $mf, $args );
			}
		}
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-event into jf2.
	 *
	 * Reads category, attendee, organizer, location, start, end, duration,
	 * photo, video, audio, featured, syndication, uid and url, plus the common
	 * properties from parse_h(). Without content, description is used, as in
	 * older markup. Adds post-type.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Reads duration, video, audio, featured, syndication and
	 *              description, and adds post-type.
	 *
	 * @param array $event h-event microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array|null jf2 properties, or null if the input is not a microformat.
	 */
	public static function parse_hevent( $event, $mf, $args ) {
		if ( ! self::is_microformat( $event ) ) {
			return;
		}
		$data       = array(
			'type' => 'event',
		);
		$data       = array_merge( $data, self::parse_h( $event, $mf, $args ) );
		$properties = array( 'category', 'attendee', 'organizer', 'location', 'start', 'end', 'duration', 'photo', 'video', 'audio', 'featured', 'syndication', 'uid', 'url' );
		$data       = array_merge( $data, self::get_prop_array( $event, $properties, $args ) );
		// Older markup describes an event with description rather than content.
		if ( empty( $data['content'] ) ) {
			$data['content'] = self::parse_html_value( $event, 'description' );
			if ( empty( $data['summary'] ) && is_array( $data['content'] ) && isset( $data['content']['text'] ) ) {
				$data['summary'] = self::get_summary( $event, $data['content'] );
			}
		}
		$data              = self::filter_empty( $data );
		$data['post-type'] = post_type_discovery( $data );
		return $data;
	}

	/**
	 * Converts an h-review into jf2.
	 *
	 * Reads category, item, the media and syndication properties, the
	 * responses (in-reply-to, like-of, repost-of, bookmark-of), summary,
	 * published, rating, best and worst, plus the common properties from
	 * parse_h(). Without content, description is used, as in hReview markup.
	 * Adds post-type.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Reads media, syndication, responses and description, and
	 *              adds post-type.
	 *
	 * @param array $entry h-review microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array|null jf2 properties, or null if the input is not a microformat.
	 */
	public static function parse_hreview( $entry, $mf, $args ) {
		if ( ! self::is_microformat( $entry ) ) {
			return;
		}
		$data       = array(
			'type' => 'review',
			'name' => null,
			'url'  => null,
		);
		// A review is often also a reply to, or a like of, what it reviews.
		$properties = array( 'category', 'item', 'photo', 'video', 'audio', 'syndication', 'in-reply-to', 'like-of', 'repost-of', 'bookmark-of' );
		$data       = array_merge( $data, self::get_prop_array( $entry, $properties, $args ) );
		$properties = array( 'summary', 'published', 'rating', 'best', 'worst' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		// Older (hReview) markup uses description for the review text.
		if ( empty( $data['content'] ) ) {
			$data['content'] = self::parse_html_value( $entry, 'description' );
		}
		$data              = self::filter_empty( $data );
		$data['post-type'] = post_type_discovery( $data );
		return $data;
	}


	/**
	 * Converts an h-product into jf2.
	 *
	 * Reads category, brand, photo, audio, video, identifier, price and description, plus the common properties from parse_h().
	 *
	 * @since 1.0.0
	 *
	 * @param array $entry h-product microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array|null jf2 properties, or null if the input is not a microformat.
	 */
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
		$data       = array_merge( $data, self::get_prop_array( $entry, $properties, $args ) );
		$properties = array( 'identifier', 'price', 'description' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return self::filter_empty( $data );
	}


	/**
	 * Converts an h-resume into jf2.
	 *
	 * Reads category and item, plus the common properties from parse_h().
	 *
	 * @since 1.0.0
	 *
	 * @param array $entry h-resume microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array|null jf2 properties, or null if the input is not a microformat.
	 */
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
		$data       = array_merge( $data, self::get_prop_array( $entry, $properties, $args ) );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-listing into jf2.
	 *
	 * Reads category and item, plus the common properties from parse_h().
	 *
	 * @since 1.0.0
	 *
	 * @param array $entry h-listing microformat.
	 * @param array $mf    Parsed mf2 document.
	 * @param array $args  Parse arguments (see Parser::parse()).
	 * @return array|null jf2 properties, or null if the input is not a microformat.
	 */
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
		$data       = array_merge( $data, self::get_prop_array( $entry, $properties, $args ) );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $entry, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $entry, $mf, $args ) );
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-recipe into jf2.
	 *
	 * Reads category, item, ingredient, photo, video, yield, duration,
	 * nutrition and instructions (rich text), plus the common properties from
	 * parse_h(). Adds post-type.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Reads ingredient, photo, video, yield, duration, nutrition
	 *              and instructions, and adds post-type.
	 *
	 * @param array $recipe h-recipe microformat.
	 * @param array $mf     Parsed mf2 document.
	 * @param array $args   Parse arguments (see Parser::parse()).
	 * @return array|null jf2 properties, or null if the input is not a microformat.
	 */
	public static function parse_hrecipe( $recipe, $mf, $args ) {
		if ( ! self::is_microformat( $recipe ) ) {
			return;
		}
		$data       = array(
			'type' => 'recipe',
			'name' => null,
			'url'  => null,
		);
		$properties = array( 'category', 'item', 'ingredient', 'photo', 'video' );
		$data       = array_merge( $data, self::get_prop_array( $recipe, $properties, $args ) );
		$properties = array( 'yield', 'duration', 'nutrition' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $recipe, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data                 = array_merge( $data, self::parse_h( $recipe, $mf, $args ) );
		$data['instructions'] = self::parse_html_value( $recipe, 'instructions' );
		$data                 = self::filter_empty( $data );
		$data['post-type']    = post_type_discovery( $data );
		return $data;
	}

	/**
	 * Converts an h-item into jf2.
	 *
	 * Reads category and item, plus the common properties from parse_h().
	 *
	 * @since 1.0.0
	 *
	 * @param array $item h-item microformat.
	 * @param array $mf   Parsed mf2 document.
	 * @param array $args Parse arguments (see Parser::parse()).
	 * @return array|null jf2 properties, or null if the input is not a microformat.
	 */
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
		$data       = array_merge( $data, self::get_prop_array( $item, $properties, $args ) );
		$properties = array();
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $item, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		$data = array_merge( $data, self::parse_h( $item, $mf, $args ) );
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-adr into jf2.
	 *
	 * @since 1.0.0
	 *
	 * @param array $hadr h-adr microformat.
	 * @param array $mf   Parsed mf2 document. Unused.
	 * @param array $args Parse arguments. Unused.
	 * @return array|null jf2 with type 'adr' and the address and geo properties present,
	 *                     or null if the input is not a microformat.
	 */
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
		$props      = self::get_prop_array( $hadr, $properties, $args );
		$data       = array_merge( $data, $props );
		return self::filter_empty( $data );
	}

	/**
	 * Converts an h-geo into jf2.
	 *
	 * @since 1.0.0
	 *
	 * @param array $hgeo h-geo microformat.
	 * @param array $mf   Parsed mf2 document. Unused.
	 * @param array $args Parse arguments. Unused.
	 * @return array|null jf2 with type 'geo', latitude, longitude and altitude, or null
	 *                     if the input is not a microformat.
	 */
	public static function parse_hgeo( $hgeo, $mf, $args ) {
		if ( ! self::is_microformat( $hgeo ) ) {
			return;
		}
		$data       = array(
			'type' => 'geo',
		);
		$properties = array( 'latitude', 'longitude', 'altitude' );
		foreach ( $properties as $p ) {
			$v = self::get_plaintext( $hgeo, $p );
			if ( null !== $v ) {
				$data[ $p ] = $v;
			}
		}
		return self::filter_empty( $data );
	}
}
