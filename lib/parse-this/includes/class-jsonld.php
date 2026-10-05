<?php
/**
 * JSONLD class.
 *
 * @package Parse_This
 */

namespace ParseThis;

defined( 'ABSPATH' ) || exit;

/**
 * Converts schema.org JSON-LD embedded in HTML pages into jf2.
 *
 * Recognizes articles and web pages, people, organizations, web sites,
 * events, images, audio, video, music releases, movies and TV, places and
 * postal addresses.
 *
 * @since 1.0.0
 */
class JSONLD extends Base {

	/**
	 * Schema.org Article and its subtypes, read as entries.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const ARTICLE_TYPES = array( 'Article', 'AdvertiserContentArticle', 'NewsArticle', 'AnalysisNewsArticle', 'AskPublicNewsArticle', 'BackgroundNewsArticle', 'OpinionNewsArticle', 'ReportageNewsArticle', 'ReviewNewsArticle', 'Report', 'SatiricalArticle', 'ScholarlyArticle', 'MedicalScholarlyArticle', 'SocialMediaPosting', 'BlogPosting', 'LiveBlogPosting', 'DiscussionForumPosting', 'TechArticle', 'APIReference' );

	/**
	 * Schema.org WebPage and its subtypes. Read as an entry only when nothing
	 * more specific describes the page, since SEO plugins add one to every page.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const WEBPAGE_TYPES = array( 'WebPage', 'AboutPage', 'CheckoutPage', 'CollectionPage', 'ContactPage', 'FAQPage', 'ItemPage', 'MedicalWebPage', 'QAPage', 'RealEstateListing', 'SearchResultsPage' );

	/**
	 * Schema.org Review and its subtypes.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const REVIEW_TYPES = array( 'Review', 'CriticReview', 'UserReview', 'EmployerReview', 'MediaReview', 'Recommendation', 'ClaimReview' );

	/**
	 * Schema.org Product and its subtypes.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const PRODUCT_TYPES = array( 'Product', 'ProductModel', 'ProductGroup', 'IndividualProduct', 'SomeProducts' );

	/**
	 * Schema.org Event and its subtypes.
	 *
	 * @since 2.0.0
	 * @var string[]
	 */
	const EVENT_TYPES = array( 'Event', 'BusinessEvent', 'ChildrensEvent', 'ComedyEvent', 'CourseInstance', 'DanceEvent', 'DeliveryEvent', 'EducationEvent', 'EventSeries', 'ExhibitionEvent', 'Festival', 'FoodEvent', 'Hackathon', 'LiteraryEvent', 'MusicEvent', 'PublicationEvent', 'BroadcastEvent', 'OnDemandEvent', 'SaleEvent', 'ScreeningEvent', 'SocialEvent', 'SportsEvent', 'TheaterEvent', 'VisualArtsEvent' );
	/**
	 * Parses every application/ld+json script in a document into jf2.
	 *
	 * A top-level @graph is unwrapped before conversion.
	 *
	 * @since 1.0.0
	 *
	 * @param DOMDocument|null $doc  Parsed HTML document.
	 * @param string           $url  URL of the page. Unused.
	 * @param array            $args Parse arguments (see Parser::parse()).
	 * @return array jf2 properties. When $args['debug'] is set, the decoded JSON-LD is included
	 *               under '_jsonld'.
	 */
	public static function parse( $doc, $url, $args ) {
		if ( ! $doc ) {
			return array();
		}
		$xpath = new \DOMXPath( $doc );

		$jsonld  = array();
		$content = '';
		foreach ( $xpath->query( "//script[@type='application/ld+json']" ) as $script ) {
			$content  = $script->textContent; // phpcs:ignore
			$jsonld[] = json_decode( $content, true );
		}
		$jsonld = array_filter( $jsonld );
		if ( 1 === count( $jsonld ) && wp_is_numeric_array( $jsonld ) ) {
			$jsonld = $jsonld[0];
		}
		if ( ! wp_is_numeric_array( $jsonld ) && ! self::is_jsonld( $jsonld ) && self::is_jsonld_graph( $jsonld ) ) {
			$jsonld = $jsonld['@graph'];
		}

		if ( self::is_jsonld( $jsonld ) ) {
			$jsonld = array( $jsonld );
		}

		$jf2 = self::jsonld_to_jf2( $jsonld );
		if ( ! empty( $args['debug'] ) ) {
			$jf2['_jsonld'] = $jsonld;
		}
		return array_filter( $jf2, array( __CLASS__, 'is_set' ) );
	}

