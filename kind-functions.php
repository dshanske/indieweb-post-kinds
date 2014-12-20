<?php 

// Functions for Kind Taxonomies

function get_the_kind( $id = false ) {
/**
         * Filter the array of kinds to return for a post.
         *
         *
         */

	        $kinds = get_the_terms( $id, 'kind' );
	        if ( ! $kinds || is_wp_error( $kinds ) )
	                $kinds = array();
	
	        $kinds = array_values( $kinds );
	
	        foreach ( array_keys( $kinds ) as $key ) {
	                _make_cat_compat( $kinds[$key] );
	        }
	
	        return apply_filters( 'get_the_kind', $kinds[0]->name );
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

function get_the_kinds_list( $before = '', $sep = '', $after = '', $id = 0 ) {
        /**
         * Filter the kinds list for a given post.
         *
         * @param string $kind_list List of kinds.
         * @param string $before   String to use before kinds.
         * @param string $sep      String to use between the kinds.
         * @param string $after    String to use after kinds.
         * @param int    $id       Post ID.
         */
        return apply_filters( 'the_kinds', get_the_term_list( $id, 'kind', $before, $sep, $after ), $before, $sep, $after, $id );
}

function the_kinds( $before = null, $sep = ', ', $after = '' ) {
        if ( null === $before )
                $before = __('Kinds: ');
        echo get_the_kinds_list($before, $sep, $after);
}



function get_kind_context_class ( $class = '', $classtype='u' , $id = false  ) {
   $kinds = get_post_kind ($id);
   $classes = array();
   if ( ! $kinds || is_wp_error( $kinds ) )
            $kinds = array();
   foreach ( $kinds as $kind ) {
	    switch ($kind->slug) {
		     case "like":
                            $classes[] = $classtype.'-like-of';
			    $classes[] = $kind->slug;

	     	     break;
		     case "favorite":
			    $classes[] = $classtype.'-favorite-of';
			    $classes[] = $kind->slug;
		     break;
                     case "repost":
                            $classes[] = $classtype.'-repost-of';
			    $classes[] = $kind->slug;
                     break;
                     case "reply":
                            $classes[] = $classtype.'-in-reply-to';
			    $classes[] = $kind->slug;
                     break;
                     case "rsvp":
                            $classes[] = $classtype.'-in-reply-to';
                            $classes[] = $kind->slug;
                     break; 
                     case "tag":
                            $classes[] = $classtype.'-tag-of';
                            $classes[] = $kind->slug;
                     break;

     		     default:
			    $classes[] = $kind->slug;
		}


                }
   if ( ! empty( $class ) ) {
	                if ( !is_array( $class ) )
	                        $class = preg_split( '#\s+#', $class );
	                $classes = array_merge( $classes, $class );
	        } else {
	                // Ensure that we always coerce class to being an array.
	                $class = array();
   	        }
   $classes = array_map( 'esc_attr', $classes );
 /**
         * Filter the list of CSS kind classes for the current response URL.
         *
         *
         * @param array  $classes An array of kind classes.
         * @param string $class   A comma-separated list of additional classes added to the link.
         */
        return apply_filters( 'kind_classes', $classes, $class );
}

function kind_class( $class = '' ) {
        // Separates classes with a single space, collates classes
        echo 'class="' . join( ' ', get_kind_class( $class ) ) . '"';
}

function get_kind_verbs ( $id = false  ) {
   $kinds = get_the_kinds ($id);
   $verbs = array();
   if ( ! $kinds || is_wp_error( $kinds ) )
            $kinds = array();
   foreach ( $kinds as $kind ) {
            switch ($kind->slug) {
                     case "like":
                            $verbs[] = '<span class="like">Liked </span>';
                     break;
                     case "repost":
                            $verbs[] = '<span class="repost">Reposted </span>';
                     break;
                     case "reply":
                            $verbs[] = '<span class="reply">In Reply To </span>';
		     break;
		     case "favorite":
			    $verbs[] = '<span class="favorite">Favorited </span>'; 
		     break;
		     case "share":
			    $verbs[] = '<span class="share">Shared </span>';
		     break;
		     case "bookmark":
                            $verbs[] = '<span class="bookmark">Bookmarked </span>';
                     break;

		     case "rsvp":
			    $verbs[] = '<span class="rsvp">RSVPed </span>';
		     break;
		     case "checkin":
			    $verbs[] = '<span class="checkin">Checked in at </span>';
                     break;
                     case "tag":
                            $verbs[] = '<span class="tag">Tagged as </span>';
                     break;
		     case "note":
		     break;
		     case "article":
		     break;
		     case "image":
		     break;
                     default:
                            $verbs[] = '<span class="mention">Mentioned </span>';
                }


                }
 /**
         * Filter the list of CSS kind verbs for the current response URL.
         *
         *
         * @param array  $verbs An array of kind verbs.
         */
        return apply_filters( 'kind_verb', $verbs);
}

function kind_verbs() {
        // Separates verbs with an and, collates verbs
        echo join( ' and ', get_kind_verbs() ) . '"';
}

function kinds_post_class($classes) {
	// Adds kind classes to post
	$kinds = get_the_kinds($post->ID);
	foreach ( $kinds as $kind ) {		
		switch ($kind->slug) {
                     case "note":
                            $classes[] = 'h-as-note';
                     break;
		     case 'article':
			    $classes[] = 'h-as-article';
		     break;
		     case 'photo':
			    $classes[] = 'h-as-image';
		     break;
		     case 'bookmark':
			    $classes[] = 'h-as-bookmark';
		     break;
	    }
	return $classes;
	}

add_filter( 'post_class', 'kinds_post_class' );
 
		
			
}
?>
