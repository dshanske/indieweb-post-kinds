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

/**
 * Retrieves an array of mf2 data from a post.
 *
 * @param int|WP_Post $post A Post.
 * @param string      $key Meta Key to Retrieve. If empty, retrieve all. If 'mf2' retrieve only mf2 not WordPress properties.
 * @return array|Unknown The array of post kind slugs.
 */
function get_post_mf2meta( $post, $key = '' ) {
	$mf2_post = new MF2_Post( $post );
	if ( empty( $key ) ) {
		return $mf2_post->get( null );
	}
	return $mf2_post->get( $key );
}

/**
 * Retrieves jf2 data from a post.
 *
 * @param int|WP_Post $post A Post.
 * @param boolean $wordpress Include WordPress properties or not.
 * @return array The array of post kind slugs.
 */
function get_post_jf2meta( $post, $wordpress = true ) {
	$mf2_post  = new MF2_Post( $post );
	$wordpress = $wordpress ? 'all' : 'mf2';
	$mf2       = $mf2_post->get( $wordpress );
	return $mf2_post->mf2_to_jf2( $mf2 );
}



/**
 * Retrieves an array of post kind slugs.
 *
 * @return array The array of post kind slugs.
 */
function get_post_kind_slugs() {
	return Kind_Taxonomy::get_post_king_slugs();
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
 * @return string
 */

function get_kind_view_part( $slug, $name = null ) {
	Kind_View::get_view_part( $slug, $name );
}

function kind_display( $post_id = null ) {
		echo Kind_View::get_display( $post_id ); // phpcs:ignore
}

function kind_flatten_array( $array ) {
	if ( ! is_array( $array ) ) {
		return $array;
	}
	if ( 1 === count( $array ) && wp_is_numeric_array( $array ) ) {
		return $array[0];
	}
	return array_map( 'kind_flatten_array', $array );
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

function kind_photo_gallery( $photos ) {
	return gallery_shortcode(
		array(
			'ids'     => $photos,
			'size'    => 'large',
			'columns' => 1,
			'link'    => 'file',
		)
	);
}

function kind_audio_gallery( $id, $args = null ) {
	$return  = array();
	$default = array(
		'class' => 'wp-audio-shortcode u-audio',
	);
	if ( is_array( $id ) ) {
		foreach ( $id as $i ) {
			$return[] = kind_audio_gallery( $i, $args );
		}
		return implode( ' ', $return );
	} elseif ( wp_http_validate_url( $id ) ) {
		$args['src'] = $id;
	} else {
		$args['src'] = wp_get_attachment_url( (int) $id );
	}
	if ( $args['src'] ) {
		return wp_audio_shortcode( $args );
	}
	return '';
}

function kind_video_gallery( $id, $args = null ) {
	$return   = array();
	$defaults = array(
		'class' => 'wp-video-shortcode u-video',
	);
	if ( is_array( $id ) ) {
		foreach ( $id as $i ) {
			$return[] = kind_video_gallery( $i, $args );
		}
		return implode( ' ', $return );
	} elseif ( wp_http_validate_url( $id ) ) {
		$args['src'] = $id;
	} else {
		$args['src'] = wp_get_attachment_url( (int) $id );
	}
	if ( $args['src'] ) {
		$args = wp_parse_argS( $args, $defaults );
		return wp_video_shortcode( $args );
	}
	return '';
}

