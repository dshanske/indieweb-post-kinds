<?php

// Functions Related to Display


// Add Response to Feed
add_filter('the_content_feed', 'kind_content_feed');

function kind_content_feed($content) {
  $response = get_kind_response_display();
  $response = str_replace(']]>', ']]&gt;', $response);
  return $response . $content;
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

$options = get_option('iwt_options');

function get_kind_response_display() {
	$meta = get_post_meta(get_the_ID(), '_resp_full', true);
	$options = get_option('iwt_options');
	if ( ($options['cacher']==1) && (!empty($meta)) )
	     {
        	return apply_filters( 'kind-response-display', $meta);
	     }
	$resp = "";
	$c = "";
	$kindmeta = get_kind_meta(get_the_ID());
  $kind = get_post_kind_slug();
  if ( (!$kind)||(!response_kind($kind)) ) {
            return apply_filters( 'kind-response-display', "");
  }
  $verb = kind_display_verb($kind);
  switch ($kind) {
    // Case for a Different Display for Check_In
//    case "checkin":
//      $resp .= kind_display_checkin($kindmeta);
//      break;
    default:
      if ( !isset($kindmeta['cite']) ) {
        return apply_filters( 'kind-response-display', "");
      }
      $resp .= kind_display_hcites($kindmeta['cite'], $kind);
  }
  // Wrap the entire display in the class response
  $c .= '<div class="response">' .  $resp . '</div>';
	update_post_meta( get_the_ID(), '_resp_full', $c); 
	// Return the resulting display.
	return apply_filters( 'kind-response-display', $c);
}

function invalidate_response($ID, $post)
   {
	delete_post_meta( get_the_ID(), '_resp_full' );
   }

add_action( 'publish_post', 'invalidate_response', 10, 2 );


function kind_response_display() {
	echo get_kind_response_display();
}

function content_response_top ($content ) {
    $c = "";
    $c .= get_kind_response_display();
    $c .= $content;
    return $c;
}

function content_response_bottom ($content ) {
    $c = "";
    $c .= $content;
    $c .= get_kind_response_display();
    return $c;
}

// If the Theme Has Not Declared Support for Post Kinds
// Add the Response Display to the Content Filter
function content_postkind() {
  if (!current_theme_supports('post-kinds')) {
    add_filter( 'the_content', 'content_response_top', 20 );
  }
}

add_action('init', 'content_postkind');

?>
