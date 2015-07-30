<?php
/**
 * Kind Functions
 *
 * Global Scoped Functions for Handling Kinds.
 */

/**
 * Return an array with only the mf2 prefixed meta.
 *
 * @param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
 * @return array False on failure.
 */
function get_mf2_meta( $post ) {
	$post = get_post( $post );
	$meta = get_post_meta( $post->ID );
	if ( ! $meta ) {
		return false;
	}
	foreach ( $meta as $key => $value ) {
		if ( ! str_prefix( $key, 'mf2_' ) ) {
			unset( $meta[ $key ] );
		} else {
			unset( $meta[ $key ] );
			$key = trim( $key, 'mf2_' );
			$value = array_map( 'maybe_unserialize', $value );
			$value = array_shift( $value );
			// If value is a multi-array with only one element
			if ( is_multi_array( $value ) ) {
				if ( count( $value ) == 1 ) {
					$value = array_shift( $value );
				}
				if ( isset( $value['card'] ) ) {
					if ( is_multi_array( $value['card'] ) ) {
						if ( count( $value['card'] ) == 1 ) {
							$value['card'] = array_shift( $value['card'] );
						}
					}
					$value['card'] = array_filter( $value['card'] );
				}
			}
			if ( is_array( $value ) ) {
				$value = array_filter( $value );
			}
			$meta[ $key ] = $value;
		}
	}
	return array_filter( $meta );
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
 * @@param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
 * @return string The post kind slug.
 */
function get_post_kind_slug( $post = null ) {
	return Kind_Taxonomy::get_post_kind_slug( $post );
}

/**
 * Returns the post kind name for the current post.
 *
 * @@param int|WP_Post $post Optional. Post ID or post object. Defaults to global $post.
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

?>
