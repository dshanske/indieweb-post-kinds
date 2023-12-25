<?php
/**
 * Kind Functions
 *
 * Global Scoped Functions for Handling Kinds.
 *
 * @package Post Kinds
 */

/**
 *
 */
function register_post_kind( $slug, $args ) {
	Kind_Taxonomy::register_post_kind( $slug, $args );
}

function set_post_kind_visibility( $slug, $show = true ) {
	Kind_Taxonomy::set_post_kind_visibility( $slug, $show );
}

/**
 * Retrieves an array of post kind slugs.
 *
 * @return array The array of post kind slugs.
 */
function get_post_kind_slugs() {
	return Kind_Taxonomy::get_post_kind_slugs();
}

/**
 * Returns a pretty, translated version of a post kind slug
 *
 * @param string $slug A post format slug.
 * @return string The translated post format name.
 */
function get_post_kind_string( $slug ) {
	return Kind_Taxonomy::get_post_kind_string( $slug );
}

/**
 * Returns a link to a post kind index.
 *
 * @param string $kind The post kind slug.
 * @return string The post kind term link.
 */
function get_post_kind_link( $kind ) {
	return Kind_Taxonomy::get_post_kind_link( $kind );
}

/**
 * Returns the post kind slug for the current post.
 *
 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
 * @return string The post kind slug.
 */
function get_post_kind_slug( $post = null ) {
	return Kind_Taxonomy::get_post_kind_slug( $post );
}

/**
 * Returns the post kind name for the current post.
 *
 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
 * @return string The post kind name.
 */
function get_post_kind( $post = null ) {
	return Kind_Taxonomy::get_post_kind( $post );
}

/**
 * Check if a post has any of the given kinds, or any kind.
 *
 * @uses has_term()
 *
 * @param string|array $kinds Optional. The kind to check.
 * @param object|int   $post Optional. The post to check. If not supplied, defaults to the current post if used in the loop.
 * @return bool True if the post has any of the given kinds (or any kind, if no kind specified), false otherwise.
 */
function has_post_kind( $kinds = array(), $post = null ) {
	return Kind_Taxonomy::has_post_kind( $kinds, $post );
}

/**
 * Assign a kind to a post
 *
 * @param int|object $post The post for which to assign a kind.
 * @param string     $kind A kind to assign. Using an empty string or array will default to note.
 * @return mixed WP_Error on error. Array of affected term IDs on success.
 */
function set_post_kind( $post, $kind ) {
	return Kind_Taxonomy::set_post_kind( $post, $kind );
}

/**
 * Return the Displayed Response for a Specific Kind
 *
 * @param $slug
 * @param $name
 * @param $args
 * @return string
 */

function get_kind_view_part( $slug, $name = null, $args = null ) {
	Kind_View::get_view_part( $slug, $name, $args );
}

function kind_display( $post_id = null ) {
		echo Kind_View::get_display( $post_id ); // phpcs:ignore
}

function kind_flatten_array( $a ) {
	if ( ! is_array( $a ) ) {
		return $a;
	}
	if ( wp_is_numeric_array( $a ) ) {
		$array = array_map( 'kind_flatten_array', $a );
	}
	$array = array_filter( $a );
	if ( 1 === count( $a ) ) {
		return $a[0];
	}
}

// Return any sort of src urls in content
function kind_src_url_in_content( $content ) {
	if ( ! $content ) {
		return 0;
	}
	if ( preg_match_all( '@src="([^"]+)"@', $content, $output ) ) {
		return array_pop( $output );
	}
	return 0;
}

/**
 * Get a Marketed Up Link to the Post.
 *
 * @param WP_Post|null Post to Display.
 * @param string|array Classes to add to link
 * @param string|array Classes to add to the date
 * @return string Marked up link to a post.
 **/

function kind_get_the_link( $post = null, $cls = null, $date_cls = null ) {
	$post = get_post( $post );
	$kind = get_post_kind_slug( $post );

	if ( is_array( $cls ) ) {
		$cls = implode( ' ', $cls );
	}

	if ( is_array( $date_cls ) ) {
		$date_cls = implode( ' ', $date_cls );
	}

	$time_string = '<time class="%3$s" datetime="%1$s">%2$s</time>';
	$time_string = sprintf(
		$time_string,
		esc_attr( get_the_date( DATE_W3C, $post ) ),
		get_the_date( '', $post ),
		$date_cls
	);
	return sprintf( '<a class="%4$s" href="%2$s">%1$s</a> - %3$s', kind_get_the_title( $post, $kind ), get_the_permalink( $post ), $time_string, esc_attr( $cls ) );
}


/**
 * Get a Generated Title for a Post as Most Post Kinds do Not Have an Explicit Title.
 *
 * @param WP_Post|null Post to Display.
 * @param array $args Arguments.
 * @return string Marked up link to a post.
 **/
function kind_get_the_title( $post = null, $args = array() ) {
	$defaults = array(
		'photo_size' => array( 32, 32 ),
	);

	$args = wp_parse_args( $args, $defaults );

	$post      = get_post( $post );
	$kind      = get_post_kind_slug( $post );
	$title     = get_the_title( $post );
	$before    = Kind_Taxonomy::get_before_kind( $kind );
	$content   = '';
	$kind_post = new Kind_Post( $post );

	if ( ! empty( $title ) ) {
		return $title;
	}

	if ( in_array( $kind, array( 'audio', 'video', 'photo' ), true ) ) {
		switch ( $kind ) {
			case 'photo':
				$photos = $kind_post->get_photo();
				$before = wp_get_attachment_image(
					$photos[0],
					$args['photo_size'],
					false,
					array(
						'class' => 'kind-photo-thumbnail',
					)
				);
		}
	} elseif ( ! in_array( $kind, array( 'note', 'article' ), true ) ) {
		$cite = $kind_post->get_cite( 'name' );
		if ( false === $cite ) {
			$content = Kind_View::get_post_type_string( $kind_post->get_cite( 'url' ) );
		} else {
			$content = $cite;
		}
	} else {
		$content = $post->post_excerpt;
		// If no excerpt use content
		if ( ! $content ) {
			$content = $post->post_content;
		}
		// If no content use date
		if ( $content ) {
			$content = mb_strimwidth( wp_strip_all_tags( $content ), 0, 40, '...' );
		}
	}
	if ( is_array( $content ) ) {
		$content = wp_json_encode( $content );
	}

	$content = apply_filters( 'kind_get_the_title_content', $content, $post );
	$before  = apply_filters( 'kind_get_the_title_before', $before, $post );

	return trim( sprintf( '%1$s %2$s', $before, $content ) );
}
