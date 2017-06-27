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
		add_action( 'after_micropub', array( 'Kind_Plugins', 'micropub_set_kind' ), 10, 2 );
		add_action( 'after_micropub', array( 'Kind_Plugins', 'post_formats' ), 11, 2 );
		// Override Post Type in Semantic Linkbacks.
		add_filter( 'semantic_linkbacks_post_type', array( 'Kind_Plugins', 'semantic_post_type' ), 11, 2 );

		// Remove the Automatic Post Generation that the Micropub Plugin Offers
		remove_filter( 'before_micropub', array( 'Micropub', 'generate_post_content' ) );

	}

	// Replaces need for Replacing the Entire Excerpt
	public static function semantic_post_type($post_type, $post_id) {
		return _x( 'this', 'indieweb-post-kinds' ) . ' ' . strtolower( get_post_kind( $post_id ) );
	}

	// Replacement for the Semantic Linkbacks Comment Excerpt
	public static function comment_text_excerpt($text, $comment = null, $args = array()) {
		// only change text for pingbacks/trackbacks/webmentions
		if ( ! $comment || '' === $comment->comment_type || ! get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_canonical', true ) ) {
			return $text;
		}
		// check comment type
		$comment_type = get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_type', true );
		if ( ! $comment_type || ! in_array( $comment_type, array_keys( SemanticLinkbacksPlugin::get_comment_type_strings() ) ) ) {
			$comment_type = 'mention';
		}
		$_kind = get_the_terms( $comment->comment_post_ID, 'kind' );
		if ( ! empty( $_kind ) ) {
			$kind = array_shift( $_kind );
			$kindstrings = self::get_strings();
			$post_format = $kindstrings[ $kind->slug ];
		} else {
			$post_format = get_post_format( $comment->comment_post_ID );
			// replace "standard" with "Article"
			if ( ! $post_format || 'standard' === $post_format ) {
				$post_format = 'Article';
			} else {
				$post_formatstrings = get_post_format_strings();
				// get the "nice" name
				$post_format = $post_formatstrings[ $post_format ];
			}
		}
		// generate the verb, for example "mentioned" or "liked"
		$comment_type_excerpts = SemanticLinkbacksPlugin::get_comment_type_excerpts();
		// get URL canonical url...
		$url = get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_canonical', true );
		// ...or fall back to source
		if ( ! $url ) {
			$url = get_comment_meta( $comment->comment_ID, 'semantic_linkbacks_source', true );
		}
		// parse host
		$host = parse_url( $url, PHP_URL_HOST );
		// strip leading www, if any
		$host = preg_replace( '/^www\./', '', $host );
		// generate output
		$text = sprintf( $comment_type_excerpts[ $comment_type ], get_comment_author_link( $comment->comment_ID ), 'this ' . $post_format, $url, $host );
		return apply_filters( 'semantic_linkbacks_excerpt', $text );
	}

	/**
	 * Take mf2 properties and set a post kind
	 *
	 * @param array $input Micropub Request in JSON
	 * @param array $wp_args Arguments passed to insert or update posts
	 */

	public static function micropub_set_kind( $input, $wp_args ) {
		// Only continue if create or update
		if ( ! $wp_args ) {
			return;
		}
		if ( isset( $input['properties']['rsvp'] ) ) {
			set_post_kind( $wp_args['ID'], 'rsvp' );
			return;
		}
		if ( isset( $input['properties']['checkin'] ) ) {
			set_post_kind( $wp_args['ID'], 'checkin' );
			return;
		}
		if ( isset( $input['properties']['in-reply-to'] ) ) {
			set_post_kind( $wp_args['ID'], 'reply' );
			return;
		}

		if ( isset( $input['properties']['repost-of'] ) ) {
			set_post_kind( $wp_args['ID'], 'repost' );
			return;
		}

		if ( isset( $input['properties']['like-of'] ) ) {
			set_post_kind( $wp_args['ID'], 'like' );
			return;
		}

		// Video & audio come before photo, because either of these could contain a photo
		if ( isset( $input['properties']['video'] ) || isset( $_FILES['video'] )  ) {
			set_post_kind( $wp_args['ID'], 'video' );
			return;
		}

		if ( isset( $input['properties']['audio'] ) || isset( $_FILES['audio'] )  ) {
			set_post_kind( $wp_args['ID'], 'audio' );
			return;
		}

		if ( isset( $input['properties']['photo'] ) || isset( $_FILES['photo'] )  ) {
			set_post_kind( $wp_args['ID'], 'photo' );
			return;
		}

		if ( isset( $input['properties']['bookmark-of'] ) || isset( $input['properties']['bookmark'] ) ) {
			set_post_kind( $wp_args['ID'], 'bookmark' );
			return;
		}

		// This basically adds Teacup support
		if ( isset( $input['properties']['p3k-food'] ) ) {
			if ( isset( $input['properties']['p3k-type'] ) ) {
				if ( 'drink' === $input['properties']['p3k-type'] ) {
					set_post_kind( $wp_args['ID'], 'drink' );
					return;
				}
				set_post_kind( $wp_args['ID'], 'eat' );
				return;
			}
		}
		if ( ! empty( $input['properties']['name'] ) ) {
			$name = trim( $input['properties']['name'] );
			$content = trim( $input['properties']['content'] );
			if ( 0 !== strpos( $content, $name ) ) {
				set_post_kind( $wp_args['ID'], 'article' );
				return;
			}
		}

		// Doing this as a temporary measure until there is further troubleshooting
		set_post_kind( $wp_args['ID'], get_option( 'kind_default' ) );
	}

	public static function post_formats( $input, $wp_args ) {
		$kind = get_post_kind_slug( $wp_args['ID'] );
		set_post_format( $wp_args['ID'], Kind_Taxonomy::get_kind_info( $kind, 'format' ) );
	}

} // End Class Kind_Plugins

?>
