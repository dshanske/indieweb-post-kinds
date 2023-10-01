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

		// Remove the Automatic Post Generation that the Micropub Plugin Offers
		if ( class_exists( 'Micropub_Render' ) ) {
			if ( has_filter( 'micropub_post_content', array( 'Micropub_Render', 'generate_post_content' ) ) ) {
				remove_filter( 'micropub_post_content', array( 'Micropub_Render', 'generate_post_content' ), 1, 2 );
			}
		} elseif ( class_exists( 'Micropub_Plugin' ) ) {
			if ( has_filter( 'micropub_post_content', array( 'Micropub_Plugin', 'generate_post_content' ) ) ) {
				remove_filter( 'micropub_post_content', array( 'Micropub_Plugin', 'generate_post_content' ), 1, 2 );
			}
		}

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
	 *
	 * @param array $post_array
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
		$type = post_type_discovery( mf2_to_jf2( $input ) );
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
	 * Parse our micropub values.
	 *
	 * @access public
	 *
	 * @param array $input Array of inputs to parse.
	 * @return mixed
	 */
	public static function micropub_parse( $input ) {
		if ( ! $input ) {
			return $input;
		}
		// q indicates a get query
		if ( isset( $input['q'] ) ) {
			return $input;
		}
		if ( ! isset( $input['properties'] ) ) {
			return $input;
		}
		$parsed = array( 'bookmark-of', 'like-of', 'favorite-of', 'in-reply-to', 'read-of', 'listen-of', 'watch-of' );
		foreach ( $input['properties'] as $property => $value ) {
			if ( in_array( $property, $parsed, true ) ) {
				if ( wp_is_numeric_array( $value ) ) {
					foreach ( $value as $i => $v ) {
						if ( wp_http_validate_url( $v ) ) {
							$parse = new Parse_This( $v );
							$fetch = $parse->fetch();
							if ( ! is_wp_error( $fetch ) ) {
								$parse->parse();
								$jf2 = $parse->get();
								// Entries become citations
								if ( 'entry' === $jf2['type'] ) {
									$jf2['type'] = 'cite';
								}
								$mf2                                    = jf2_to_mf2( $jf2 );
								$input['properties'][ $property ][ $i ] = $mf2;
							} else {
								error_log( wp_json_encode( $fetch ) ); // phpcs:ignore
							}
						}
					}
				} elseif ( isset( $value['url'] ) && wp_http_validate_url( $value['url'] ) ) {
					$parse = new Parse_This( $value['url'] );
					$fetch = $parse - fetch();
					if ( ! is_wp_error( $fetch ) ) {
						$parse->parse();
						$input['properties'][ $property ] = array_merge( $value, jf2_to_mf2( $parse->get() ) );
					}
				}
			}
		}
		return $input;
	}
} // End Class Kind_Plugins