	/**
	 * Converts a list of JSON-LD nodes into a single jf2 object.
	 *
	 * The most specific node describes the page: a review, recipe, event or
	 * product, then an article
	 * (with any video, audio, author and organization nodes merged into it),
	 * then video, audio or media, then a web page, then a person. A more
	 * specific node is filled in from the article or web page (author, dates,
	 * publisher, image).
	 *
	 * @since 1.0.0
	 * @since 2.0.0 Prefers reviews, recipes, events and products over articles
	 *              and web page nodes, and recognizes their subtypes.
	 *
	 * @param array $jsonld List of JSON-LD nodes.
	 * @return array jf2 properties, or the nodes converted so far keyed by type when
	 *               none of the preferred types is present.
	 */
	public static function jsonld_to_jf2( $jsonld ) {
		if ( empty( $jsonld ) ) {
			return array();
		}
		$jf2 = array();
		foreach ( $jsonld as $json ) {
			$type = self::get_type( $json );
			switch ( $type ) {
				case 'review':
					$jf2['review'] = self::review_to_hentry( $json );
					break;
				case 'recipe':
					$jf2['recipe'] = self::recipe_to_hrecipe( $json );
					break;
				case 'product':
					$jf2['product'] = self::product_to_hproduct( $json );
					break;
				case 'webpage':
					$jf2['webpage'] = array_merge( $jf2['webpage'] ?? array(), (array) self::article_to_hentry( $json ) );
					break;
				case 'entry':
					if ( ! array_key_exists( 'entry', $jf2 ) ) {
						$jf2['entry'] = self::article_to_hentry( $json );
					} else {
						$jf2['entry'] = array_merge( $jf2['entry'], self::article_to_hentry( $json ) );
					}
					break;
				case 'person':
					$jf2['person'] = self::person_to_hcard( $json );
					break;
				case 'org':
					if ( ! array_key_exists( 'org', $jf2 ) ) {
						$jf2['org'] = self::organization_to_hcard( $json );
					} else {
						$jf2['org'] = array_merge( $jf2['org'], self::organization_to_hcard( $json ) );
					}
					break;
				case 'site':
					$jf2['site'] = self::site_to_hcard( $json );
					break;
				case 'event':
					$jf2['event'] = self::event_to_hevent( $json );
					break;
				case 'image':
					$jf2['image'] = self::image_to_photo( $json );
					break;
				case 'audio':
					$jf2['audio'] = self::audio_to_audio( $json );
					break;
				case 'video':
					$jf2['video'] = self::video_to_video( $json );
					break;
				case 'music':
					$jf2['music'] = self::music_to_hcite( $json );
					break;
				case 'media':
					$jf2['media'] = self::media_to_hcite( $json );
					break;
				case 'place':
					$jf2['place'] = self::place_to_hcard( $json );
					break;
			}
		}
		$return = null;
		// The most specific description of the page wins; an article or web page
		// node only fills in what it leaves out (author, dates, publisher).
		$page = $jf2['entry'] ?? ( $jf2['webpage'] ?? array() );
		foreach ( array( 'review', 'recipe', 'event', 'product' ) as $specific ) {
			if ( ! empty( $jf2[ $specific ] ) ) {
				$return = $jf2[ $specific ];
				foreach ( array( 'author', 'published', 'updated', 'publication', 'featured' ) as $key ) {
					if ( ! isset( $return[ $key ] ) && isset( $page[ $key ] ) ) {
						$return[ $key ] = $page[ $key ];
					}
				}
				break;
			}
		}
		if ( null === $return ) {
			$return = self::choose_general( $jf2 );
			if ( null === $return ) {
				return $jf2;
			}
		}

		if ( ! array_key_exists( 'author', $return ) && array_key_exists( 'person', $jf2 ) ) {
			$return['author'] = $jf2['person'];
		}
		if ( ! array_key_exists( 'publication', $return ) && array_key_exists( 'publisher', $jf2 ) ) {
			$return['publication'] = $jf2['publisher'];
		}
		return array_filter( $return, array( __CLASS__, 'is_set' ) );
	}

	/**
	 * Chooses among general JSON-LD nodes when no specific one describes the page.
	 *
	 * An article (with video, audio, author and organization merged in), then
	 * video, audio or media, then a web page, then a person.
	 *
	 * @since 2.0.0
	 *
	 * @param array $jf2 Converted nodes keyed by type.
	 * @return array|null The chosen jf2, or null if there is none.
	 */
	private static function choose_general( $jf2 ) {
		$return = null;
		if ( array_key_exists( 'entry', $jf2 ) ) {
			// The web page node fills in what the article leaves out.
			$return = $jf2['entry'] + ( $jf2['webpage'] ?? array() );
			if ( array_key_exists( 'video', $jf2 ) ) {
				$return = array_merge( $return, $jf2['video'] );
			}
			if ( array_key_exists( 'audio', $jf2 ) ) {
				$return = array_merge( $return, $jf2['audio'] );
			}
			if ( array_key_exists( 'person', $jf2 ) ) {
				$return['author'] = $jf2['person'];
			}
			if ( array_key_exists( 'org', $jf2 ) ) {
				$return['org'] = $jf2['org'];
			}
		} elseif ( array_key_exists( 'video', $jf2 ) ) {
			$return = $jf2['video'];
		} elseif ( array_key_exists( 'audio', $jf2 ) ) {
			$return = $jf2['audio'];
		} elseif ( array_key_exists( 'media', $jf2 ) ) {
			$return = $jf2['media'];
		} elseif ( array_key_exists( 'webpage', $jf2 ) ) {
			$return = $jf2['webpage'];
			if ( array_key_exists( 'person', $jf2 ) && ! isset( $return['author'] ) ) {
				$return['author'] = $jf2['person'];
			}
		} elseif ( array_key_exists( 'person', $jf2 ) ) {
			$return = $jf2['person'];
		}
		return $return;
	}

