<?php 

// Functions for Kind Taxonomies

function get_post_kind_slug( $post = null ) {
        $post = get_post($post);
        if ( ! $post = get_post( $post ) )
                        return false;
        $_kind = get_the_terms( $post->ID, 'kind' );
	if (!empty($_kind))
	    {
        	$kind = array_shift($_kind);
        	return $kind->slug;
	    }
	else { return false; }
   }


function get_post_kind( $post = null ) {
	$kind = get_post_kind_slug($post);
	if ($kind) 
	   {
		$strings = get_post_kind_strings();
        	return $strings[$kind];
	   }	
	else {
		return false; }        
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

/**
  * Returns an array of post kind slugs to their translated and pretty display versions
	 *

	 *
	 * @return array The array of translated post kind names.
	 */
	function get_post_kind_strings() {
	        $strings = array(
	                'article' => _x( 'Article', 'Post kind' ),
	                'note'    => _x( 'Note',    'Post kind' ),
	                'reply'     => _x( 'Reply',     'Post kind' ),
	                'repost'  => _x( 'Repost',  'Post kind' ),
	                'like'     => _x( 'Like',     'Post kind' ),
	                'favorite'    => _x( 'Favorite',    'Post kind' ),
	                'bookmark'    => _x( 'Bookmark',    'Post kind' ),
	                'photo'   => _x( 'Photo',   'Post kind' ),
	                'tag'    => _x( 'Tag',    'Post kind' ),
	                'rsvp'    => _x( 'RSVP',    'Post kind' ),
	        );
        return apply_filters( 'kind_strings', $strings );
	}

/**
  * Returns an array of post kind slugs to their translated verbs
         *

         *
         * @return array The array of translated post kind verbs.
         */
        function get_post_kind_verb_strings() {
               	$strings = array(
                        'article' => _x( ' ', 'Post kind verbs' ),
                       	'note'    => _x( ' ',    'Post kind verbs' ),
                        'reply'     => _x( 'In Reply To',     'Post kind verbs' ),
                        'repost'  => _x( 'Reposted',  'Post kind verbs' ),
                        'like'     => _x( 'Liked',     'Post kind verbs' ),
                        'favorite'    => _x( 'Favorited',    'Post kind verbs' ),
                        'bookmark'    => _x( 'Bookmarked',    'Post kind verbs' ),
                        'photo'   => _x( ' ',   'Post kind verbs' ),
                        'tag'    => _x( 'Tagged',    'Post kind verbs' ),
                        'rsvp'    => _x( 'RSVPed',    'Post kind verbs' ),
                );
               return apply_filters( 'kind_verbs', $strings );

        }


/**
 * Retrieves an array of post kind slugs.
 *
 * @return array The array of post kind slugs.
 */
function get_post_kind_slugs() {
	$slugs = array_keys( get_post_kind_strings() );
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
	$strings = get_post_kind_strings();
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

/**
 * Returns true if kind is a response type kind .
 *
 *
 * @param string $kind The post kind slug.
 * @return true/false.
 */
function response_kind( $kind ) {
        $not_responses = array( "article", "note" , "photo");
	if (in_array($kind, $not_responses)) { return false; }
	else { return true; }
}



function get_kind_context_class ( $class = '', $classtype='u' , $id = false  ) {
   $kind = get_post_kind_slug ($id);
   $classes = array();
   if ($kind)
       {
	   switch ($kind) {
		     case "like":
                            $classes[] = $classtype.'-like-of';

	     	     break;
		     case "favorite":
			    $classes[] = $classtype.'-favorite-of';
		     break;
                     case "repost":
                            $classes[] = $classtype.'-repost-of';
                     break;
                     case "reply":
                            $classes[] = $classtype.'-in-reply-to';
                     break;
                     case "rsvp":
                            $classes[] = $classtype.'-in-reply-to';
                     break; 
                     case "tag":
                            $classes[] = $classtype.'-tag-of';
		     break;
		     case "bookmark":
			    $classes[] = $classtype.'-bookmark-of';
                     break;
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

function kind_context_class( $class = '' ) {
        // Separates classes with a single space, collates classes
        echo 'class="' . join( ' ', get_kind_context_class( $class ) ) . '"';
}

function kinds_as_type($classes)
	{
 	  
          $kind = get_post_kind_slug();
          switch ($kind) {
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


function kinds_post_class($classes) {
	// Adds kind classes to post
	if (!is_singular() ) {
		$classes = kinds_as_type($classes);
	    }
        $classes[] = 'kind-' . get_post_kind_slug();
	return $classes;
	}

add_filter( 'post_class', 'kinds_post_class' ); 

function kinds_body_class($classes) {
        // Adds kind classes to body
        if (is_singular() ) {
                $classes = kinds_as_type($classes); 
            }
        return $classes;
        }

add_filter( 'body_class', 'kinds_body_class' );

	
?>
