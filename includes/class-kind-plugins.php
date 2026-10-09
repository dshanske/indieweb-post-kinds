<?php
/**
 * Kind_Plugins class.
 *
 * @package Post_Kinds
 * @since   2.5.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integrations with other plugins, such as Micropub and ActivityPub.
 *
 * @since 2.5.2
 */
class Kind_Plugins {

	/**
	 * WP-Cron hook that retries parsing a Micropub citation in the background.
	 *
	 * @var string
	 */
	const ENRICH_EVENT = 'post_kinds_enrich_citation';

	/**
	 * How many times a citation is parsed after temporary failures, counting the
	 * attempt during the request.
	 *
	 * @var int
	 */
	const ENRICH_ATTEMPTS = 3;

	/**
	 * Post meta holding the log of citation parsing outcomes for a post.
	 *
	 * @var string
	 */
	const LOG_META = '_kind_citation_log';

	/**
	 * Post meta holding the post's citation status: retrying or failed. It is
	 * removed when no citation is retrying or failed.
	 *
	 * @var string
	 */
	const STATUS_META = '_kind_citation_status';

	/**
	 * How many log entries are kept per post.
	 *
	 * @var int
	 */
	const LOG_LIMIT = 20;

	/**
	 * Citation URLs from the current Micropub request that could not be parsed,
	 * as arrays of property, URL and WP_Error, waiting for the post ID.
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
		// Set the post kind for Micropub posts before they are inserted (Micropub 2.1.0
		// and later), with after_micropub as a fallback.
		add_filter( 'micropub_tax_input', array( static::class, 'micropub_tax_input' ), 10, 2 );
		add_action( 'after_micropub', array( static::class, 'micropub_set_kind' ), 9, 2 );
		add_action( 'after_micropub', array( static::class, 'post_formats' ), 11, 2 );
		add_filter( 'before_micropub', array( static::class, 'micropub_parse' ), 11 );
		add_action( 'after_micropub', array( static::class, 'schedule_enrichment' ), 20, 2 );
		add_action( self::ENRICH_EVENT, array( static::class, 'enrich_citation' ), 10, 4 );
		// Advertise the enabled kinds to Micropub clients (Micropub 1.4 and later).
		add_filter( 'micropub_query', array( static::class, 'micropub_query' ), 10, 2 );
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
	 * Returns the post kind for an mf2 object, using Post Type Discovery.
	 * Implements https://www.w3.org/TR/post-type-discovery/ via Parse This.
	 *
	 * @param array $mf2 mf2 object, such as a Micropub request.
	 * @return string Registered kind slug, or an empty string if none applies.
	 */
	public static function discover_kind( $mf2 ) {
		if ( ! is_array( $mf2 ) || empty( $mf2['properties'] ) || ! function_exists( '\\ParseThis\\post_type_discovery' ) ) {
			return '';
		}
		$type = \ParseThis\post_type_discovery( \ParseThis\mf2_to_jf2( $mf2 ) );
		return ( $type && Kind_Taxonomy::get_post_kind_info( $type ) ) ? $type : '';
	}

	/**
	 * Sets the kind of a Micropub post in the taxonomy input, so the post is
	 * inserted with its kind rather than the default term. Everything that runs
	 * during the insert, such as the publish transition, post formats and other
	 * plugins' publish hooks, then sees the right kind.
	 *
	 * Hooked to Micropub's micropub_tax_input filter (Micropub 2.1.0 and later),
	 * which is applied when a post is created.
	 *
	 * @param array|null $tax_input Taxonomy input for the new post.
	 * @param array      $input     Micropub request.
	 * @return array|null Taxonomy input, with the kind added.
	 */
	public static function micropub_tax_input( $tax_input, $input ) {
		$kind = self::discover_kind( $input );
		if ( ! $kind ) {
			return $tax_input;
		}
		if ( ! is_array( $tax_input ) ) {
			$tax_input = array();
		}
		if ( empty( $tax_input['kind'] ) ) {
			$tax_input['kind'] = array( $kind );
		}
		return $tax_input;
	}