	/**
	 * Converts a Review node into a jf2 entry with review-of.
	 *
	 * Follows the h-entry review form Post Kinds publishes (microformats/h-entry#32):
	 * the reviewed item goes in review-of, and the rating in rating, best and
	 * worst, so the post type is review.
	 *
	 * @since 2.0.0
	 *
	 * @param array $review Review node.
	 * @return array|false jf2 entry, or false if the node is not a review.
	 */
	public static function review_to_hentry( $review ) {
		if ( 'review' !== self::get_type( $review ) ) {
			return false;
		}
		$rating = ( isset( $review['reviewRating'] ) && is_array( $review['reviewRating'] ) ) ? $review['reviewRating'] : array();
		$body   = $review['reviewBody'] ?? null;
		$jf2    = array(
			'type'      => 'entry',
			'name'      => $review['name'] ?? ( $review['headline'] ?? null ),
			'url'       => $review['url'] ?? null,
			'summary'   => $review['description'] ?? null,
			'published' => normalize_iso8601( $review['datePublished'] ?? null ),
			'updated'   => normalize_iso8601( $review['dateModified'] ?? null ),
			'author'    => self::agent_to_hcard( $review['author'] ?? null ),
			'review-of' => self::item_to_jf2( $review['itemReviewed'] ?? null ),
			'rating'    => self::scalar( $rating['ratingValue'] ?? null ),
			'best'      => self::scalar( $rating['bestRating'] ?? null ),
			'worst'     => self::scalar( $rating['worstRating'] ?? null ),
		);
		if ( is_string( $body ) && '' !== trim( $body ) ) {
			$jf2['content'] = array(
				'html' => Parser::clean_content( $body ),
				'text' => trim( wp_strip_all_tags( $body ) ),
			);
		}
		$jf2              = array_filter( $jf2, array( __CLASS__, 'is_set' ) );
		$jf2['post-type'] = post_type_discovery( $jf2 );
		return $jf2;
	}

	/**
	 * Converts a Product node into a jf2 product.
	 *
	 * @since 2.0.0
	 *
	 * @param array $product Product node.
	 * @return array|false jf2 product (name, url, photo, description, brand,
	 *                     identifier, price, category), or false if the node is
	 *                     not a product.
	 */
	public static function product_to_hproduct( $product ) {
		if ( 'product' !== self::get_type( $product ) ) {
			return false;
		}
		$brand = $product['brand'] ?? null;
		if ( is_array( $brand ) && ! wp_is_numeric_array( $brand ) ) {
			$brand = $brand['name'] ?? null;
		}
		$offer = $product['offers'] ?? null;
		if ( is_array( $offer ) && wp_is_numeric_array( $offer ) ) {
			$offer = reset( $offer );
		}
		$price = null;
		if ( is_array( $offer ) && isset( $offer['price'] ) ) {
			$price = trim( self::scalar( $offer['price'] ) . ' ' . ( $offer['priceCurrency'] ?? '' ) );
		} elseif ( is_array( $offer ) && isset( $offer['lowPrice'] ) ) {
			$price = trim( self::scalar( $offer['lowPrice'] ) . ' ' . ( $offer['priceCurrency'] ?? '' ) );
		}
		$jf2 = array(
			'type'        => 'product',
			'name'        => $product['name'] ?? null,
			'url'         => $product['url'] ?? null,
			'photo'       => self::image_to_photo( $product['image'] ?? null ),
			'description' => $product['description'] ?? null,
			'brand'       => is_string( $brand ) ? $brand : null,
			'identifier'  => $product['sku'] ?? ( $product['gtin13'] ?? ( $product['gtin'] ?? ( $product['mpn'] ?? null ) ) ),
			'price'       => $price,
			'category'    => $product['category'] ?? null,
		);
		return array_filter( $jf2, array( __CLASS__, 'is_set' ) );
	}

	/**
	 * Converts a Recipe node into a jf2 recipe.
	 *
	 * Uses the same property names as h-recipe: ingredient, yield, duration,
	 * nutrition and instructions (rich text).
	 *
	 * @since 2.0.0
	 *
	 * @param array $recipe Recipe node.
	 * @return array|false jf2 recipe, or false if the node is not a recipe.
	 */
	public static function recipe_to_hrecipe( $recipe ) {
		if ( 'recipe' !== self::get_type( $recipe ) ) {
			return false;
		}
		$yield = $recipe['recipeYield'] ?? null;
		if ( is_array( $yield ) ) {
			$yield = reset( $yield );
		}
		$nutrition = $recipe['nutrition'] ?? null;
		if ( is_array( $nutrition ) ) {
			$nutrition = $nutrition['calories'] ?? null;
		}
		$category         = array_merge( (array) ( $recipe['recipeCategory'] ?? array() ), (array) ( $recipe['recipeCuisine'] ?? array() ) );
		$jf2              = array(
			'type'         => 'recipe',
			'name'         => $recipe['name'] ?? null,
			'url'          => $recipe['url'] ?? null,
			'summary'      => $recipe['description'] ?? null,
			'photo'        => self::image_to_photo( $recipe['image'] ?? null ),
			'published'    => normalize_iso8601( $recipe['datePublished'] ?? null ),
			'author'       => self::agent_to_hcard( $recipe['author'] ?? null ),
			'ingredient'   => array_values( array_filter( (array) ( $recipe['recipeIngredient'] ?? ( $recipe['ingredients'] ?? array() ) ), 'is_string' ) ),
			'yield'        => self::scalar( $yield ),
			'duration'     => $recipe['totalTime'] ?? ( $recipe['cookTime'] ?? null ),
			'nutrition'    => self::scalar( $nutrition ),
			'instructions' => self::instructions( $recipe['recipeInstructions'] ?? null ),
			'category'     => array_values( array_filter( $category, 'is_string' ) ),
		);
		$jf2              = array_filter( $jf2, array( __CLASS__, 'is_set' ) );
		$jf2['post-type'] = post_type_discovery( $jf2 );
		return $jf2;
	}

