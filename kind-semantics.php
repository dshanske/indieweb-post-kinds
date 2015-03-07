<?php

//if (class_exists('SemanticLinkbacksPlugin') ) {
//  add_filter('comment_text', 'kind_comment_text_excerpt', 12, 3);
//}

// Replacement for the Semantic Linkbacks Comment Excerpt
function kind_comment_text_excerpt($text, $comment = null, $args = array()) {
  // only change text for pingbacks/trackbacks/webmentions
    if (!$comment || $comment->comment_type == "" || !get_comment_meta($comment->comment_ID, "semantic_linkbacks_canonical", true)) {
      return $text;
    }
    // check comment type
    $comment_type = get_comment_meta($comment->comment_ID, "semantic_linkbacks_type", true);
    if (!$comment_type || !in_array($comment_type, array_keys(SemanticLinkbacksPlugin::get_comment_type_strings()))) {
      $comment_type = "mention";
    }
    $_kind = get_the_terms( $comment->comment_post_ID, 'kind' );
    if (!empty($_kind))
      {
          $kind = array_shift($_kind);
          $kindstrings = get_post_kind_strings();
          $post_format = $kindstrings[$kind->slug];
      }
    else {
      $post_format = get_post_format($comment->comment_post_ID);
      // replace "standard" with "Article"
      if (!$post_format || $post_format == "standard") {
        $post_format = "Article";
      } else {
        $post_formatstrings = get_post_format_strings();
        // get the "nice" name
        $post_format = $post_formatstrings[$post_format];
      }
    }
    // generate the verb, for example "mentioned" or "liked"
    $comment_type_excerpts = SemanticLinkbacksPlugin::get_comment_type_excerpts();
    // get URL canonical url...
    $url = get_comment_meta($comment->comment_ID, "semantic_linkbacks_canonical", true);
    // ...or fall back to source
    if (!$url) {
      $url = get_comment_meta($comment->comment_ID, "semantic_linkbacks_source", true);
    }
    // parse host
    $host = parse_url($url, PHP_URL_HOST);
    // strip leading www, if any
    $host = preg_replace("/^www\./", "", $host);
    // generate output
    $text = sprintf($comment_type_excerpts[$comment_type], get_comment_author_link($comment->comment_ID), $post_format, $url, $host);
    return apply_filters("semantic_linkbacks_excerpt", $text);
  }
