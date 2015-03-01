<?php

// Functions Related to Display


$options = get_option('iwt_options');

function get_kind_response_display() {
	$meta = get_post_meta(get_the_ID(), '_resp_full', true);
	$options = get_option('iwt_options');
	if ( ($options['cacher']!=1) && (!empty($meta)) )
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
  if ( !isset($kindmeta['cite']) ) {
         return apply_filters( 'kind-response-display', "");
  }
	$verb = kind_display_verb($kind);
  $resp .= kind_display_hcites($kindmeta['cite'], $kind);
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