	/**
	 * Converts recipe instructions (text, HowToStep or HowToSection nodes) to rich text.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $instructions recipeInstructions value.
	 * @return array|null Array with html (an ordered list) and text, or null.
	 */
	private static function instructions( $instructions ) {
		if ( is_string( $instructions ) ) {
			$instructions = '' === trim( $instructions ) ? array() : array( $instructions );
		}
		$steps = array();
		foreach ( (array) $instructions as $step ) {
			if ( is_string( $step ) ) {
				$steps[] = $step;
			} elseif ( is_array( $step ) && isset( $step['itemListElement'] ) ) {
				// A HowToSection: its steps, in order.
				foreach ( (array) $step['itemListElement'] as $inner ) {
					$text = is_array( $inner ) ? ( $inner['text'] ?? ( $inner['name'] ?? null ) ) : $inner;
					if ( is_string( $text ) ) {
						$steps[] = $text;
					}
				}
			} elseif ( is_array( $step ) ) {
				$text = $step['text'] ?? ( $step['name'] ?? null );
				if ( is_string( $text ) ) {
					$steps[] = $text;
				}
			}
		}
		$steps = array_values( array_filter( array_map( 'trim', array_map( 'wp_strip_all_tags', $steps ) ) ) );
		if ( ! $steps ) {
			return null;
		}
		return array(
			'html' => '<ol><li>' . implode( '</li><li>', array_map( 'esc_html', $steps ) ) . '</li></ol>',
			'text' => implode( "\n", $steps ),
		);
	}

	/**
	 * Converts whatever a review is of into jf2.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $item itemReviewed value: a URL, or a node of any type.
	 * @return array|string|null jf2 for the item, its URL, or null.
	 */
	private static function item_to_jf2( $item ) {
		if ( is_string( $item ) ) {
			return $item;
		}
		if ( ! is_array( $item ) ) {
			return null;
		}
		if ( wp_is_numeric_array( $item ) ) {
			$item = reset( $item );
		}
		switch ( self::get_type( $item ) ) {
			case 'event':
				return self::event_to_hevent( $item );
			case 'media':
				return self::media_to_hcite( $item );
			case 'product':
				return self::product_to_hproduct( $item );
			case 'recipe':
				return self::recipe_to_hrecipe( $item );
			case 'place':
				return self::place_to_hcard( $item );
			case 'org':
				return self::organization_to_hcard( $item );
			case 'person':
				return self::person_to_hcard( $item );
		}
		// Anything else (a Book, a LocalBusiness, a CreativeWork): a citation.
		$cite = array(
			'type'   => 'cite',
			'name'   => $item['name'] ?? null,
			'url'    => $item['url'] ?? ( $item['sameAs'] ?? null ),
			'author' => self::agent_to_hcard( $item['author'] ?? null ),
			'uid'    => $item['isbn'] ?? null,
		);
		$cite = array_filter( $cite, array( __CLASS__, 'is_set' ) );
		return count( $cite ) > 1 ? $cite : null;
	}

	/**
	 * Converts an author or creator value (a person, organization or name) into a card.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $agent author value.
	 * @return array|null jf2 card, or null.
	 */
	private static function agent_to_hcard( $agent ) {
		if ( is_array( $agent ) && wp_is_numeric_array( $agent ) ) {
			$agent = reset( $agent );
		}
		if ( is_string( $agent ) && '' !== trim( $agent ) ) {
			return array(
				'type' => 'card',
				'name' => trim( $agent ),
			);
		}
		if ( ! is_array( $agent ) ) {
			return null;
		}
		$card = ( 'org' === self::get_type( $agent ) ) ? self::organization_to_hcard( $agent ) : self::person_to_hcard( $agent );
		if ( ! $card && isset( $agent['name'] ) && is_string( $agent['name'] ) ) {
			$card = array(
				'type' => 'card',
				'name' => $agent['name'],
			);
		}
		return $card ? $card : null;
	}

	/**
	 * Returns a number or string value as a string.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Value.
	 * @return string|null The value as a string, or null for anything else.
	 */
	private static function scalar( $value ) {
		if ( is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}
		return ( is_string( $value ) && '' !== trim( $value ) ) ? trim( $value ) : null;
	}

	/**
	 * Checks whether a value is worth keeping (keeps "0", unlike array_filter()).
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Value.
	 * @return bool False for null, false, '' and empty arrays.
	 */
	private static function is_set( $value ) {
		return null !== $value && false !== $value && '' !== $value && array() !== $value;
	}

