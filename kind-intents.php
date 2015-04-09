<?php
/**
Endpoint to Quickly Add a Reply/Like/Etc.
*/
add_action('init', array('Kind_Intents', 'init'));


class Kind_Intents {
  /**
   * Initialize the plugin.
   */
  public static function init() {
    add_filter('query_vars', array('Kind_Intents', 'query_var'));
    add_action('parse_query', array('Kind_Intents', 'parse_query'));
  }
  public static function query_var($vars) {
    $vars[] = 'intent';
    return $vars;
  }

  public static function parse_query($wp) {
    $data = array_merge_recursive( $_POST, $_GET );
    // check if it is a intent request or not
    if (!array_key_exists('intent', $wp->query_vars)) {
      return;
    }
    if (!is_user_logged_in() ) {
//      status_header(400);
//      _e ('You must be logged in to post', 'Post kinds');
//     exit;
      auth_redirect();
    }
    $kind = $wp->query_vars['intent'];
    $kinds = array('reply', 'like', 'favorite', 'bookmark', 'repost');
    // plain text header
    header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
    // check if source url is transmitted
    if (!in_array($kind, $kinds)) {
      status_header(400);
      _e ('Invalid Intent', 'Post kinds');
      exit;
    }
    if (!current_user_can('publish_posts') ) {
      status_header(403);  
      _e ('User Does Not Have Permission to Publish', 'Post kinds');
      exit;
    }

    if (!isset($data['url']) ) {
      status_header(400);
      _e ('A URL must be provided', 'Post kinds');
      exit;
    }
    if (filter_var($data['url'], FILTER_VALIDATE_URL) === false) {
      status_header(400);
      _e ('The URL is Invalid', 'Post kinds');
      exit;
    }
    $args = array (
      'post_content' => ' ',
      'post_status'    => 'private', // Defaults to private
      'post_type' => 'post',
      'post_title' => current_time('Gis') // Post Title is the time
    );
    // If public is past, the post status is publish
    if (isset($data['public']) ) {
      $args['post_status'] = 'publish';
    }
    if (isset($data['content']) ) {
      $args['post_content'] = wp_kses_post( trim($data['content']) );
    }
    // tags will map to a category if exists, otherwise a tag
    if (isset($data['tags'])) {
      foreach ($data['tags'] as $mp_cat) {
        $wp_cat = get_category_by_slug($mp_cat);
        if ($wp_cat) {
          $args['post_category'][] = $wp_cat->term_id;
        } else {
          $args['tags_input'][] = $mp_cat;
        }
      }
    }
    $args = apply_filters('pre_kind_intent', $args);
    $post_id = wp_insert_post($args, true);  
    if (is_wp_error($post_id) ) {
        status_header(400);
        echo $post_id->get_error_message();
        exit;
    }
    wp_set_object_terms($post_id, $kind, 'kind');
    $cite = array();
    $cite[0] = array();
    $cite[0]['url'] = esc_url($data['url']);
    $cite[0]['name'] = sanitize_title( trim($data['title']) );
    if (isset($data['text']) ) {
      $cite[0]['content'] = wp_kses_post( trim($data['text']) );
    }
    if (isset($data['lat'])||isset($data['lon']) ) {
      update_post_meta($post_id, 'geo_latitude', sanitize_text_field(trim($data['lat'])) );
      update_post_meta($post_id, 'geo_longitude', sanitize_text_field(trim($data['lon'])) );
    }
    
    update_post_meta($post_id, 'mf2_cite', $cite); 
    // be sure to add an "exit;" to the end of your request handler
    do_action('after_kind_intent', $post_id);
    // Return just the link to the new post
    status_header (200);
    echo get_permalink($post_id);
    // Optionally instead redirect to the new post
    // wp_redirect(get_permalink($post_id));
    exit;
  }

}
