<?php 

// Functions for Kind Taxonomies

/**
 * Retrieves an array of post kind slugs.
 *
 * @return array The array of post kind slugs.
 */
function get_post_kind_slugs() {
	$slugs = array_keys( kind_taxonomy::get_strings() );
	return array_combine( $slugs, $slugs );
}

/**
 * Returns a pretty, translated version of a post kind slug
 *
 *
 * @param string $slug A post format slug.
 * @return string The translated post format name.
 */
function get_post_kind_string( $slug ) {
	$strings = kind_taxonomy::get_strings();
	return ( isset( $strings[$slug] ) ) ? $strings[$slug] : '';
}

/**
 * Returns a link to a post kind index.
 *
 *
 * @param string $kind The post kind slug.
 * @return string The post kind term link.
 */
function get_post_kind_link( $kind ) {
	$term = get_term_by('slug', $kind, 'kind' );
	if ( ! $term || is_wp_error( $term ) )
		return false;
	return get_term_link( $term );
}

function get_post_kind_slug( $post = null ) {
	$post = get_post($post);
	if ( ! $post = get_post( $post ) )
		return false;
	$_kind = get_the_terms( $post->ID, 'kind' );
	if (!empty($_kind)) {
		$kind = array_shift($_kind);
    return $kind->slug;
	}
	else { return false; }
}

function get_post_kind( $post = null ) {
	$kind = get_post_kind_slug($post);
	if ($kind) {
		$strings = kind_taxonomy::get_strings();
		return $strings[$kind];
	}	
	else {
		return false; 
	}        
}

/**
 * Check if a post has any of the given kinds, or any kind.
 *
 * @uses has_term()
 *
 * @param string|array $kinds Optional. The kind to check.
 * @param object|int $post Optional. The post to check. If not supplied, defaults to the current post if used in the loop.
 * @return bool True if the post has any of the given kinds (or any kind, if no kind specified), false otherwise.
 */
function has_post_kind( $kinds = array(), $post = null ) {
	$prefixed = array();
	if ( $kinds ) {
		foreach ( (array) $kind as $single ) {
			$kind[] = sanitize_key( $single );
		}
	}
	return has_term( $kind, 'kind', $post );
}

/**
 * Assign a kind to a post
 *
 *
 * @param int|object $post The post for which to assign a kind.
 * @param string $kind A kind to assign. Using an empty string or array will default to note.
 * @return mixed WP_Error on error. Array of affected term IDs on success.
 */
function set_post_kind( $post, $kind ) {
	$post = get_post( $post );
	if ( empty( $post ) )
		return new WP_Error( 'invalid_post', __( 'Invalid post' ) );
	if ( ! empty( $kind ) ) {
		$kind = sanitize_key( $kind );
	}
	else { 
		$kind = 'note';
	}
	return wp_set_post_terms( $post->ID, $kind, 'kind' );
}

?>