	/**
	 * Converts a MusicRelease node into a jf2 cite.
	 *
	 * @since 1.0.0
	 *
	 * @param array $music MusicRelease node.
	 * @return array|false jf2 cite including its artists and tracks, or false if the node
	 *                     is not a MusicRelease.
	 */
	public static function music_to_hcite( $music ) {
		if ( 'music' !== self::get_type( $music ) ) {
			return false;
		}

		$return = array(
			'type'      => 'cite',
			'name'      => $music['name'] ?? null,
			'url'       => $music['url'] ?? null,
			'summary'   => $music['description'] ?? null,
			'duration'  => $music['duration'] ?? null,
			'category'  => $music['genre'] ?? null,
			'published' => normalize_iso8601( $music['datePublished'] ?? null ),
			'featured'  => self::image_to_photo( $music['image'] ?? null ),
		);
		if ( isset( $music['releaseOf'] ) ) {
			if ( isset( $music['releaseOf']['byArtist'] ) ) {
				$return['author'] = array();
				foreach ( $music['releaseOf']['byArtist'] as $artist ) {
					$return['author'][] = array(
						array_filter(
							array(
								'type' => 'card',
								'name' => $artist['name'] ?? null,
								'url'  => $artist['@id'] ?? null,
							)
						),
					);
				}
			}
		}
		if ( isset( $music['tracks'] ) ) {
			$return['tracks'] = array();
			foreach ( $music['tracks'] as $track ) {
				$return['tracks'][] = self::music_to_hcite( $track );
			}
		}
		return array_filter( $return );
	}

	/**
	 * Converts a Movie, TVSeries or TVEpisode node into a jf2 cite.
	 *
	 * @since 1.0.0
	 *
	 * @param array $movie Movie, TVSeries or TVEpisode node.
	 * @return array|false jf2 cite with actor, director and creator cards, or false if
	 *                     the node is not one of those types.
	 */
	public static function media_to_hcite( $movie ) {
		if ( 'media' !== self::get_type( $movie ) ) {
			return false;
		}

		$return = array(
			'type'      => 'cite',
			'name'      => $movie['name'] ?? null,
			'url'       => $movie['url'] ?? null,
			'summary'   => $movie['description'] ?? null,
			'duration'  => $movie['duration'] ?? null,
			'category'  => $movie['genre'] ?? null,
			'published' => normalize_iso8601( $movie['datePublished'] ?? null ),
			'featured'  => self::image_to_photo( $movie['image'] ?? null ),
			'video'     => self::video_to_video( $movie['trailer'] ?? null ),
		);

		if ( empty( $return['duration'] ) && isset( $movie['timeRequired'] ) ) {
			$return['duration'] = $movie['timeRequired'];
		}
		foreach ( array( 'actor', 'director', 'creator' ) as $type ) {
			if ( isset( $movie[ $type ] ) ) {
				if ( ! wp_is_numeric_array( $movie[ $type ] ) ) {
					$movie[ $type ] = array( $movie[ $type ] );
				}
				$return[ $type ] = array();
				foreach ( $movie[ $type ] as $person ) {
					$return[ $type ][] = self::person_to_hcard( $person );
				}
			}
		}
		return array_filter( $return );
	}

	/**
	 * Converts an Event node into a jf2 event.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event Event or BusinessEvent node.
	 * @return array|false jf2 event, or false if the node is not an event.
	 */
	public static function event_to_hevent( $event ) {
		if ( 'event' !== self::get_type( $event ) ) {
			return false;
		}
		$return = array(
			'type'      => 'event',
			'name'      => $event['name'] ?? null,
			'url'       => $event['url'] ?? null,
			'summary'   => $event['description'] ?? null,
			'organizer' => self::organization_to_hcard( $event['organizer'] ?? null ),
			'location'  => self::place_to_hcard( $event['location'] ?? null ),
			'start'     => normalize_iso8601( $event['startDate'] ?? null ),
			'end'       => normalize_iso8601( $event['endDate'] ?? null ),
			'featured'  => self::image_to_photo( $event['image'] ?? null ),
		);

		return array_filter( $return );
	}


	/**
	 * Returns the URL of an ImageObject.
	 *
	 * For a list, the last image is used.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string|mixed $image ImageObject node, list of nodes, or URL.
	 * @return string|false The image URL, or false if $image is not an image.
	 */
	public static function image_to_photo( $image ) {
		if ( wp_is_numeric_array( $image ) ) {
			$image = array_pop( $image );
		}
		if ( is_string( $image ) ) {
			return $image;
		}
		if ( 'image' !== self::get_type( $image ) ) {
			return false;
		}

		/*
		 * Disabled: return the caption as alt text.
		 *
		 * if ( isset( $image['caption'] ) ) {
		 *     return array(
		 *         'value' => $image['url'],
		 *         'alt'   => $image['caption'],
		 *     );
		 * }
		 */
		return $image['url'];
	}