	/**
	 * Sets the kind of a Micropub post after it is created, if it does not
	 * already have it.
	 *
	 * The kind is normally set before the insert (see micropub_tax_input()). This
	 * covers Micropub versions before 2.1.0, and users who cannot assign kind
	 * terms, for whom WordPress ignores the taxonomy input.
	 *
	 * @param array      $input   Micropub request.
	 * @param array|null $wp_args Arguments of the created or updated post; null for queries.
	 */
	public static function micropub_set_kind( $input, $wp_args ) {
		if ( empty( $wp_args['ID'] ) ) {
			return;
		}
		$post_id = (int) $wp_args['ID'];
		if ( isset( $input['action'] ) && 'update' === $input['action'] ) {
			// Only recalculate when the update changed a property that decides the
			// kind, so a kind chosen in the editor survives unrelated updates.
			if ( ! self::update_changes_kind( $input ) ) {
				return;
			}
			$kind = self::discover_kind( self::stored_mf2( $post_id ) );
		} else {
			$kind = self::discover_kind( $input );
		}
		if ( $kind && get_post_kind_slug( $post_id ) !== $kind ) {
			self::set_kind_without_moving_properties( $post_id, $kind );
		}
	}

	/**
	 * Returns the properties that decide a post's kind: each registered kind's
	 * property, plus rsvp, which Post Type Discovery checks first.
	 *
	 * @return string[] Property names.
	 */
	public static function kind_properties() {
		$properties = array( 'rsvp' );
		foreach ( Kind_Taxonomy::get_kind_list() as $kind ) {
			$property = Kind_Taxonomy::get_kind_info( $kind, 'property' );
			if ( $property ) {
				$properties[] = $property;
			}
		}
		return array_values( array_unique( $properties ) );
	}

	/**
	 * Whether a Micropub update request replaces, adds or deletes a property
	 * that decides the kind.
	 *
	 * @param array $input Micropub update request.
	 * @return bool
	 */
	public static function update_changes_kind( $input ) {
		$changed = array();
		foreach ( array( 'replace', 'add' ) as $operation ) {
			if ( isset( $input[ $operation ] ) && is_array( $input[ $operation ] ) ) {
				$changed = array_merge( $changed, array_keys( $input[ $operation ] ) );
			}
		}
		if ( isset( $input['delete'] ) && is_array( $input['delete'] ) ) {
			// Either a list of property names, or an object of property => values.
			$changed = array_merge( $changed, wp_is_numeric_array( $input['delete'] ) ? $input['delete'] : array_keys( $input['delete'] ) );
		}
		return (bool) array_intersect( $changed, self::kind_properties() );
	}

	/**
	 * Builds the mf2 for a post from what is stored: the mf2_ post meta, plus the
	 * title and content, which Post Type Discovery uses to tell articles from notes.
	 *
	 * @param int $post_id Post ID.
	 * @return array mf2 object.
	 */
	public static function stored_mf2( $post_id ) {
		$properties = array();
		foreach ( get_post_meta( $post_id ) as $key => $values ) {
			if ( 0 !== strpos( $key, 'mf2_' ) || 'mf2_type' === $key ) {
				continue;
			}
			$value = maybe_unserialize( $values[0] );
			if ( '' === $value || array() === $value ) {
				continue;
			}
			$properties[ substr( $key, 4 ) ] = is_array( $value ) ? $value : array( $value );
		}
		$post = get_post( $post_id );
		if ( $post && '' !== $post->post_title ) {
			$properties['name'] = array( $post->post_title );
		}
		if ( $post && '' !== $post->post_content ) {
			$properties['content'] = array( $post->post_content );
		}
		return array(
			'type'       => array( 'h-entry' ),
			'properties' => $properties,
		);
	}

