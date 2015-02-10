<?php
/**
 * Plugin Name: IndieWeb Post Kinds
 * Plugin URI: https://github.com/dshanske/indieweb-post-kinds
 * Description: Adds a semantic layer to Posts similar in usage to post formats, allowing them to be classified as likes, replies, favorites, etc.
 * Version: 1.0.1
 * Author: David Shanske
 * Author URI: http://david.shanske.com
 * Text Domain: Post kinds
 */

if ( ! defined( 'POST_KIND_EXCLUDE' ) )
    define('POST_KIND_EXCLUDE', ' ');

// Register Kind to Distinguish the Types of Posts

// require_once( plugin_dir_path( __FILE__ ) . 'class.taxonomy-single-term.php');
// require_once( plugin_dir_path( __FILE__ ) . 'walker.taxonomy-single-term.php');


require_once( plugin_dir_path( __FILE__ ) . 'kind-select.php');


require_once( plugin_dir_path( __FILE__ ) . '/iwt-config.php');
// Add Kind Post Metadata
require_once( plugin_dir_path( __FILE__ ) . '/kind-postmeta.php');
// Add Kind Functions
require_once( plugin_dir_path( __FILE__ ) . '/kind-functions.php');
// Add Kind Display Functions
require_once( plugin_dir_path( __FILE__ ) . '/kind-view.php');
// Add Embed Functions for Commonly Embedded Websites not Supported by Wordpress
require_once( plugin_dir_path( __FILE__ ) . '/embeds.php');

// Load Dashicons or Genericons in Front End in Order to Use Them in Response Display
// Load a local stylesheet
add_action( 'wp_enqueue_scripts', 'kindstyle_load' );
function kindstyle_load() {
        wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'kind.min.css');
  }

