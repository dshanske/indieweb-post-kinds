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
		remove_filter( 'micropub_post_content', array( 'Micropub_Plugin', 'generate_post_content' ), 1, 2 );

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
		$parsed = array( 'bookmark-of', 'like-of', 'favorite-of', 'in-reply-to', 'read-of' );
		foreach ( $input['properties'] as $property => $value ) {
			if ( ! wp_is_numeric_array( $value ) ) {
				continue;
			}
			if ( in_array( $property, $parsed, true ) ) {
				foreach ( $value as $i => $v ) {
					if ( Link_Preview::is_valid_url( $v ) ) {
						$parse = Link_Preview::simple_parse( $v );
						if ( ! is_wp_error( $parse ) ) {
							$input['properties'][ $property ][ $i ] = $parse;
						}
					}
				}
			}
		}
		return $input;
	}

} // End Class Kind_Plugins


