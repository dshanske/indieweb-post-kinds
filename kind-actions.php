<?php
/**
Handler to Quickly Add a Reply/Like/Etc.
*/
add_action('init', array('Kind_Actions', 'init'));


class Kind_Actions {
  /**
   * Initialize the plugin.
   */
  public static function init() {
    add_filter('query_vars', array('Kind_Actions', 'query_var'));
    add_action('parse_query', array('Kind_Actions', 'parse_query'));
  }
  public static function query_var($vars) {
    $vars[] = 'indie-action';
    return $vars;
  }

  public static function parse_query($wp) {
    $data = array_merge_recursive( $_POST, $_GET );
    // check if it is an action request or not
    if (!array_key_exists('indie-action', $wp->query_vars)) {
      return;
    }
    if (!is_user_logged_in() ) {
//      status_header(400);
//      _e ('You must be logged in to post', 'Post kinds');
//     exit;
      auth_redirect();
    }
    $kind = $wp->query_vars['indie-action'];
    $kinds = array('reply', 'like', 'favorite', 'bookmark', 'repost');
    if (!in_array($kind, $kinds)) {
      header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
      status_header(400);
      _e ('Invalid Action', 'Post kinds');
      exit;
    }
    if (!current_user_can('publish_posts') ) {
      header('Content-Type: text/plain; charset=' . get_option('blog_charset'));
      status_header(403);  
      _e ('User Does Not Have Permission to Publish', 'Post kinds');
      exit;
    }

    if (!isset($data['url']) || isset($data['fill']) ) {
      header('Content-Type: text/html; charset=' . get_option('blog_charset'));
      Kind_Actions::post_form($kind);
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
    $args = apply_filters('pre_kind_action', $args);
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
    $cite[0]['name'] = sanitize_text_field( trim($data['title']) );
    if (isset($data['text']) ) {
      $cite[0]['content'] = wp_kses_post( trim($data['text']) );
    }
    if (isset($data['lat'])||isset($data['lon']) ) {
      update_post_meta($post_id, 'geo_latitude', sanitize_text_field(trim($data['lat'])) );
      update_post_meta($post_id, 'geo_longitude', sanitize_text_field(trim($data['lon'])) );
    }
    
    update_post_meta($post_id, 'mf2_cite', $cite); 
    // be sure to add an "exit;" to the end of your request handler
    do_action('after_kind_action', $post_id);
    // Return just the link to the new post
    status_header (200);
    echo get_permalink($post_id);
    // Optionally instead redirect to the new post
    // wp_redirect(get_permalink($post_id));
    exit;
  }

  public function post_form($kind) {
    echo '<title>';
    _e ('Quick Post', 'Post kinds');
    echo '</title>';
    echo '<h1>';
    _e ('Quick Post', 'Post kinds');
    echo ' - ' . $kind . '</h1>';
    echo '<form action="'. site_url()  . '/?indie-action=' . $kind . '" method="post">';
    echo '<p>';
    _e ('URL:', 'Post kinds'); 
    echo '<input type="url" name="url" size="70" /></p>';
    echo '<p>';
    _e('Name:', 'Post kinds');
    echo '<input type="text" name="title" size="70" /></p>';
    echo '<p>';
    _e('Author Name:', 'Post kinds');
    echo '<input type="text" name="author" size="70" /></p>';
    echo '<p>';
    _e('Publisher:', 'Post kinds');
    echo '<input type="text" name="publisher" size="70" /></p>';
    echo '<p>';
    _e('Content/Excerpt:', 'Post kinds');
    echo '<textarea name="text" rows="3" cols="70" ></textarea></p>';
    echo '<p><input type="submit" /></p>';
    echo '</form>';
  }


  /**
   * Download the source's HTML via server-side call
   *
   * @ courtesy of Press This enhancement
   *
   * @return string Source's HTML sanitized markup
   */
  public function fetch_source_html( $url ) {
    // Download source page to tmp file
    $source_tmp_file = ( ! empty( $url ) ) ? download_url( $url ) : '';
    $source_content  = '';
    if ( ! is_wp_error( $source_tmp_file ) && file_exists( $source_tmp_file ) ) {
      // Get the content of the source page from the tmp file.
      $source_content = wp_kses(
        file_get_contents( $source_tmp_file ),
        array(
          'img' => array(
            'src'      => array(),
          ),
          'iframe' => array(
            'src'      => array(),
          ),
          'link' => array(
            'rel'      => array(),
            'itemprop' => array(),
            'href'     => array(),
          ),
          'meta' => array(
            'property' => array(),
            'name'     => array(),
            'content'  => array(),
          )
        )
      );
      // All done with backward compatibility
      // Let's do some cleanup, for good measure :)
      unlink( $source_tmp_file );
    } else if ( is_wp_error( $source_tmp_file ) ) {
      $source_content = new WP_Error( 'upload-error',  sprintf( __( 'Error: %s' ), sprintf( __( 'Could not download the source URL (native error: %s).' ), $source_tmp_file->get_error_message() ) ) );
    } else if ( ! file_exists( $source_tmp_file ) ) {
      $source_content = new WP_Error( 'no-local-file',  sprintf( __( 'Error: %s' ), __( 'Could not save or locate the temporary download file for the source URL.' ) ) );
    }
    return $source_content;
  }

/**
   * Fetch and parse _meta, _img, and _links data from the source
   *
   * @courtesy of new Press This
   *
   * @param string $url
   * @param array $data Existing data array if you have one.
   *
   * @return array New data array
   */
  public function source_data_fetch( $url, $data = array() ) {
    if ( empty( $url ) ) {
      return array();
    }
    // Download source page to tmp file
    $source_content = $this->fetch_source_html( $url );
    if ( is_wp_error( $source_content ) ) {
      return array( 'errors' => $source_content->get_error_messages() );
    }
    // Fetch and gather <img> data
    if ( empty( $data['_img'] ) ) {
      $data['_img'] = array();
    }
    if ( preg_match_all( '/<img (.+)[\s]?\/>/', $source_content, $matches ) ) {
      if ( ! empty( $matches[0] ) ) {
        foreach ( $matches[0] as $value ) {
          if ( preg_match( '/<img[^>]+src="([^"]+)"[^>]+\/>/', $value, $new_matches ) ) {
            if ( ! in_array( $new_matches[1], $data['_img'] ) ) {
              $data['_img'][] = $new_matches[1];
            }
          }
        }
      }
    }
    // Fetch and gather <iframe> data
    if ( empty( $data['_embed'] ) ) {
      $data['_embed'] = array();
    }
    if ( preg_match_all( '/<iframe (.+)[\s][^>]*>/', $source_content, $matches ) ) {
      if ( ! empty( $matches[0] ) ) {
        foreach ( $matches[0] as $value ) {
          if ( preg_match( '/<iframe[^>]+src=(\'|")([^"]+)(\'|")/', $value, $new_matches ) ) {
            if ( ! in_array( $new_matches[2], $data['_embed'] ) ) {
              if ( preg_match( '/\/\/www\.youtube\.com\/embed\/([^\?]+)\?.+$/', $new_matches[2], $src_matches ) ) {
                $data['_embed'][] = 'https://www.youtube.com/watch?v=' . $src_matches[1];
              } else if ( preg_match( '/\/\/player\.vimeo\.com\/video\/([\d]+)([\?\/]{1}.*)?$/', $new_matches[2], $src_matches ) ) {
                $data['_embed'][] = 'https://vimeo.com/' . (int) $src_matches[1];
              } else if ( preg_match( '/\/\/vine\.co\/v\/([^\/]+)\/embed/', $new_matches[2], $src_matches ) ) {
                $data['_embed'][] = 'https://vine.co/v/' . $src_matches[1];
              }
            }
          }
        }
      }
    }
    // Fetch and gather <meta> data
    if ( empty( $data['_meta'] ) ) {
      $data['_meta'] = array();
    }
    if ( preg_match_all( '/<meta ([^>]+)[\s]?\/?>/  ', $source_content, $matches ) ) {
      if ( ! empty( $matches[0] ) ) {
        foreach ( $matches[0] as $key => $value ) {
          if ( preg_match( '/<meta[^>]+(property|name)="(.+)"[^>]+content="(.+)"/', $value, $new_matches ) ) {
            if ( empty( $data['_meta'][ $new_matches[2] ] ) ) {
              if ( preg_match( '/:?(title|description|keywords)$/', $new_matches[2] ) ) {
                $data['_meta'][ $new_matches[2] ] = str_replace( '&#039;', "'", str_replace( '&#034;', '', html_entity_decode( $new_matches[3] ) ) );
              } else {
                $data['_meta'][ $new_matches[2] ] = $new_matches[3];
                if ( 'og:url' == $new_matches[2] ) {
                  if ( false !== strpos( $new_matches[3], '//www.youtube.com/watch?' )
                       || false !== strpos( $new_matches[3], '//www.dailymotion.com/video/' )
                       || preg_match( '/\/\/vimeo\.com\/[\d]+$/', $new_matches[3] )
                       || preg_match( '/\/\/soundcloud\.com\/.+$/', $new_matches[3] )
                       || preg_match( '/\/\/twitter\.com\/[^\/]+\/status\/[\d]+$/', $new_matches[3] )
                       || preg_match( '/\/\/vine\.co\/v\/[^\/]+/', $new_matches[3] ) ) {
                    if ( ! in_array( $new_matches[3], $data['_embed'] ) ) {
                      $data['_embed'][] = $new_matches[3];
                    }
                  }
                } else if ( 'og:video' == $new_matches[2] || 'og:video:secure_url' == $new_matches[2] ) {
                  if ( preg_match( '/\/\/www\.youtube\.com\/v\/([^\?]+)/', $new_matches[3], $src_matches ) ) {
                    if ( ! in_array( 'https://www.youtube.com/watch?v=' . $src_matches[1], $data['_embed'] ) ) {
                      $data['_embed'][] = 'https://www.youtube.com/watch?v=' . $src_matches[1];
                    }
                  } else if ( preg_match( '/\/\/vimeo.com\/moogaloop\.swf\?clip_id=([\d]+)$/', $new_matches[3], $src_matches ) ) {
                    if ( ! in_array( 'https://vimeo.com/' . $src_matches[1], $data['_embed'] ) ) {
                      $data['_embed'][] = 'https://vimeo.com/' . $src_matches[1];
                    }
                  }
                } else if ( 'og:image' == $new_matches[2] || 'og:image:secure_url' == $new_matches[2] ) {
                  if ( ! in_array( $new_matches[3], $data['_img'] ) ) {
                    $data['_img'][] = $new_matches[3];
                  }
                }
              }
            }
          }
        }
      }
    }
    // Fetch and gather <link> data
    if ( empty( $data['_links'] ) ) {
      $data['_links'] = array();
    }
    if ( preg_match_all( '/<link ([^>]+)[\s]?\/>/', $source_content, $matches ) ) {
      if ( ! empty( $matches[0] ) ) {
        foreach ( $matches[0] as $key => $value ) {
          if ( preg_match( '/<link[^>]+(rel|itemprop)="([^"]+)"[^>]+href="([^"]+)"[^>]+\/>/', $value, $new_matches ) ) {
            if ( 'alternate' == $new_matches[2] || 'thumbnailUrl' == $new_matches[2] || 'url' == $new_matches[2] ) {
              if ( empty( $data['_links'][ $new_matches[2] ] ) ) {
                $data['_links'][ $new_matches[2] ] = $new_matches[3];
              }
            }
          }
        }
      }
    }
    return $data;
  }

}