function kind_admin_style() {
    wp_enqueue_style('kind-admin', plugins_url('kind-admin.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'kind_admin_style');

function it_publish ( $ID, $post=null)
  {
     $response_url = get_post_meta($ID, 'response_url', true);
     if (!empty($response_url))
	 {
     		send_webmention(get_permalink($ID), $response_url);
 	 }
  }


add_filter('publish_post', 'it_publish', 10, 3);

add_action( 'init', 'register_taxonomy_kind' );

function register_taxonomy_kind() {

        $labels = array( 
        'name' => _x( 'Kinds', 'kind' ),
        'singular_name' => _x( 'Kind', 'kind' ),
        'search_items' => _x( 'Search Kinds', 'kind' ),
        'popular_items' => _x( 'Popular Kinds', 'kind' ),
        'all_items' => _x( 'All Kinds', 'kind' ),
        'parent_item' => _x( 'Parent Kind', 'kind' ),
        'parent_item_colon' => _x( 'Parent Kind:', 'kind' ),
        'edit_item' => _x( 'Edit Kind', 'kind' ),
        'update_item' => _x( 'Update Kind', 'kind' ),
        'add_new_item' => _x( 'Add New Kind', 'kind' ),
        'new_item_name' => _x( 'New Kind', 'kind' ),
        'separate_items_with_commas' => _x( 'Separate kinds with commas', 'kind' ),
        'add_or_remove_items' => _x( 'Add or remove kinds', 'kind' ),
        'choose_from_most_used' => _x( 'Choose from the most used kinds', 'kind' ),
        'menu_name' => _x( 'Kinds', 'kind' ),
    );

    $args = array( 
        'labels' => $labels,
        'public' => true,
        'show_in_nav_menus' => true,
        'show_ui' => false,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'rewrite' => true,
        'query_var' => true
    );

    register_taxonomy( 'kind', array('post'), $args );
}

// Sets up some starter terms...unless terms already exist 
// or any of the existing terms are defined
function kind_defaultterms () {

    // see if we already have populated any terms
    $kinds = get_terms( 'kind', array( 'hide_empty' => false ) );
	if (!term_exists('like', 'kind')) {
	      wp_insert_term('like', 'kind', 
		array(
   		 	  'description'=> 'Like',
    			  'slug' => 'like',
		     ) );

            }  
        if (!term_exists('favorite', 'kind')) {
              wp_insert_term('favorite', 'kind',
                array(
                          'description'=> 'Favorite',
                          'slug' => 'favorite',
                     ) );

            } 
        if (!term_exists('reply', 'kind')) {
              wp_insert_term('reply', 'kind',
                array(
                          'description'=> 'Reply',
                          'slug' => 'reply',
                     ) );

            }
        if (!term_exists('rsvp', 'kind')) {
              wp_insert_term('rsvp', 'kind',
                array(
                          'description'=> 'RSVP for Event',
                          'slug' => 'rsvp',
                     ) );

            }
        if (!term_exists('repost', 'kind')) {
              wp_insert_term('repost', 'kind',
                array(
                          'description'=> 'Repost',
                          'slug' => 'repost',
                     ) );

            }
        if (!term_exists('bookmark', 'kind')) {
              wp_insert_term('bookmark', 'kind',
                array(
                          'description'=> 'Sharing a Link',
                          'slug' => 'bookmark',
                     ) );

            }
        if (!term_exists('tag', 'kind')) {
              wp_insert_term('Tag', 'kind',
                array(
                          'description'=> 'Tagging a Post',
                          'slug' => 'tag',
                     ) );

            }
        if (!term_exists('article', 'kind')) {
              wp_insert_term('article', 'kind',
                array(
                          'description'=> 'Longer Content',
                          'slug' => 'article',
                     ) );

            }
        if (!term_exists('note', 'kind')) {
              wp_insert_term('note', 'kind',
                array(
                          'description'=> 'Short Content',
                          'slug' => 'note',
                     ) );

            }
        if (!term_exists('photo', 'kind')) {
              wp_insert_term('photo', 'kind',
                array(
                          'description'=> 'Image Post',
                          'slug' => 'photo',
                     ) );

            }


}

function activate_kinds()
    {
	register_taxonomy_kind();
	kind_defaultterms();
    }

register_activation_hook( __FILE__, 'activate_kinds' );

add_filter('post_link', 'kind_permalink', 10, 3);
add_filter('post_type_link', 'kind_permalink', 10, 3);
 
function kind_permalink($permalink, $post_id, $leavename) {
    if (strpos($permalink, '%kind%') === FALSE) return $permalink;
     
        // Get post
        $post = get_post($post_id);
        if (!$post) return $permalink;
 
        // Get taxonomy terms
        $terms = wp_get_object_terms($post->ID, 'kind');   
        if (!is_wp_error($terms) && !empty($terms) && is_object($terms[0])) $taxonomy_slug = $terms[0]->slug;
        else $taxonomy_slug = 'standard';
 
    return str_replace('%kind%', $taxonomy_slug, $permalink);
}   

function kind_archive_title($title)
 {
     if ( is_tax( 'kind' ) ) {
                if ( is_tax( 'kind', 'note' ) ) {
                        $title = _x( 'Notes', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'article' ) ) {
                        $title = _x( 'Articles', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'bookmark' ) ) {
                        $title = _x( 'Bookmarks', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'favorite' ) ) {
                        $title = _x( 'Favorites', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'like' ) ) {
                        $title = _x( 'Likes', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'photo' ) ) {
                        $title = _x( 'Photos', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'reply' ) ) {
                        $title = _x( 'Replies', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'repost' ) ) {
                        $title = _x( 'Repost', 'kind archive title', 'mf2_s' );
                } elseif ( is_tax( 'kind', 'rsvp' ) ) {
                        $title = _x( 'RSVP', 'kind archive title', 'mf2_s' );
                }
                  elseif ( is_tax( 'kind', 'tag' ) ) {
                        $title = _x( 'Tags', 'kind archive title', 'mf2_s' );
                }
	 }
	return $title;
   }

add_filter('get_the_archive_title', 'kind_archive_title', 10, 3);




function json_rest_add_kindmeta($_post,$post,$context) {
	$response = get_post_meta( $post["ID"], 'response');
	if (!empty($response)) { $_post['response'] = $response; }
	return $_post;
}

add_filter("json_prepare_post",'json_rest_add_kindmeta',10,3);

function postkind_plugin_notice() {
    if (!class_exists("WebMentionPlugin"))
        {
            echo '<div class="error"><p>';
           _e( 'This Plugin Requires Webmention Support', 'post_kinds' );
            echo '</p></div>';
        }
}
add_action( 'admin_notices', 'postkind_plugin_notice' );

function iwt_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=iwt_options">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
 
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'iwt_settings_link' );

?>
