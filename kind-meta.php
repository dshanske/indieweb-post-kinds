<?php 
// Kind Meta Display Function

/**
  * Returns an array of post kind slugs to their translated verbs
         *

         *
         * @return array The array of translated post kind verbs.
         */
        function get_post_kind_phrases() {
                $strings = array(
                        'article' => _x( ' ', 'Post kind' ),
                        'note'    => _x( ' ',    'Post kind' ),
                        'reply'     => _x( '<span class="verb">In Reply To</span> %1s',     'Post kind' ),
                        'repost'  => _x( 'Reposted %1s',  'Post kind' ),
                        'like'     => _x( 'Liked %1s',     'Post kind' ),
                        'favorite'    => _x( 'Favorited %1s',    'Post kind' ),
                        'bookmark'    => _x( 'Bookmarked %1s',    'Post kind' ),
                        'photo'   => _x( ' ',   'Post kind' ),
                        'tag'    => _x( 'Tagged %1s',    'Post kind' ),
                        'rsvp'    => _x( 'RSVPed %1s',    'Post kind' ),
                        'listen'    => _x( 'Listened to %1s',    'Post kind' ),
                        'watch'   => _x( 'Watched', 'Post kind' ),
                        'checkin'   => _x( 'Checked In', 'Post kind' ),
                        'wish'   => _x( 'Desires', 'Post kind' ),
                        'play'   => _x( 'Played', 'Post kind' )
                );
               return apply_filters( 'kind_phrases', $strings );

        }


// Extracts the Domain Name for a URL for presentation purposes
if (!function_exists('extract_domain_name')) {
    function extract_domain_name($url) {
      $host = parse_url($url, PHP_URL_HOST);
      $host = preg_replace("/^www\./", "", $host);
      return $host;
    }
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
  return implode(" and ", $authors);
}

// Mark up a url appropriately
function kind_display_url($cite) {
  if (empty($cite) ) { return ""; }
  if (! empty($cite['url'])  ) {
    if (!empty ($cite['name']) ) {
          $url = ' ' . '<a class="u-url" href="' . $cite['url'] . '">' . '<span class="p-name">' . $cite['name'] . '</span>' . '</a>';
    }
    else {
      $url = ' ' . '<a class="u-url" href="' . $cite['url'] . '">' . '<span class="p-name">' . _x ('a post', 'Post kinds') . '</span>' . '</a>';
    }
  }
  return $url;
}

// Take the verb and the kind and return the appropriate verb marked up
function kind_display_verb($v) {
  $verbstrings = get_post_kind_verb_strings();
  $verb = '<span class="verb"><strong>' . $verbstrings[$v] . '</strong>';
  return $verb;
}

// Take the url and return the domain name marked up
function kind_display_domain($url) {
  if (empty($url) ) { return ""; }
  $domain = ' (<em>' . extract_domain_name($url) . '</em>)';
  return $domain;
}

// Take the url and return the domain name marked up
function kind_display_publication($publish) {
  if (empty($publish) ) { return ""; }
  $pub = ' (<em><span class="p-publication">' . $publish . '</span></em>)';
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
  $v = kind_display_verb($verb);
  if ( isset($cite['card']) ) {
    $cards = kind_display_hcards($cite['card']);
    $cards = __('by', "Post kinds") . ' ' . $cards;
  }
  if ( isset($cite['url']) ) {
    $name = kind_display_url($cite);
    $embed = kind_display_embeds($cite['url']);
    if ( isset($cite['publication']) ) {
      $domain = kind_display_publication($cite['publication']);
    }
    else {
      $domain = kind_display_domain($cite['url']);
    }
  }
  else {
    $name = kind_display_name($cite['name']);
    if ( isset($cite['publication']) ) {
      $domain = kind_display_publication($cite['publication']);
    }
  }
  if ( isset($cite['content']) ) {
    $content = kind_display_content($cite['content']);
  }
  $c = $v . ' ' . $name . ' ' . $cards . $domain . $embed . $content;
  return $c;
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


