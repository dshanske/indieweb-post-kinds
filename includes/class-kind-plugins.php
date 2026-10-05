<?php

/**
 * Post Kind Plugins Class
 *
 * Custom Functions for Specific Other Pugins
 *
 * @package Post Kinds
 */
class Kind_Plugins {

	/**
	 * WP-Cron hook that retries parsing a Micropub citation in the background.
	 *
	 * @var string
	 */
	const ENRICH_EVENT = 'post_kinds_enrich_citation';

	/**
	 * How many times a citation is parsed, counting the attempt during the request.
	 *
	 * @var int
	 */
	const ENRICH_ATTEMPTS = 3;

	/**
	 * Citation URLs from the current Micropub request that could not be parsed
	 * in time, as arrays of property and URL, waiting for the post ID.
	 *
	 * @var array[]
	 */
	private static $pending = array();

	/**
	 * Initialize our plugin integrations.
	 *
	 * @access public
	 */
	public static function init() {
		// Set Post Kind for Micropub Inputs.
		add_action( 'after_micropub', array( static::class, 'micropub_set_kind' ), 9, 2 );
		add_action( 'after_micropub', array( static::class, 'post_formats' ), 11, 2 );
		add_filter( 'before_micropub', array( static::class, 'micropub_parse' ), 11 );
		add_action( 'after_micropub', array( static::class, 'schedule_enrichment' ), 20, 2 );
		add_action( self::ENRICH_EVENT, array( static::class, 'enrich_citation' ), 10, 4 );
		add_filter( 'tempus_widget_post_title', array( static::class, 'tempus_widget_post_title' ), 10, 2 );
		// Override Post Type in Semantic Linkbacks.
		add_filter( 'semantic_linkbacks_post_type', array( static::class, 'semantic_post_type' ), 11, 2 );

		// Post Kinds displays the response itself, so turn off Micropub's dynamic
		// rendering of Micropub posts (Micropub 2.4.0 and later).
		add_filter( 'micropub_dynamic_render', '__return_false' );

		// Hum Compatibility Filters
		add_action( 'hum_local_types', array( static::class, 'hum_local_types' ), 11 );
		add_action( 'hum_type_prefix', array( static::class, 'hum_type_prefix' ), 11, 2 );

		// ActivityPub Filters
		add_filter( 'activitypub_post', array( static::class, 'activitypub_post' ), 12 );
	}


	/**
	 * Construct a title for the Tempus Widget link.
	 *
	 * @access public
	 *
	 * @param string $title The Original Title.
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function tempus_widget_post_title( $title, $post ) {
		return kind_get_the_title( $post );
	}

	/**
	 * Sets the ActivityPub object type based on the post kind.
	 *
	 * @param array $post_array ActivityPub object array.
	 *
	 * @return array
	 */
	public static function activitypub_post( $post_array ) {
		$post_id   = url_to_postid( $post_array['id'] );
		$post_type = get_post_type( $post_id );
		if ( 'post' === $post_type ) {
			$kind = new Kind_Post( $post_id );
			switch ( $kind->get_kind() ) {
				case 'note':
					$post_array['type'] = 'Note';
					break;
				case 'photo':
					$post_array['type'] = 'Image';
					break;
				case 'video':
					$post_array['type'] = 'Video';
					break;
				case 'audio':
					$post_array['type'] = 'Audio';
					break;
				case 'article':
					$post_array['type'] = 'Article';
					break;
				case 'reply':
					$post_array['type']      = 'Note';
					$post_array['inReplyTo'] = $kind->get_cite( 'url' );
					break;
			}
		}
		return $post_array;
	}

	public static function hum_local_types( $types ) {
		// http://tantek.pbworks.com/w/page/21743973/Whistle#design - Some of the uses are modified based on design considerations noted.
		$types[] = 'f'; // Favorited, Likes, etc
		$types[] = 'e'; // Events
		$types[] = 'g'; // Geo Checkin
		$types[] = 'h'; // Link
		$types[] = 'm'; // Metric
		$types[] = 'q'; // Question
		$types[] = 'r'; // Review
		$types[] = 'x'; // Experience
		$types[] = 'u'; // Status Update
		return $types;
	}

	public static function hum_type_prefix( $prefix, $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( 'post' !== $post_type ) {
			return $prefix;
		}

		$kind      = get_post_kind_slug( $post_id );
		$shortlink = Kind_Taxonomy::get_kind_info( $kind, 'shortlink' );
		if ( ! empty( $shortlink ) ) {
			return $shortlink;
		}
		return $prefix;
	}

