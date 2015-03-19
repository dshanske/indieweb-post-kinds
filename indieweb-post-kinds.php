<?php
/**
 * Plugin Name: IndieWeb Post Kinds
 * Plugin URI: https://wordpress.org/plugins/indieweb-post-kinds/
 * Description: Ever want to reply to someone else's post with a post on your own site? Or to "like" someone else's post, but with your own site?
 * Version: 1.2.3
 * Author: David Shanske
 * Author URI: http://david.shanske.com
 * Text Domain: Post kinds
 */

if ( ! defined( 'POST_KIND_EXCLUDE' ) )
    define('POST_KIND_EXCLUDE', 'play,wish,checkin');

if ( ! defined( 'MULTIKIND' ) )
    define('MULTIKIND', '0');

// If MultiKind is not enabled, load the selector
if (MULTIKIND=='0')
   {
	require_once( plugin_dir_path( __FILE__ ) . 'kind-select.php');
   }
// Else Load Checkboxes
else {
	require_once( plugin_dir_path( __FILE__ ) . 'multikind.php');
     }
 // Config Settings
require_once( plugin_dir_path( __FILE__ ) . '/iwt-config.php');
// Add Kind Post Metadata
require_once( plugin_dir_path( __FILE__ ) . '/kind-postmeta.php');
// Add Kind Functions
require_once( plugin_dir_path( __FILE__ ) . '/kind-functions.php');
// Add Kind Display Functions
require_once( plugin_dir_path( __FILE__ ) . '/kind-view.php');
// Add Kind Meta Display Functions
require_once( plugin_dir_path( __FILE__ ) . '/kind-meta.php');
// Add Kind Version of Semantic Linkbacks Comment Function
require_once( plugin_dir_path( __FILE__ ) . '/kind-semantics.php');



// Add Embed Functions for Commonly Embedded Websites not Supported by Wordpress
require_once( plugin_dir_path( __FILE__ ) . '/embeds.php');

// Register Kind Taxonomy
add_action( 'init', 'register_taxonomy_kind' );

// Semantic Linkbacks Override for Comments
add_action( 'init', 'kind_remove_semantics', 11);

// Load stylesheets
add_action( 'wp_enqueue_scripts', 'kindstyle_load' );
add_action('admin_enqueue_scripts', 'kind_admin_style');

// Add a Settings Link to the Plugins Page
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'iwt_settings_link' );

// On Activation, add terms
register_activation_hook( __FILE__, 'activate_kinds' );

// Add Kind Permalinks
add_filter('post_link', 'kind_permalink', 10, 3);
add_filter('post_type_link', 'kind_permalink', 10, 3);

// Return Kind Meta as part of the JSON Rest API
add_filter("json_prepare_post",'json_rest_add_kindmeta',10,3);

// Add the Correct Archive Title to Kind Archives
add_filter('get_the_archive_title', 'kind_archive_title', 10, 3);

// Add a notice to the Admin Pages if the WordPress Webmentions Plugin isn't Activated
add_action( 'admin_notices', 'postkind_plugin_notice' );

// Trigger Webmention on Change in Post Status
add_filter('transition_post_status', 'it_transition', 10, 3);

// Add Response to Feed
add_filter('the_content_feed', 'kind_content_feed');

function kindstyle_load() {
        wp_enqueue_style( 'kind', plugin_dir_url( __FILE__ ) . 'kind.min.css');
  }

function kind_admin_style() {
    wp_enqueue_style('kind-admin', plugins_url('kind-admin.min.css', __FILE__));
}

function iwt_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page=iwt_options">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

function activate_kinds() {
  if ( function_exists('iwt_plugin_notice') ) {
    deactivate_plugins( plugin_basename( __FILE__ ) );
    wp_die( 'You have Indieweb Taxonomy activated. Post Kinds replaces this plugin. Please disable Taxonomy before activating' );
  }
  register_taxonomy_kind();
  kind_defaultterms();
}

