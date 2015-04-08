<?php 
// Kind Meta Formatting Functions

/**
  * Returns an array of post kind slugs to their translated and pretty display versions
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
    'listen'   => _x( 'Listen', 'Post kind' ),
    'watch'   => _x( 'Watch', 'Post kind' ),
    'checkin'   => _x( 'Checkin', 'Post kind' ),
    'wish'   => _x( 'Wish', 'Post kind' ),
    'play'   => _x( 'Play', 'Post kind' ),
    'weather'   => _x( 'Weather', 'Post kind' ),
    'exercise'   => _x( 'Exercise', 'Post kind' ),
    'travel'   => _x( 'Travel', 'Post kind' )
  );
  return apply_filters( 'kind_strings', $strings );
 }

/**
  * Returns an array of post kind slugs to their pluralized translated and pretty display versions
  *
  * @return array The array of translated post kind names.
  */
function get_post_kind_strings_plural() {
  $strings = array(
    'article' => _x( 'Articles', 'Post kind' ),
    'note'    => _x( 'Notes',    'Post kind' ),
    'reply'     => _x( 'Replies',     'Post kind' ),
    'repost'  => _x( 'Reposts',  'Post kind' ),
    'like'     => _x( 'Likes',     'Post kind' ),
    'favorite'    => _x( 'Favorites',    'Post kind' ),
    'bookmark'    => _x( 'Bookmarks',    'Post kind' ),
    'photo'   => _x( 'Photos',   'Post kind' ),
    'tag'    => _x( 'Tags',    'Post kind' ),
    'rsvp'    => _x( 'RSVPs',    'Post kind' ),
    'listen'   => _x( 'Listens', 'Post kind' ),
    'watch'   => _x( 'Watches', 'Post kind' ),
    'checkin'   => _x( 'Checkins', 'Post kind' ),
    'wish'   => _x( 'Wishlist', 'Post kind' ),
    'play'   => _x( 'Plays', 'Post kind' ),
    'weather'   => _x( 'Weather', 'Post kind' ),
    'exercise'   => _x( 'Exercises', 'Post kind' ),
    'travel'   => _x( 'Travels', 'Post kind' )
  );
  return apply_filters( 'kind_strings_plural', $strings );
}

/**
  * Returns an array of post kind slugs to their translated verbs
  *
  * @return array The array of translated post kind verbs.
  */
function get_post_kind_verb_strings() {
  $strings = array(
    'article' => _x( ' ', 'Post kind' ),
    'note'    => _x( ' ',    'Post kind' ),
    'reply'     => _x( 'In Reply To',     'Post kind' ),
    'repost'  => _x( 'Reposted',  'Post kind' ),
    'like'     => _x( 'Liked',     'Post kind' ),
    'favorite'    => _x( 'Favorited',    'Post kind' ),
    'bookmark'    => _x( 'Bookmarked',    'Post kind' ),
    'photo'   => _x( ' ',   'Post kind' ),
    'tag'    => _x( 'Tagged',    'Post kind' ),
    'rsvp'    => _x( 'RSVPed',    'Post kind' ),
    'listen'    => _x( 'Listened to ',    'Post kind' ),
    'watch'   => _x( 'Watched', 'Post kind' ),
    'checkin'   => _x( 'Checked In At', 'Post kind' ),
    'wish'   => _x( 'Desires', 'Post kind' ),
    'play'   => _x( 'Played', 'Post kind' ),
    'weather'   => _x( 'Weathered', 'Post kind' ),
    'exercise'   => _x( 'Exercised', 'Post kind' ),
    'travel'   => _x( 'Traveled', 'Post kind' )
  );
  return apply_filters( 'kind_verbs', $strings );
}

/**
  * Uses an array of post kind slugs with the author terminologies
  *
  * @return array The appropriate post kind author string.
  */
function get_post_kind_author_string($verb) {
  $strings = array(
    'article' => _x( 'by', 'Post kind' ),
  );
  $strings = apply_filters( 'kind_author_string', $strings );
  if (array_key_exists($verb, $strings) ) {
    return $strings[$verb];
  }
  else {
    return _x('by', 'Post kind');
  }
}

/**
  * Returns the publication terminology for the publication
  *
  * @return array The post kind publication string.
  */
function get_post_kind_publication_string($verb) {
  $strings = array(
    'article' => _x( 'on', 'Post kind' ),
    'listen' => _x( '-', 'Post kind' ),
    'watch' => _x( '-', 'Post kind' )
  );
  $strings = apply_filters( 'kind_publication_string', $strings );
  if (array_key_exists($verb, $strings) ) {
    return $strings[$verb];
  }
  else {
    return _x('on', 'Post kind');
  }
}

/**
  * Returns an array of domains with the post type terminologies
  *
  * @return array A translated post type string for specific domain or 'a post'
  */
function get_post_kind_post_type_string($url) {
  $strings = array(
    'twitter.com' => _x( 'a tweet', 'Post kind' ),
    'vimeo.com' => _x( 'a video', 'Post kind' ),
    'youtube.com'   => _x( 'a video', 'Post kind' )
  );
  $domain = extract_domain_name($url);
  if (array_key_exists($domain, $strings) ) {
    return apply_filters( 'kind_post_type_string', $strings[$domain] );
  }
  else {
    return _x('a post', 'Post kind'); 
  }
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
  $verbstrings = get_post_kind_verb_strings();
  $verb = '<span class="verb"><strong>' . $verbstrings[$v] . '</strong>';
  return $verb;
}

// Take the url and return the domain name marked up
function kind_display_domain($url, $verb) {
  if (empty($url) ) { return ""; }
  $domain = '<em>' . get_post_kind_publication_string($verb) . ' ' . extract_domain_name($url) . '</em>';
  return $domain;
}

// Take the url and return the domain name marked up
function kind_display_publication($publish, $verb) {
  if (empty($publish) ) { return ""; }
  $pub = '<em>' . get_post_kind_publication_string($verb) . ' ' . '<span class="p-publication">' . $publish . '</span></em>';
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
    $cards = ' ' . get_post_kind_author_string($verb) . ' ' . $cards;
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
