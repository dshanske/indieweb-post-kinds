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
	 * Initialize our plugin integrations.
	 *
	 * @access public
	 */
	public static function init() {
		// Set Post Kind for Micropub Inputs.
		add_action( 'after_micropub', array( static::class, 'micropub_set_kind' ), 9, 2 );
		add_action( 'after_micropub', array( static::class, 'post_formats' ), 11, 2 );
		add_filter( 'before_micropub', array( static::class, 'micropub_parse' ), 11 );
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
		// Queries (q) and requests without properties, such as updates, are left as is.
		if ( ! is_array( $input ) || isset( $input['q'] ) || ! isset( $input['properties'] ) || ! is_array( $input['properties'] ) ) {
			return $input;
		}
		if ( ! class_exists( '\\ParseThis\\Parser' ) ) {
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
				$cite = self::parse_citation( $value );
				if ( $cite ) {
					$input['properties'][ $property ][ $i ] = $cite;
				}
			}
		}
		return $input;
	}

	/**
	 * Fetches and parses a URL into an mf2 citation.
	 *
	 * @access public
	 *
	 * @param string $url URL of the cited page.
	 * @return array|false mf2 citation, or false if the page could not be fetched.
	 */
	public static function parse_citation( $url ) {
		$parse = new \ParseThis\Parser( $url );
		$fetch = $parse->fetch();
		if ( is_wp_error( $fetch ) ) {
			error_log( wp_json_encode( $fetch ) ); // phpcs:ignore
			return false;
		}
		// Keep nested objects in place rather than moving them to refs.
		$parse->parse( array( 'references' => false ) );
		$jf2 = $parse->get();
		if ( ! is_array( $jf2 ) || empty( $jf2 ) ) {
			return false;
		}
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