function register_taxonomy_kind() {
	load_plugin_textdomain( 'Post Kind', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        $labels = array( 
        'name' => _x( 'Kinds', 'Post kind' ),
        'singular_name' => _x( 'Kind', 'Post kind' ),
        'search_items' => _x( 'Search Kinds', 'Post kind' ),
        'popular_items' => _x( 'Popular Kinds', 'Post kind' ),
        'all_items' => _x( 'All Kinds', 'Post kind' ),
        'parent_item' => _x( 'Parent Kind', 'Post kind' ),
        'parent_item_colon' => _x( 'Parent Kind:', 'Post kind' ),
        'edit_item' => _x( 'Edit Kind', 'Post kind' ),
        'update_item' => _x( 'Update Kind', 'Post kind' ),
        'add_new_item' => _x( 'Add New Kind', 'Post kind' ),
        'new_item_name' => _x( 'New Kind', 'Post kind' ),
        'separate_items_with_commas' => _x( 'Separate kinds with commas', 'Post kind' ),
        'add_or_remove_items' => _x( 'Add or remove kinds', 'Post kind' ),
        'choose_from_most_used' => _x( 'Choose from the most used kinds', 'Post kind' ),
        'menu_name' => _x( 'Kinds', 'Post kind' ),
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

/**
  * Returns an array of post kind slugs to their translated and pretty display versions
	 *

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
                        'play'   => _x( 'Play', 'Post kind' )
	        );
        return apply_filters( 'kind_strings', $strings );
	}

/**
  * Returns an array of post kind slugs to their pluralized translated and pretty display versions
         *

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
                        'play'   => _x( 'Plays', 'Post kind' )    
                );
        return apply_filters( 'kind_strings_plural', $strings );
        }


/**
  * Returns an array of post kind slugs to their translated verbs
         *

         *
         * @return array The array of translated post kind verbs.
         */
        function get_post_kind_verb_strings() {
               	$strings = array(
                        'article' => _x( ' ', 'Post kind verbs' ),
                       	'note'    => _x( ' ',    'Post kind verbs' ),
                        'reply'     => _x( 'In Reply To',     'Post kind verbs' ),
                        'repost'  => _x( 'Reposted',  'Post kind verbs' ),
                        'like'     => _x( 'Liked',     'Post kind verbs' ),
                        'favorite'    => _x( 'Favorited',    'Post kind verbs' ),
                        'bookmark'    => _x( 'Bookmarked',    'Post kind verbs' ),
                        'photo'   => _x( ' ',   'Post kind verbs' ),
                        'tag'    => _x( 'Tagged',    'Post kind verbs' ),
                        'rsvp'    => _x( 'RSVPed',    'Post kind verbs' ),
                        'listen'    => _x( 'Listened to ',    'Post kind verbs' ),
                        'watch'   => _x( 'Watched', 'Post kind' ),
                        'checkin'   => _x( 'Checked In', 'Post kind' ),
                        'wish'   => _x( 'Desires', 'Post kind' ),
                        'play'   => _x( 'Played', 'Post kind' )    
                );
               return apply_filters( 'kind_verbs', $strings );

        }


/**
 * Retrieves an array of post kind slugs.
 *
 * @return array The array of post kind slugs.
 */
function get_post_kind_slugs() {
	$slugs = array_keys( get_post_kind_strings() );
	return array_combine( $slugs, $slugs );
}

/**
	 * Returns a pretty, translated version of a post kind slug
	 *
	 *
	 * @param string $slug A post format slug.
	 * @return string The translated post format name.
	 */
function get_post_kind_string( $slug ) {
	$strings = get_post_kind_strings();
	     return ( isset( $strings[$slug] ) ) ? $strings[$slug] : '';
	}

/**
 * Returns a link to a post kind index.
 *
 *
 * @param string $kind The post kind slug.
 * @return string The post kind term link.
 */
function get_post_kind_link( $kind ) {
	$term = get_term_by('slug', $kind, 'kind' );
	if ( ! $term || is_wp_error( $term ) )
		return false;
	return get_term_link( $term );
}

/**
 * Returns true if kind is a response type kind .
 *
 *
 * @param string $kind The post kind slug.
 * @return true/false.
 */
function response_kind( $kind ) {
        $not_responses = array( "article", "note" , "photo");
        if (in_array($kind, $not_responses)) { return false; }
        else { return true; }
}


// Sets up some starter terms...unless terms already exist 
// or any of the existing terms are defined
function kind_defaultterms () {
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
        if (!term_exists('listen', 'kind')) {
              wp_insert_term('listen', 'kind',
                array(
                          'description'=> 'Listen',
                          'slug' => 'listen',
                     ) );

            }
        if (!term_exists('watch', 'kind')) {
              wp_insert_term('watch', 'kind',
                array(
                          'description'=> 'Watch',
                          'slug' => 'watch',
                     ) );

            }
        if (!term_exists('checkin', 'kind')) {
              wp_insert_term('checkin', 'kind',
                array(
                          'description'=> 'Checkin',
                          'slug' => 'checkin',
                     ) );

            }
        if (!term_exists('play', 'kind')) {
              wp_insert_term('play', 'kind',
                array(
                          'description'=> 'Game Play',
                          'slug' => 'play',
                     ) );

            }
        if (!term_exists('wish', 'kind')) {
              wp_insert_term('wish', 'kind',
                array(
                          'description'=> 'Wish or Desire',
                          'slug' => 'wish',
                     ) );

            }

       // Allows for extensions to add terms to the plugin
       do_action('kind_add_term');

}

 
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
     $strings = get_post_kind_strings_plural();
     if ( is_tax( 'kind' ) ) {
		foreach ($strings as $key => $string)
		     { 
			if ( is_tax( 'kind', $key) )
			   { 
				$title = $string;
				return $title;
			   }
                     }
	 }
    return $title;
   }

function it_publish ( $ID, $post=null)
  {
     $cites = get_post_meta($ID, 'mf2_cite', true);
     if (empty($cites)) { return; }
     foreach ($cites as $cite) {
        if (!empty($cite) && isset($cite['url'])) {
     		  send_webmention(get_permalink($ID), $cite['url']);
        }
 	  }
  }


function it_transition($old,$new,$post){
  it_publish($post->ID,$post);
}

function json_rest_add_kindmeta($_post,$post,$context) {
	$response = get_post_meta( $post["ID"], 'mf2_cite');
	if (!empty($response)) { $_post['mf2_cite'] = $response; }
	return $_post;
}

function kind_content_feed($content, $feed_type) {
  $response = get_kind_response_display();
  $response = str_replace(']]>', ']]&gt;', $response);
  return $response . $content; 
}

function postkind_plugin_notice() {
    if (!class_exists("WebMentionPlugin"))
        {
           echo '<div class="error"><p>';
           echo '<a href="https://wordpress.org/plugins/webmention/">';
           _e( 'This Plugin Requires Webmention Support', 'post_kinds' );
            echo '</a></p></div>';
        }
}

function kind_remove_semantics() {
  if (class_exists('SemanticLinkbacksPlugin') ) {
    remove_filter('comment_text', array('SemanticLinkbacksPlugin', 'comment_text_excerpt'),12);
    add_filter('comment_text', 'kind_comment_text_excerpt', 12, 3);
  }
}
?>