	/**
	 * Converts an AudioObject node into jf2 properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $audio AudioObject node, or a URL.
	 * @return array|string|false jf2 properties (name, summary, featured, audio,
	 *                           published, duration, content, publication), the URL
	 *                           if a string was passed, or false.
	 */
	public static function audio_to_audio( $audio ) {
		if ( is_string( $audio ) ) {
			return $audio;
		}
		if ( 'audio' !== self::get_type( $audio ) ) {
			return false;
		}

		$return = array(
			'name'      => $audio['name'] ?? null,
			'summary'   => $audio['description'] ?? null,
			'featured'  => $audio['thumbnailUrl'] ?? null,
			'audio'     => $audio['contentUrl'] ?? null,
			'published' => normalize_iso8601( $audio['uploadDate'] ?? null ),
			'duration'  => $audio['duration'] ?? null,
		);
		if ( isset( $audio['transcript'] ) ) {
			$return['content'] = array(
				'html'  => Parser::clean_content( $audio['transcript'] ),
				'value' => wp_strip_all_tags( $audio['transcript'] ),
			);
		}
		if ( isset( $audio['publisher'] ) ) {
			$return['publication'] = self::organization_to_hcard( $audio['publisher'] );
		}
		return array_filter( $return );
	}

	/**
	 * Converts a VideoObject node into jf2 properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $video VideoObject node, or a URL.
	 * @return array|string|false jf2 properties (name, summary, featured, video,
	 *                           published, duration, content, publication), the URL
	 *                           if a string was passed, or false.
	 */
	public static function video_to_video( $video ) {
		if ( is_string( $video ) ) {
			return $video;
		}
		if ( 'video' !== self::get_type( $video ) ) {
			return false;
		}
		$return = array(
			'name'      => $video['name'] ?? null,
			'summary'   => $video['description'] ?? null,
			'featured'  => $video['thumbnailUrl'] ?? null,
			'video'     => $video['contentUrl'] ?? null,
			'published' => normalize_iso8601( $video['uploadDate'] ?? null ),
			'duration'  => $video['duration'] ?? null,
		);

		if ( isset( $video['transcript'] ) ) {
			$return['content'] = array(
				'html'  => Parser::clean_content( $video['transcript'] ),
				'value' => wp_strip_all_tags( $video['transcript'] ),
			);
		}
		if ( isset( $video['publisher'] ) ) {
			$return['publication'] = self::organization_to_hcard( $video['publisher'] );
		}
		return array_filter( $return );
	}

	/**
	 * Converts a GeoCoordinates node into a jf2 geo object.
	 *
	 * @since 1.0.0
	 *
	 * @param array|mixed $geo GeoCoordinates node.
	 * @return array|false Geo with latitude and longitude, or false.
	 */
	public static function geocoordinates_to_geo( $geo ) {
		if ( ! self::is_jsonld( $geo ) ) {
			return false;
		}
		if ( ! self::is_jsonld_type( $geo, 'GeoCoordinates' ) ) {
			return false;
		}
		$return = array(
			'type'      => 'geo',
			'latitude'  => $geo['latitude'] ?? null,
			'longitude' => $geo['longitude'] ?? null,
		);
		return array_filter( $return );
	}

	/**
	 * Converts a PostalAddress node into h-adr properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array|mixed $address PostalAddress node.
	 * @return array|false Address properties (locality, region, country-name,
	 *                     postal-code, street-address), or false.
	 */
	public static function postaladdress_to_address( $address ) {
		if ( ! self::is_jsonld_type( $address, 'PostalAddress' ) ) {
			return false;
		}

		$return = array(
			'locality'       => $address['addressLocality'] ?? null,
			'region'         => $address['addressRegion'] ?? null,
			'country-name'   => $address['addressCountry'] ?? null,
			'postal-code'    => $address['postalCode'] ?? null,
			'street-address' => $address['streetAddress'] ?? null,
		);
		return array_filter( $return );
	}

	/**
	 * Converts a Place node into a jf2 card.
	 *
	 * @since 1.0.0
	 *
	 * @param array|mixed $place Place node.
	 * @return array|false Card with '_type' => 'place', its address merged in, or false.
	 */
	public static function place_to_hcard( $place ) {
		if ( ! self::is_jsonld( $place ) ) {
			return false;
		}
		if ( 'place' !== self::get_type( $place ) ) {
			return false;
		}
		$hcard = array(
			'type'  => 'card',
			'_type' => 'place',
			'name'  => $place['name'] ?? null,
			'note'  => $place['description'] ?? null,
			'tel'   => $place['telephone'] ?? null,
			'photo' => self::image_to_photo( $place['image'] ?? null ),
			'me'    => $place['sameAs'] ?? null,
			'geo'   => self::geocoordinates_to_geo( $place['geo'] ?? null ),
		);

		if ( isset( $place['address'] ) ) {
			$address = self::postaladdress_to_address( $place['address'] );
			if ( is_array( $address ) ) {
				$hcard = array_merge( $hcard, $address );
			}
		}
		return array_filter( $hcard );
	}

