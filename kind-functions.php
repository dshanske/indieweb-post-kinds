<?php 

// Functions for Kind Taxonomies

// retrieve metadata from postmeta and return
// abstract retrieval from display
function get_kind_meta($post_id) {
  $response = get_post_meta($post_id, 'response', true);
  $kindmeta = array();
  // Retrieve either from response or from mf2_
  if ( !empty($response) ) {
     $kindmeta['cite'] = array();
     // Convert to new format and update
     if ( !empty($response['title']) ) {
      $kindmeta['cite']['name'] = $response['title'];
      }
     if ( !empty($response['url']) ) {
      $kindmeta['cite']['url'] = $response['url'];
      }
     if ( !empty($response['content']) ) {
      $kindmeta['cite']['content'] = $response['content'];
      }
     if ( !empty($response['published']) ) {
      $kindmeta['cite']['published'] = $response['published'];
      }
     if ( !empty($response['author']) ) {
      $kindmeta['card'] = array();
      $kindmeta['card']['name'] = $response['author'];
      if ( !empty($response['icon']) ) {
        $kindmeta['card']['photo'] = $response['icon'];
        }
      }
  if( isset($kindmeta['cite']) ) {
    update_post_meta($post_id, 'mf2_cite', $kindmeta['cite']);
    if( isset($kindmeta['card']) ) {
      update_post_meta($post_id, 'mf2_card', $kindmeta['card']);
      }
    delete_post_meta($post_id, 'response');
    return $kindmeta;
    }
  }
  $kindmeta['cite'] = get_post_meta($post_id, 'mf2_cite', true);
  $kindmeta['card'] = get_post_meta($post_id, 'mf2_card', true);
  $kindmeta['rsvp'] = get_post_meta($post_id, 'mf2_rsvp', true);
  $kindmeta['event'] = get_post_meta($post_id, 'mf2_event', true);
  return array_filter($kindmeta);
}

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
		     break;
		     case "listen":
                            $classes[] = $classtype.'-listen';
                     break;
                     case "watch":
                            $classes[] = $classtype.'-watch';
                     break;
                     case "game":
                            $classes[] = $classtype.'-play';
                     break;
                     case "wish":
                            $classes[] = $classtype.'-wish';
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
