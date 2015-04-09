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
    // check if it is a intent request or not
    if (!array_key_exists('intent', $wp->query_vars)) {
      return;
    }
    $kind = $wp->query_vars['intent'];
    $kinds = array('reply', 'like', 'favorite', 'bookmark');
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

    if (!isset($_GET['url']) ) {
      status_header(400);
      _e ('A URL must be provided', 'Post kinds');
      exit;
    }
    if (filter_var($_GET['url'], FILTER_VALIDATE_URL) === false) {
      status_header(400);
      _e ('The URL is Invalid', 'Post kinds');
      exit;
    }
    $args = array (
      'post_content' => ' ',
      'post_status'    => 'private'
    );
    if (isset($_GET['title']) ) {
      $args['post_title']=sanitize_title($_GET['title']);
    }
    else {
      $args['post_title']=current_time('Gis');
    }
    if (isset($_GET['public']) ) {
      $args['post_status'] = 'publish';
    }
    $post_id = wp_insert_post($args, true);  
    if (is_wp_error($post_id) ) {
        status_header(400);
        echo $post_id->get_error_message();
        exit;
    }
    wp_set_object_terms($post_id, $kind, 'kind');
    $cite = array();
    $cite[0] = array();
    $cite[0]['url'] = esc_url($_GET['url']);
    if (isset($_GET['quote']) ) {
      $cite[0]['content'] = wp_kses_post($_GET['quote']);
    }
    update_post_meta($post_id, 'mf2_cite', $cite); 
    // be sure to add an "exit;" to the end of your request handler
    status_header(200);
    echo get_permalink($post_id);
    exit;
  }

}
