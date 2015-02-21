<?php

// Functions Related to Display


$options = get_option('iwt_options');

// Extracts the Domain Name for a URL for presentation purposes
if (!function_exists('extract_domain_name'))
  {
    function extract_domain_name($url) {
      $host = parse_url($url, PHP_URL_HOST);
      $host = preg_replace("/^www\./", "", $host);
      return $host;
    }	
  }

// Take the Kind Meta and return the author formatted appropriately
function get_kindmeta_author($kindmeta, $kind) {
  // If there is card data, use that
  $author = false;
  switch ($kind) {
    default:
      if ( isset($kindmeta['card']) ) {
        $author = '<span class="p-author h-card"> ';
        if (! empty($kindmeta['card']['photo']) ) {
          $author .= '<img class="u-photo" src="' . $kindmeta['card']['photo'] . '" title="' . $kindmeta['card']['name'] . '" />';
        }
        $author .= $kindmeta['card']['name'] . '</span>';
      }
  }
  return $author;
}

// Take the Kind Meta and the kind and return the appropriate URL marked up
function get_kindmeta_url($kindmeta, $kind) {
  $url = false;
  switch ($kind) {
    default:
      if (! empty($kindmeta['cite']['url'])  ) {
        if (!empty ($kindmeta['cite']['name']) ) {
          $url = ' ' . '<a href="' . $kindmeta['cite']['url'] . '">' . $kindmeta['cite']['name'] . '</a>';
        }
        else {
          $url = ' ' . '<a href="' . $kindmeta['cite']['url'] . '">' . get_the_title() . '</a>';
        }
      }
  }
  return $url;
}

// Take the Kind Meta and the kind and return the appropriate verb marked up
function get_kindmeta_verb($kindmeta, $kind) {
  $verb = false;
  $verbstrings = get_post_kind_verb_strings();
  switch ($kind) {
    default:
      $verb = '<span class="verb"><strong>' . $verbstrings[$kind] . '</strong>';
  }
  return $verb;
}

// Take the Kind Meta and return the domain name marked up
function get_kindmeta_domain($kindmeta, $kind) {
  $domain = false;
  switch ($kind) {
    default:
      if ( isset($kindmeta['cite']['url']) ) {
        $domain = ' (<em>' . extract_domain_name($kindmeta['cite']['url']) . '</em>)';
      }
  }
  return $domain;
}

// Take the Kind Meta and return the content marked up
function get_kindmeta_content($kindmeta, $kind) {
  $content = false;
  switch ($kind) {
    default:
    if ( !empty ($kindmeta['cite']['content']) ) {
      $content = '<blockquote class="p-content">' . $kindmeta['cite']['content'] . '</blockquote>';
    }
  }
  return $content;
}

// Take the Kind Meta and return the name marked up
function get_kindmeta_name($kindmeta, $kind) {
  $name = false;
  switch ($kind) {
    default:
      $name = '<span class="p-name">' . $kindmeta['cite']['name'] . '</span>';
  }
  return $name;
}


// Take the Kind Meta and return the embed marked up
function get_kindmeta_embeds($kindmeta, $kind) {
  $embeds = false;
  $options = get_option('iwt_options');
  switch ($kind) {
    default:
      if($options['embeds'] == 1) {
          if ( !empty($kindmeta['cite']['url']) ) {
            $embed_code = new_embed_get($kindmeta['cite']['url']);
            if ($embed_code != false) {
              $embeds = '<div class="embeds">' . $embed_code . '</div>';
            }
          }
      }
  }
  return $embeds;
}


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
	$verb = get_kindmeta_verb($kindmeta, $kind);
  $author = get_kindmeta_author($kindmeta, $kind);
  $url = get_kindmeta_url($kindmeta, $kind);
  $domain = get_kindmeta_domain($kindmeta, $kind);
  $content = get_kindmeta_content($kindmeta, $kind);
  $name = get_kindmeta_name($kindmeta, $kind);
  if ($verb != false) {
    $resp .= $verb;
  }
  if ($url != false) {
    $resp .= $url;
  }  
  elseif ($name != false) {
    $resp .= $name;
  }
  if ( $author != false ) {
      $resp .= __(" by ", "Post kinds") . $author;
    }
  if ( $domain != false ) {
    $resp .= $domain;
  }
	if ( $content != false ) {
    $resp .= $content;
  }
  $embeds = get_kindmeta_embeds($kindmeta, $kind);
	if ($embeds != false) {
    $resp .= $embeds;
  }
  // Wrap the entire display in the class response
  $c .= '<div class="' .  implode(' ',get_kind_context_class ( 'h-cite response', 'p' )) . '">' . $resp . '</div>';
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