	/**
	 * Replaces need for replacing the entire excerpt.
	 *
	 * @access public
	 *
	 * @param string $post_type Post type slug.
	 * @param int    $post_id   Post ID.
	 * @return string
	 */
	public static function semantic_post_type( $post_type, $post_id ) {
		return _x( 'this', 'direct article', 'indieweb-post-kinds' ) . ' ' . strtolower( get_post_kind( $post_id ) );
	}

	/**
	 * Take mf2 properties and set a post kind.
	 * Implements Post Type Discovery https://www.w3.org/TR/post-type-discovery/
	 *
	 * @param array $input   Micropub Request in JSON.
	 * @param array $wp_args Arguments passed to insert or update posts.
	 */
	public static function micropub_set_kind( $input, $wp_args ) {
		// Only continue if create or update
		if ( ! $wp_args ) {
			return;
		}
		if ( ! function_exists( '\\ParseThis\\post_type_discovery' ) ) {
			return;
		}
		$type = \ParseThis\post_type_discovery( \ParseThis\mf2_to_jf2( $input ) );
		if ( ! empty( $type ) ) {
			set_post_kind( $wp_args['ID'], $type );
		}
	}

	/**
	 * Set our post formats.
	 *
	 * @access public
	 *
	 * @param $input
	 * @param $wp_args
	 */
	public static function post_formats( $input, $wp_args ) {
		if ( empty( $wp_args ) || empty( $input ) ) {
			return;
		}
		$kind = get_post_kind_slug( $wp_args['ID'] );
		set_post_format( $wp_args['ID'], Kind_Taxonomy::get_kind_info( $kind, 'format' ) );
	}

	/**
	 * Returns the Micropub properties whose URL values are enriched into citations.
	 *
	 * @return string[] Property names.
	 */
	public static function citation_properties() {
		return array( 'bookmark-of', 'like-of', 'favorite-of', 'in-reply-to', 'read-of', 'listen-of', 'watch-of' );
	}

	/**
	 * Enriches the URLs in citation properties of a Micropub create request into
	 * h-cite objects parsed from the cited page.
	 *
	 * Follows the Micropub spec: property values are arrays, so a property whose
	 * value is not an array is left for Micropub to handle, and only string URLs
	 * inside the array are enriched. Values the client already sent as objects,
	 * such as an h-cite, are kept as sent.
	 *
	 * @access public
	 *
	 * @param array $input Micropub request, in mf2 JSON.
	 * @return array The request, with citation URLs enriched where possible.
	 */
	public static function micropub_parse( $input ) {
		self::$pending = array();
		// Queries (q) and requests without properties, such as updates, are left as is.
		if ( ! is_array( $input ) || isset( $input['q'] ) || ! isset( $input['properties'] ) || ! is_array( $input['properties'] ) ) {
			return $input;
		}
		if ( ! class_exists( '\\ParseThis\\Parser' ) || ! self::enrichment_enabled( $input ) ) {
			return $input;
		}
		foreach ( self::citation_properties() as $property ) {
			if ( empty( $input['properties'][ $property ] ) || ! wp_is_numeric_array( $input['properties'][ $property ] ) ) {
				continue;
			}
			foreach ( $input['properties'][ $property ] as $i => $value ) {
				if ( ! is_string( $value ) || ! wp_http_validate_url( $value ) ) {
					continue;
				}
				// Keep the client waiting only briefly: a short timeout and no follow-up
				// requests. A citation that fails for a temporary reason is retried in
				// the background once the post exists (see schedule_enrichment()).
				$cite = self::parse_citation( $value, self::request_parse_args() );
				if ( is_wp_error( $cite ) ) {
					self::report_failure( $value, $cite );
					if ( self::is_temporary_failure( $cite ) ) {
						self::$pending[] = array( $property, $value );
					}
					continue;
				}
				$input['properties'][ $property ][ $i ] = $cite;
			}
		}
		return $input;
	}