	/**
	 * Converts a Person node, or a plain name, into a jf2 card.
	 *
	 * Organizations are accepted too, since sites often list one as the author.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $person Person or Organization node, or a name.
	 * @return array|false Card, list of cards if the node lists several names, or false.
	 */
	public static function person_to_hcard( $person ) {
		if ( is_string( $person ) ) {
			return array(
				'type' => 'card',
				'name' => $person,
			);
		}
		if ( ! self::is_jsonld( $person ) ) {
			return false;
		}
		$type = self::get_type( $person );
		// Organizations are commonly listed as authors.
		if ( 'org' === $type ) {
			return self::organization_to_hcard( $person );
		}
		if ( 'person' !== $type ) {
			return false;
		}
		if ( isset( $person['name'] ) && is_array( $person['name'] ) ) {
			$author = array();
			foreach ( $person['name'] as $a ) {
				$author[] = array(
					'type' => 'card',
					'name' => $a,
				);
			}
		} else {

			$author = array(
				'type'      => 'card',
				'name'      => $person['name'] ?? null,
				'email'     => $person['email'] ?? null,
				'photo'     => self::image_to_photo( $person['image'] ?? null ),
				'url'       => $person['url'] ?? null,
				'me'        => $person['sameAs'] ?? null,
				'dt-bday'   => $person['birthDate'] ?? null,
				'job-title' => $person['jobTitle'] ?? null,
				'location'  => self::place_to_hcard( $person['location'] ?? null ),
			);
		}
		return array_filter( $author );
	}

	/**
	 * Converts a WebSite node into a jf2 card.
	 *
	 * @since 2.0.0
	 *
	 * @param array|mixed $website WebSite node.
	 * @return array|false Card with '_type' => 'website', or false.
	 */
	public static function site_to_hcard( $website ) {
		if ( 'site' !== self::get_type( $website ) ) {
			return false;
		}

		$publication = array(
			'type'  => 'card',
			'_type' => 'website',
			'name'  => $website['name'] ?? null,
			'url'   => $website['url'] ?? null,
			'me'    => $website['sameAs'] ?? null,
		);
		return array_filter( $publication );
	}

	/**
	 * Converts an Organization node into a jf2 card.
	 *
	 * @since 1.0.0
	 *
	 * @param array|mixed $organization Organization, NewsMediaOrganization, NGO or
	 *                                   MusicGroup node.
	 * @return array|false Card with logo as photo, members and address, or false.
	 */
	public static function organization_to_hcard( $organization ) {
		if ( 'org' !== self::get_type( $organization ) ) {
			return false;
		}

		$publication = array(
			'type'     => 'card',
			'_type'    => $organization['@type'],
			'name'     => $organization['name'] ?? null,
			'photo'    => self::image_to_photo( $organization['logo'] ?? null ),
			'url'      => $organization['url'] ?? null,
			'me'       => $organization['sameAs'] ?? null,
			'email'    => $organization['email'] ?? null,
			'location' => self::place_to_hcard( $organization['location'] ?? null ),
			'summary'  => $organization['description'] ?? null,
		);
		if ( empty( $publication['photo'] ) ) {
			$publication['photo'] = self::image_to_photo( $organization['image'] ?? null );
		}
		if ( isset( $organization['member'] ) ) {
			$members = $organization['member'];
			if ( ! wp_is_numeric_array( $members ) ) {
				$members = array( $members );
			}
			$publication['member'] = array();
			foreach ( $members as $member ) {
				$publication['member'][] = self::person_to_hcard( $member );
			}
			$publication['member'] = array_filter( $publication['member'] );
		}
		if ( isset( $organization['address'] ) ) {
			$address = self::postaladdress_to_address( $organization['address'] );
			if ( is_array( $address ) ) {
				$publication = array_merge( $publication, $address );
			} else {
				$publication['_address'] = $address;
			}
		}
		return array_filter( $publication );
	}

	/**
	 * Checks whether a value is a typed JSON-LD node.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $jsonld Value to check.
	 * @return bool True if it is an array with an @type.
	 */
	public static function is_jsonld( $jsonld ) {
		return ( is_array( $jsonld ) && array_key_exists( '@type', $jsonld ) );
	}

	/**
	 * Checks whether a value is a JSON-LD document with an @graph.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $jsonld Value to check.
	 * @return bool True if it is an array with an @graph.
	 */
	public static function is_jsonld_graph( $jsonld ) {
		return ( is_array( $jsonld ) && array_key_exists( '@graph', $jsonld ) );
	}

	/**
	 * Checks whether a JSON-LD node has one of the given types.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed           $jsonld Node to check. Its @type may be a string or a list.
	 * @param string|string[] $type   Type or types to match.
	 * @return bool True if any of the node's types matches.
	 */
	public static function is_jsonld_type( $jsonld, $type ) {
		if ( ! self::is_jsonld( $jsonld ) ) {
			return false;
		}

		if ( is_string( $type ) ) {
			$type = array( $type );
		}
		// @type may be a single type or a list of types.
		$types = is_array( $jsonld['@type'] ) ? $jsonld['@type'] : array( $jsonld['@type'] );
		return ( 0 < count( array_intersect( $types, $type ) ) );
	}

