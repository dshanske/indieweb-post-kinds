<?php 

// Functions for Kind Taxonomies


function is_multi_array( $arr ) {
  rsort( $arr );
  return isset( $arr[0] ) && is_array( $arr[0] );
}

// retrieve metadata from postmeta and return
// abstract retrieval from display
function get_kind_meta($post_id) {
  $response = get_post_meta($post_id, 'response', true);
  $kindmeta = array();
  // Retrieve from the old response array and store as the first
  // entry in a new multidimensional array
  if ( !empty($response) ) {
     $kindmeta['cite'] = array();
     $kindmeta['cite'][0] = array();
     // Convert to new format and update
     if ( !empty($response['title']) ) {
      $kindmeta['cite'][0]['name'] = $response['title'];
      }
     if ( !empty($response['url']) ) {
      $kindmeta['cite'][0]['url'] = $response['url'];
      }
     if ( !empty($response['content']) ) {
      $kindmeta['cite'][0]['content'] = $response['content'];
      }
     if ( !empty($response['published']) ) {
      $kindmeta['cite'][0]['published'] = $response['published'];
      }
     if ( !empty($response['author']) ) {
      $kindmeta['cite'][0]['card'] = array();
      $kindmeta['cite'][0]['card'][0] = array();
      $kindmeta['cite'][0]['card'][0]['name'] = $response['author'];
      if ( !empty($response['icon']) ) {
        $kindmeta['cite'][0]['card'][0]['photo'] = $response['icon'];
        }
      }
  if( isset($kindmeta['cite']) ) {
    update_post_meta($post_id, 'mf2_cite', $kindmeta['cite']);
    delete_post_meta($post_id, 'response');
    return $kindmeta;
    }
  }
  $props = array('cite', 'card', 'category', 'content', 'description', 'end', 
  'h', 'in-reply-to','like', 'like-of', 'location', 'name', 'photo', 
  'published', 'repost', 'repost-of', 'rsvp', 'slug', 'start', 'summary',
  'syndication', 'syndicate-to');
  foreach ($props as $prop) {
    $key = 'mf2_' . $prop;
    $kindmeta[$prop] = get_post_meta($post_id, $key, true);
  }
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