	/**
	 * Sets a post's kind without moving property values between properties.
	 *
	 * When the kind changes in the editor, Kind_Metabox::change_kind() moves the
	 * old kind's property to the new one (a like-of becomes an in-reply-to, for
	 * example). After a Micropub update the properties are already where the
	 * client put them, so that is suspended here.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $kind    Kind slug.
	 */
	private static function set_kind_without_moving_properties( $post_id, $kind ) {
		$moving = has_action( 'change_kind', array( 'Kind_Metabox', 'change_kind' ) );
		if ( false !== $moving ) {
			remove_action( 'change_kind', array( 'Kind_Metabox', 'change_kind' ), $moving );
		}
		set_post_kind( $post_id, $kind );
		if ( false !== $moving ) {
			add_action( 'change_kind', array( 'Kind_Metabox', 'change_kind' ), $moving, 3 );
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
	 * Enriches the URLs in citation properties of a Micropub create request, or
	 * of the replace part of an update request, into h-cite objects parsed from
	 * the cited page.
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
		// Queries (q) are left as is.
		if ( ! is_array( $input ) || isset( $input['q'] ) ) {
			return $input;
		}
		if ( ! class_exists( '\\ParseThis\\Parser' ) || ! self::enrichment_enabled( $input ) ) {
			return $input;
		}
		// Create requests carry properties. Update requests can replace citation
		// properties; Micropub only allows adding categories, syndication and media.
		if ( isset( $input['properties'] ) && is_array( $input['properties'] ) ) {
			$input['properties'] = self::enrich_properties( $input['properties'] );
		}
		if ( isset( $input['action'] ) && 'update' === $input['action'] && isset( $input['replace'] ) && is_array( $input['replace'] ) ) {
			$input['replace'] = self::enrich_properties( $input['replace'] );
		}
		return $input;
	}

	/**
	 * Enriches the citation URLs in a set of Micropub properties.
	 *
	 * @param array $properties Properties, keyed by name, each an array of values.
	 * @return array The properties, with citation URLs enriched where possible.
	 */
	private static function enrich_properties( $properties ) {
		foreach ( self::citation_properties() as $property ) {
			if ( empty( $properties[ $property ] ) || ! wp_is_numeric_array( $properties[ $property ] ) ) {
				continue;
			}
			foreach ( $properties[ $property ] as $i => $value ) {
				if ( ! is_string( $value ) || ! wp_http_validate_url( $value ) ) {
					continue;
				}
				// Keep the client waiting only briefly: a short timeout and no follow-up
				// requests. Failures are logged, and retried in the background where
				// worthwhile, once the post exists (see schedule_enrichment()).
				$cite = self::parse_citation( $value, self::request_parse_args() );
				if ( is_wp_error( $cite ) ) {
					self::report_failure( $value, $cite );
					self::$pending[] = array( $property, $value, $cite );
					continue;
				}
				$properties[ $property ][ $i ] = $cite;
			}
		}
		return $properties;
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
	 * Classifies a parse failure to decide whether to retry it.
	 *
	 * @param WP_Error $error Parse failure.
	 * @return string One of:
	 *                'temporary'   A connection problem or timeout, a server error or rate limiting.
	 *                'unavailable' The page was missing or refused (HTTP 404, 401 or 403), which
	 *                              may be an outage or a configuration problem on the cited site.
	 *                'permanent'   Anything else, such as content that cannot be parsed.
	 */
	public static function failure_type( $error ) {
		if ( 'http_request_failed' === $error->get_error_code() ) {
			return 'temporary';
		}
		$code = self::failure_response_code( $error );
		if ( 429 === $code || $code >= 500 ) {
			return 'temporary';
		}
		if ( in_array( $code, array( 401, 403, 404 ), true ) ) {
			return 'unavailable';
		}
		return 'permanent';
	}

	/**
	 * Returns the HTTP status code of a parse failure, if there was one.
	 *
	 * @param WP_Error $error Parse failure.
	 * @return int HTTP status code, or 0.
	 */
	private static function failure_response_code( $error ) {
		$data = $error->get_error_data();
		return is_array( $data ) && isset( $data['response_code'] ) ? (int) $data['response_code'] : 0;
	}

	/**
	 * Returns how long to wait before retrying a failed citation, if at all.
	 *
	 * Temporary failures are retried after a minute, then after 15 minutes, up
	 * to ENRICH_ATTEMPTS attempts in all. A missing or refused page is retried
	 * once, after some hours, in case the cited site was down or misconfigured.
	 * Other failures are not retried.
	 *
	 * @param int      $post_id Post ID.
	 * @param string   $url     Cited URL.
	 * @param WP_Error $error   The failure.
	 * @param int      $attempt Which attempt failed.
	 * @return int Seconds to wait, or 0 for no retry.
	 */
	public static function retry_delay( $post_id, $url, $error, $attempt ) {
		switch ( self::failure_type( $error ) ) {
			case 'temporary':
				if ( $attempt >= self::ENRICH_ATTEMPTS ) {
					return 0;
				}
				return 1 === $attempt ? MINUTE_IN_SECONDS : 15 * MINUTE_IN_SECONDS;
			case 'unavailable':
				// Only once per citation.
				foreach ( self::get_citation_log( $post_id ) as $entry ) {
					if ( $url === $entry['url'] && 'unavailable' === $entry['type'] ) {
						return 0;
					}
				}
				/**
				 * Filters how long to wait before retrying, once, a citation whose page
				 * was missing or refused (HTTP 404, 401 or 403).
				 *
				 * @since 4.0.0
				 *
				 * @param int      $delay Delay in seconds. Default 6 hours.
				 * @param string   $url   Cited URL.
				 * @param WP_Error $error The failure.
				 */
				return max( 0, (int) apply_filters( 'post_kinds_micropub_unavailable_retry_delay', 6 * HOUR_IN_SECONDS, $url, $error ) );
			default:
				return 0;
		}
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
	 * Logs and, where worthwhile, schedules a retry for citations that could not
	 * be parsed during a Micropub create or update request, once the post ID is known.
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
			self::handle_failure( (int) $wp_args['ID'], $citation[0], $citation[1], 1, $citation[2] );
		}
	}

	/**
	 * Logs a failed attempt and schedules a retry if one is worthwhile.
	 *
	 * @param int      $post_id  Post ID.
	 * @param string   $property Micropub property, such as in-reply-to.
	 * @param string   $url      Cited URL.
	 * @param int      $attempt  Which attempt failed.
	 * @param WP_Error $error    The failure.
	 */
	private static function handle_failure( $post_id, $property, $url, $attempt, $error ) {
		// Work out the delay before logging, so the log holds only earlier failures.
		$delay    = self::retry_delay( $post_id, $url, $error, $attempt );
		$retry_at = $delay ? time() + $delay : 0;
		self::log_outcome( $post_id, $property, $url, $attempt, $error, $retry_at );
		if ( $retry_at ) {
			$args = array( $post_id, $property, $url, $attempt + 1 );
			if ( ! wp_next_scheduled( self::ENRICH_EVENT, $args ) ) {
				wp_schedule_single_event( $retry_at, self::ENRICH_EVENT, $args );
			}
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
			self::log_outcome( $post_id, $property, $url, $attempt, 'skipped', 0 );
			return;
		}
		$cite = self::parse_citation( $url );
		if ( is_wp_error( $cite ) ) {
			self::report_failure( $url, $cite );
			self::handle_failure( $post_id, $property, $url, $attempt, $cite );
			return;
		}
		$values[ $index ] = $cite;
		update_post_meta( $post_id, $key, $values );
		self::log_outcome( $post_id, $property, $url, $attempt, 'enriched', 0 );
	}

	/**
	 * Returns the log of citation parsing outcomes for a post, oldest first.
	 *
	 * Each entry is an array with these keys:
	 * - time          Unix timestamp of the attempt.
	 * - property      Micropub property, such as in-reply-to.
	 * - url           Cited URL.
	 * - attempt       Attempt number; 1 is the attempt during the Micropub request.
	 * - result        'enriched', 'failed', or 'skipped' (the citation was edited since).
	 * - type          For failures: 'temporary', 'unavailable' or 'permanent'. Otherwise empty.
	 * - code          For failures: the WP_Error code. Otherwise empty.
	 * - response_code For failures: the HTTP status code, or 0.
	 * - message       For failures: the error message. Otherwise empty.
	 * - retry         Unix timestamp of the scheduled retry, or 0 if none.
	 *
	 * @param int $post_id Post ID.
	 * @return array[] Log entries.
	 */
	public static function get_citation_log( $post_id ) {
		$log = get_post_meta( $post_id, self::LOG_META, true );
		return is_array( $log ) ? $log : array();
	}

	/**
	 * Adds an outcome to a post's citation log and updates its citation status.
	 *
	 * @param int             $post_id  Post ID.
	 * @param string          $property Micropub property.
	 * @param string          $url      Cited URL.
	 * @param int             $attempt  Attempt number.
	 * @param WP_Error|string $result   The failure, or 'enriched' or 'skipped'.
	 * @param int             $retry_at Timestamp of the scheduled retry, or 0.
	 */
	private static function log_outcome( $post_id, $property, $url, $attempt, $result, $retry_at ) {
		$failed = is_wp_error( $result );
		$entry  = array(
			'time'          => time(),
			'property'      => $property,
			'url'           => $url,
			'attempt'       => (int) $attempt,
			'result'        => $failed ? 'failed' : $result,
			'type'          => $failed ? self::failure_type( $result ) : '',
			'code'          => $failed ? $result->get_error_code() : '',
			'response_code' => $failed ? self::failure_response_code( $result ) : 0,
			'message'       => $failed ? $result->get_error_message() : '',
			'retry'         => (int) $retry_at,
		);
		$log    = self::get_citation_log( $post_id );
		$log[]  = $entry;
		$log    = array_slice( $log, -self::LOG_LIMIT );
		update_post_meta( $post_id, self::LOG_META, $log );

		// The status reflects the latest outcome for each cited URL.
		$latest = array();
		foreach ( $log as $item ) {
			$latest[ $item['url'] ] = $item;
		}
		$status = '';
		foreach ( $latest as $item ) {
			if ( 'failed' !== $item['result'] ) {
				continue;
			}
			if ( ! $item['retry'] ) {
				$status = 'failed';
				break;
			}
			$status = 'retrying';
		}
		if ( $status ) {
			update_post_meta( $post_id, self::STATUS_META, $status );
		} else {
			delete_post_meta( $post_id, self::STATUS_META );
		}

		/**
		 * Fires after each attempt to parse a Micropub citation.
		 *
		 * @since 4.0.0
		 *
		 * @param array $entry   The log entry. See Kind_Plugins::get_citation_log().
		 * @param int   $post_id Post ID.
		 */
		do_action( 'post_kinds_citation_enrichment', $entry, $post_id );
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
	/**
	 * Adds the enabled kinds to Micropub's configuration query, using the
	 * post-types extension (Query for Supported Vocabulary), so clients only
	 * offer the kinds this site uses.
	 *
	 * @see https://indieweb.org/Micropub-extensions#Query_for_Supported_Vocabulary
	 *
	 * @param array|mixed $response Micropub query response.
	 * @param array       $input    Micropub query.
	 * @return array|mixed The response, with post-types added for q=config.
	 */
	public static function micropub_query( $response, $input ) {
		if ( ! is_array( $response ) || ! isset( $input['q'] ) || 'config' !== $input['q'] ) {
			return $response;
		}
		$types = isset( $response['post-types'] ) && is_array( $response['post-types'] ) ? $response['post-types'] : array();
		$known = wp_list_pluck( $types, 'type' );
		foreach ( self::micropub_post_types() as $type ) {
			if ( ! in_array( $type['type'], $known, true ) ) {
				$types[] = $type;
			}
		}
		$response['post-types'] = $types;
		return $response;
	}

	/**
	 * Returns the enabled kinds as Micropub post types.
	 *
	 * @return array[] Arrays with the keys type (the kind slug, which is its Post
	 *                 Type Discovery type) and name (the kind's singular name).
	 */
	public static function micropub_post_types() {
		// The kinds offered in the editor: those enabled in settings, plus note,
		// which is always available.
		$kinds = Kind_Config::get_termslist();
		array_unshift( $kinds, 'note' );
		$types = array();
		foreach ( array_unique( $kinds ) as $kind ) {
			$name = Kind_Taxonomy::get_kind_info( $kind, 'singular_name' );
			if ( $name ) {
				$types[] = array(
					'type' => $kind,
					'name' => $name,
				);
			}
		}
		/**
		 * Filters the post types advertised to Micropub clients in q=config.
		 *
		 * @since 4.0.0
		 *
		 * @param array[] $types Arrays with the keys type and name.
		 */
		return apply_filters( 'post_kinds_micropub_post_types', $types );
	}
} // End Class Kind_Plugins