	/**
	 * Fetches and parses a URL into an mf2 citation.
	 *
	 * @access public
	 *
	 * @param string $url  URL of the cited page.
	 * @param array  $args {
	 *     Optional. Limits for this parse.
	 *
	 *     @type int $timeout      HTTP timeout in seconds. Default: Parse This's own (15).
	 *     @type int $max_requests Follow-up requests allowed. Default: Parse This's own (10).
	 * }
	 * @return array|WP_Error mf2 citation, or WP_Error if the page could not be fetched or parsed.
	 */
	public static function parse_citation( $url, $args = array() ) {
		$timeout       = isset( $args['timeout'] ) ? (int) $args['timeout'] : 0;
		$max_requests  = isset( $args['max_requests'] ) ? (int) $args['max_requests'] : null;
		$limit_timeout = static function ( $request ) use ( $timeout ) {
			$request['timeout'] = isset( $request['timeout'] ) ? min( (float) $request['timeout'], $timeout ) : $timeout;
			return $request;
		};
		$limit_budget  = static function () use ( $max_requests ) {
			return $max_requests;
		};
		if ( $timeout > 0 ) {
			add_filter( 'http_request_args', $limit_timeout, 99 );
		}
		if ( null !== $max_requests ) {
			add_filter( 'parse_this_max_requests', $limit_budget, 99 );
		}
		try {
			$parse = new \ParseThis\Parser( $url );
			$fetch = $parse->fetch();
			if ( is_wp_error( $fetch ) ) {
				return $fetch;
			}
			// Keep nested objects in place rather than moving them to refs.
			$parse->parse( array( 'references' => false ) );
			$jf2 = $parse->get();
		} finally {
			remove_filter( 'http_request_args', $limit_timeout, 99 );
			remove_filter( 'parse_this_max_requests', $limit_budget, 99 );
		}
		if ( ! is_array( $jf2 ) || empty( $jf2 ) ) {
			return new WP_Error( 'empty', __( 'Nothing could be parsed from the URL.', 'indieweb-post-kinds' ) );
		}
		// Content Parse This did not recognize is returned whole under raw.
		unset( $jf2['raw'] );
		$type = isset( $jf2['type'] ) ? $jf2['type'] : '';
		// A page that parses as a feed (a home page, for example) is cited as the
		// page itself, not its entries.
		if ( 'feed' === $type ) {
			$jf2 = array_intersect_key( $jf2, array_flip( array( 'name', 'url', 'author', 'summary', 'photo' ) ) );
		}
		$jf2 = self::clean_citation( $jf2 );
		// Entries and feeds become citations. Other types, such as an h-event for
		// an RSVP, are kept.
		$jf2['type'] = in_array( $type, array( '', 'entry', 'feed' ), true ) ? 'cite' : $type;
		return \ParseThis\jf2_to_mf2( $jf2 );
	}

	/**
	 * Whether to enrich citation URLs in Micropub requests.
	 *
	 * @param array|null $input Micropub request, or null outside a request.
	 * @return bool
	 */
	public static function enrichment_enabled( $input = null ) {
		/**
		 * Filters whether Post Kinds enriches citation URLs in Micropub requests
		 * (in-reply-to, like-of, and so on) with data parsed from the cited page.
		 *
		 * Return false to store citations exactly as the client sent them.
		 *
		 * @since 4.0.0
		 *
		 * @param bool       $enabled Whether to enrich citations. Default true.
		 * @param array|null $input   Micropub request, or null when a background retry runs.
		 */
		return (bool) apply_filters( 'post_kinds_micropub_enrich', true, $input );
	}

	/**
	 * Returns the limits for parsing citations during a Micropub request.
	 *
	 * @return array Arguments for parse_citation().
	 */
	public static function request_parse_args() {
		/**
		 * Filters the HTTP timeout, in seconds, for parsing a citation while a
		 * Micropub client waits. Citations that time out are retried in the
		 * background.
		 *
		 * @since 4.0.0
		 *
		 * @param int $timeout Timeout in seconds. Default 5.
		 */
		$timeout = (int) apply_filters( 'post_kinds_micropub_parse_timeout', 5 );
		return array(
			'timeout'      => max( 1, $timeout ),
			'max_requests' => 0,
		);
	}

	/**
	 * Whether a parse failure is worth retrying later: a connection problem or
	 * timeout, a server error, or rate limiting. Pages that are missing, private
	 * or not parseable are not retried.
	 *
	 * @param WP_Error $error Parse failure.
	 * @return bool
	 */
	public static function is_temporary_failure( $error ) {
		if ( 'http_request_failed' === $error->get_error_code() ) {
			return true;
		}
		$data = $error->get_error_data();
		$code = is_array( $data ) && isset( $data['response_code'] ) ? (int) $data['response_code'] : 0;
		return 429 === $code || $code >= 500;
	}

