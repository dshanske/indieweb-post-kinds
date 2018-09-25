<?php

/**
 * Post Kind Plugins Class
 *
 * Custom Functions for Specific Other Pugins
 *
 * @package Post Kinds
 */
class Kind_Plugins {
	public static function init() {
		// Set Post Kind for Micropub Inputs.
		add_action( 'after_micropub', array( 'Kind_Plugins', 'micropub_set_kind' ), 9, 2 );
		add_action( 'after_micropub', array( 'Kind_Plugins', 'post_formats' ), 11, 2 );
		add_filter( 'before_micropub', array( 'Kind_Plugins', 'micropub_parse' ), 11 );
		// Override Post Type in Semantic Linkbacks.
		add_filter( 'semantic_linkbacks_post_type', array( 'Kind_Plugins', 'semantic_post_type' ), 11, 2 );

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

	}

	// Replaces need for Replacing the Entire Excerpt
	public static function semantic_post_type( $post_type, $post_id ) {
		return _x( 'this', 'direct article', 'indieweb-post-kinds' ) . ' ' . strtolower( get_post_kind( $post_id ) );
	}

	/**
	 * Take mf2 properties and set a post kind
	 * Implements Post Type Discovery https://www.w3.org/TR/post-type-discovery/
	 *
	 * @param array $input Micropub Request in JSON
	 * @param array $wp_args Arguments passed to insert or update posts
	 */
	public static function micropub_set_kind( $input, $wp_args ) {
		// Only continue if create or update
		if ( ! $wp_args ) {
			return;
		}
		$type = Parse_This_MF2::post_type_discovery( $input );
		if ( ! empty( $type ) ) {
			set_post_kind( $wp_args['ID'], $type );
		}
	}

	public static function post_formats( $input, $wp_args ) {
		$kind = get_post_kind_slug( $wp_args['ID'] );
		set_post_format( $wp_args['ID'], Kind_Taxonomy::get_kind_info( $kind, 'format' ) );
	}

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
				} else {
					if ( isset( $value['url'] ) && wp_http_validate_url( $value['url'] ) ) {
						$parse = new Parse_this( $value['url'] );
						$fetch = $parse - fetch();
						if ( ! is_wp_error( $fetch ) ) {
							$parse->parse();
							$input['properties'][ $property ] = array_merge( $value, jf2_to_mf2( $parse->get() ) );
						}
					}
				}
			}
		}
		return $input;
	}

} // End Class Kind_Plugins


