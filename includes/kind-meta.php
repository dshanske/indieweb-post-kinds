<?php 
// Kind Meta Formatting Functions

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


// Take an hcard and return it displayed properly
function kind_display_card($hcard) {
  if (empty($hcard) ) { return ""; }
  $author = '<span class="p-author h-card"> ';
  if (! empty($hcard['photo']) ) {
          $author .= '<img class="u-photo" src="' . $hcard['photo'] . '" title="' . $hcard['name'] . '" />';
  }
  $author .= $hcard['name'] . '</span>';
  return $author;
}

// Takes an array of hcards and returns it displayed properly
function kind_display_hcards($cards) {
  foreach ($cards as $key => $value) {
    $authors[] = kind_display_card($value);
  }
  return implode(_x(' and ', 'Post kind') , $authors);
}

// Mark up a url appropriately
function kind_display_url($cite) {
  if (empty($cite) ) { return ""; }
  if (! empty($cite['url'])  ) {
    if (!empty ($cite['name']) ) {
          $url = ' ' . '<a class="u-url" href="' . $cite['url'] . '">' . '<span class="p-name">' . $cite['name'] . '</span>' . '</a>';
    }
    else {
      $url = ' ' . '<a class="u-url" href="' . $cite['url'] . '">' . '<span class="p-name">' . get_post_kind_post_type_string($cite['url']) . '</span>' . '</a>';
    }
  }
  return $url;
}

// Take the verb and the kind and return the appropriate verb marked up
function kind_display_verb($v) {
  $verbstrings = kind_taxonomy::get_verb_strings();
  $verb = '<span class="verb"><strong>' . $verbstrings[$v] . '</strong>';
  return $verb;
}

// Take the url and return the domain name marked up
function kind_display_domain($url, $verb) {
  if (empty($url) ) { return ""; }
  $domain = '<em>' . kind_taxonomy::get_publication_string($verb) . ' ' . extract_domain_name($url) . '</em>';
  return $domain;
}

// Take the url and return the domain name marked up
function kind_display_publication($publish, $verb) {
  if (empty($publish) ) { return ""; }
  $pub = '<em>' . kind_taxonomy::get_publication_string($verb) . ' ' . '<span class="p-publication">' . $publish . '</span></em>';
  return $pub;
}


// Take the content and return the content marked up
function kind_display_content($c) {
  if (empty($c) ) { return ""; }
  $content = '<blockquote class="p-content">' . $c . '</blockquote>';
  return $content;
}

// Take the name and return it marked up
function kind_display_name($n) {
  if (empty($n) ) { return ""; }
  $name = '<span class="p-name">' . $n . '</span>';
  return $name;
}

// Take the Kind Meta and return the embed marked up
function kind_display_embeds($url) {
  if (empty($url) ) { return ""; }
  $options = get_option('iwt_options');
  if($options['embeds'] == 1) {
    $embed_code = new_embed_get($url);
    if ($embed_code != false) {
      $embeds = '<div class="embeds">' . $embed_code . '</div>';
      return $embeds;
    }
    return "";
  }
  return false;
}

// Display a Cite
function kind_display_cite($cite, $verb) {
  $domain = "";
  $name = "";
  $embed = "";
  $cards = "";
  $content = "";
  $v = kind_display_verb($verb) . ' ';
  if ( isset($cite['card']) ) {
    $cards = kind_display_hcards($cite['card']);
    $cards = ' ' . kind_taxonomy::get_author_string($verb) . ' ' . $cards;
  }
  if ( isset($cite['url']) ) {
    $name = kind_display_url($cite);
    $embed = kind_display_embeds($cite['url']);
    if ( isset($cite['publication']) ) {
      $domain = ' ' . kind_display_publication($cite['publication'], $verb);
    }
    else {
      $domain = ' ' . kind_display_domain($cite['url'], $verb);
    }
  }
  else {
    $name = kind_display_name($cite['name']);
    if ( isset($cite['publication']) ) {
      $domain = ' ' . kind_display_publication($cite['publication'], $verb);
    }
  }
  if ( isset($cite['content']) ) {
    $content = kind_display_content($cite['content']);
  }
  $c = $v . $name . $domain . $cards . $embed . $content;
  return apply_filters('kind_display_cite', $c, $cite, $verb);
}

// Takes an array of cites and returns it displayed properly
function kind_display_hcites($cites, $verb) {
  $response = '<ul class="cites">';
  foreach ($cites as $key => $value) {
    $response .= '<li class="' . implode(' ',get_kind_context_class ( 'h-cite', 'p' )) . '">' . kind_display_cite($value, $verb) . '</li>';
  }
  $response .= '</ul>';
  return $response;
}