	/**
	 * Reports a citation that could not be parsed. Only shown when WP_DEBUG is on.
	 *
	 * @param string   $url   Cited URL.
	 * @param WP_Error $error Parse failure.
	 */
	public static function report_failure( $url, $error ) {
		wp_trigger_error(
			__METHOD__,
			sprintf( 'Could not parse %1$s for a Micropub citation: %2$s', esc_url( $url ), esc_html( $error->get_error_message() ) )
		);
	}

	/**
	 * Schedules background parsing for citations that could not be parsed during
	 * a Micropub create request, now that the post exists.
	 *
	 * @param array      $input   Micropub request.
	 * @param array|null $wp_args Arguments of the created or updated post.
	 */
	public static function schedule_enrichment( $input, $wp_args ) {
		$pending       = self::$pending;
		self::$pending = array();
		if ( empty( $pending ) || empty( $wp_args['ID'] ) ) {
			return;
		}
		foreach ( $pending as $citation ) {
			self::schedule_retry( (int) $wp_args['ID'], $citation[0], $citation[1], 2 );
		}
	}

	/**
	 * Schedules one background parse of a citation.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $property Micropub property, such as in-reply-to.
	 * @param string $url      Cited URL.
	 * @param int    $attempt  Which attempt this will be, starting at 2.
	 */
	private static function schedule_retry( $post_id, $property, $url, $attempt ) {
		// Wait a minute, then 15 minutes, then an hour, and so on.
		$delay = 2 === $attempt ? MINUTE_IN_SECONDS : 15 * MINUTE_IN_SECONDS * ( 4 ** ( $attempt - 3 ) );
		$args  = array( $post_id, $property, $url, $attempt );
		if ( ! wp_next_scheduled( self::ENRICH_EVENT, $args ) ) {
			wp_schedule_single_event( time() + $delay, self::ENRICH_EVENT, $args );
		}
	}

	/**
	 * Parses a Micropub citation in the background and stores the result.
	 *
	 * The citation is only replaced if the post still holds the URL as it was
	 * stored, so later edits are not overwritten.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $property Micropub property, such as in-reply-to.
	 * @param string $url      Cited URL.
	 * @param int    $attempt  Which attempt this is.
	 */
	public static function enrich_citation( $post_id, $property, $url, $attempt ) {
		if ( ! get_post( $post_id ) || ! class_exists( '\\ParseThis\\Parser' ) || ! self::enrichment_enabled() ) {
			return;
		}
		$key    = 'mf2_' . $property;
		$values = get_post_meta( $post_id, $key, true );
		$index  = is_array( $values ) ? array_search( $url, $values, true ) : false;
		if ( false === $index ) {
			return;
		}
		$cite = self::parse_citation( $url );
		if ( is_wp_error( $cite ) ) {
			self::report_failure( $url, $cite );
			if ( self::is_temporary_failure( $cite ) && $attempt < self::ENRICH_ATTEMPTS ) {
				self::schedule_retry( $post_id, $property, $url, $attempt + 1 );
			}
			return;
		}
		$values[ $index ] = $cite;
		update_post_meta( $post_id, $key, $values );
	}

	/**
	 * Removes Parse This bookkeeping from parsed jf2, recursively.
	 *
	 * Keys starting with an underscore (_code, _links, _alternate,
	 * _source_format, ...), post-type and refs are not microformats properties,
	 * and would otherwise be stored in post meta and returned by Micropub's
	 * q=source.
	 *
	 * @access public
	 *
	 * @param mixed $jf2 Parsed jf2, or any value within it.
	 * @return mixed The value without bookkeeping keys.
	 */
	public static function clean_citation( $jf2 ) {
		if ( ! is_array( $jf2 ) ) {
			return $jf2;
		}
		foreach ( array_keys( $jf2 ) as $key ) {
			if ( is_string( $key ) && ( 0 === strpos( $key, '_' ) || in_array( $key, array( 'post-type', 'refs' ), true ) ) ) {
				unset( $jf2[ $key ] );
			} elseif ( is_array( $jf2[ $key ] ) ) {
				$jf2[ $key ] = self::clean_citation( $jf2[ $key ] );
			}
		}
		return $jf2;
	}
} // End Class Kind_Plugins