	/**
	 * Maps a JSON-LD node's schema.org type to the plugin's type name.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $jsonld Node to check.
	 * @return string|false One of review, recipe, product, entry (an article),
	 *                      webpage, org, person, site, event, image, audio, video,
	 *                      music, media, place or address, or false.
	 */
	public static function get_type( $jsonld ) {
		if ( self::is_jsonld_type( $jsonld, self::REVIEW_TYPES ) ) {
			return 'review';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Recipe' ) ) ) {
			return 'recipe';
		} elseif ( self::is_jsonld_type( $jsonld, self::PRODUCT_TYPES ) ) {
			return 'product';
		} elseif ( self::is_jsonld_type( $jsonld, self::ARTICLE_TYPES ) ) {
			return 'entry';
		} elseif ( self::is_jsonld_type( $jsonld, self::WEBPAGE_TYPES ) ) {
			return 'webpage';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Organization', 'NewsMediaOrganization', 'NGO', 'MusicGroup' ) ) ) {
			return 'org';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Person' ) ) ) {
			return 'person';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'WebSite' ) ) ) {
			return 'site';
		} elseif ( self::is_jsonld_type( $jsonld, self::EVENT_TYPES ) ) {
			return 'event';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'ImageObject' ) ) ) {
			return 'image';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'AudioObject' ) ) ) {
			return 'audio';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'VideoObject' ) ) ) {
			return 'video';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'MusicRelease' ) ) ) {
			return 'music';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Movie', 'TVSeries', 'TVEpisode' ) ) ) {
			return 'media';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'Place' ) ) ) {
			return 'place';
		} elseif ( self::is_jsonld_type( $jsonld, array( 'PostalAddress' ) ) ) {
			return 'address';
		}

		return false;
	}


	/**
	 * Converts an article or web page node into a jf2 entry.
	 *
	 * Accepts WebPage, Article, NewsArticle and BlogPosting.
	 *
	 * @since 1.0.0
	 *
	 * @param array|mixed $newsarticle Article node.
	 * @return array|false jf2 entry, or false if the node is not an article.
	 */
	public static function article_to_hentry( $newsarticle ) {
		if ( ! in_array( self::get_type( $newsarticle ), array( 'entry', 'webpage' ), true ) ) {
			return false;
		}
		$jf2          = array();
		$jf2['type']  = 'entry';
		$jf2['_type'] = $newsarticle['@type'];
		if ( isset( $newsarticle['datePublished'] ) ) {
			$jf2['published'] = normalize_iso8601( $newsarticle['datePublished'] );
		}

		if ( isset( $newsarticle['dateModified'] ) ) {
			$jf2['updated'] = normalize_iso8601( $newsarticle['dateModified'] );
		}

		if ( isset( $newsarticle['headline'] ) ) {
			$jf2['name'] = $newsarticle['headline'];
		} elseif ( isset( $newsarticle['name'] ) ) {
			$jf2['name'] = $newsarticle['name'];
		}
		if ( isset( $newsarticle['description'] ) ) {
			$jf2['summary'] = $newsarticle['description'];
		}
		if ( isset( $newsarticle['image'] ) ) {
			if ( wp_is_numeric_array( $newsarticle['image'] ) ) {
				$newsarticle['image'] = end( $newsarticle['image'] );
			}
			$jf2['featured'] = self::image_to_photo( $newsarticle['image'] );
		}
		if ( isset( $newsarticle['keywords'] ) ) {
			if ( ! is_array( $newsarticle['keywords'] ) ) {
				$newsarticle['keywords'] = explode( ',', $newsarticle['keywords'] );
			} elseif ( is_string( $newsarticle['keywords'] ) ) {
				$newsarticle['keywords'] = array( $newsarticle['keywords'] );
			}
			$jf2['category'] = array_map( 'trim', $newsarticle['keywords'] );
		}

		if ( isset( $newsarticle['articleBody'] ) ) {
			$jf2['content'] = array(
				'html' => Parser::clean_content( $newsarticle['articleBody'] ),
				'text' => trim( wp_strip_all_tags( $newsarticle['articleBody'] ) ),
			);
		}
		if ( isset( $newsarticle['author'] ) ) {
			if ( ! wp_is_numeric_array( $newsarticle['author'] ) ) {
				$newsarticle['author'] = array( $newsarticle['author'] );
			}
			$jf2['author'] = array();
			foreach ( $newsarticle['author'] as $author ) {
				$jf2['author'][] = self::person_to_hcard( $author );
			}
			$jf2['author'] = array_filter( $jf2['author'] );
			if ( 0 === count( $jf2['author'] ) ) {
				unset( $jf2['author'] );
			} elseif ( 1 === count( $jf2['author'] ) ) {
				$jf2['author'] = array_pop( $jf2['author'] );
			}
		}
		if ( isset( $newsarticle['creator'] ) ) {
			if ( ! wp_is_numeric_array( $newsarticle['creator'] ) ) {
				$newsarticle['creator'] = array( $newsarticle['creator'] );
			}
			$jf2['author'] = array();
			foreach ( $newsarticle['creator'] as $creator ) {
				$jf2['author'][] = self::person_to_hcard( $creator );
			}
		}

		foreach ( array( 'video', 'audio' ) as $media ) {
			if ( ! isset( $newsarticle[ $media ] ) ) {
				continue;
			}
			// May be a URL, a single object or a list of objects.
			$value = $newsarticle[ $media ];
			if ( wp_is_numeric_array( $value ) ) {
				$value = reset( $value );
			}
			if ( is_array( $value ) ) {
				if ( isset( $value['@id'] ) ) {
					$value = $value['@id'];
				} elseif ( isset( $value['contentUrl'] ) ) {
					$value = $value['contentUrl'];
				}
			}
			if ( is_string( $value ) ) {
				$jf2[ $media ] = $value;
			}
		}

		if ( isset( $newsarticle['publisher'] ) ) {
			$jf2['publication'] = self::organization_to_hcard( $newsarticle['publisher'] );
		}
		return array_filter( $jf2 );
	}
}
